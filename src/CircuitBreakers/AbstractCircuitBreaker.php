<?php
declare(strict_types = 1);

namespace Interrupt\CircuitBreakers;

use DateInterval;
use Interrupt\Contracts\CircuitBreakerInterface;
use Interrupt\Contracts\RecordStrategyInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Clock\ClockInterface;
use Scale\Time\Hours;

abstract class AbstractCircuitBreaker implements CircuitBreakerInterface {
  protected ClockInterface $clock;
  protected CacheItemPoolInterface $cacheItemPool;
  protected RecordStrategyInterface $recordStrategy;
  /**
   * Cool down interval in seconds, after which CircuitBreaker state goes from CircuitStateEnum::OPEN
   * to CircuitStateEnum::HALF_OPEN
   * Default: 10 seconds
   */
  protected DateInterval $coolDownInterval;

  abstract public function successful(string $serviceName): CircuitStateEnum;
  abstract public function failed(string $serviceName): CircuitStateEnum;

  public function __construct(
    ClockInterface $clock,
    CacheItemPoolInterface $cacheItemPool,
    RecordStrategyInterface $recordStrategy,
    DateInterval $coolDownInterval = new DateInterval('PT10S')
  ) {
    $this->clock = $clock;
    $this->cacheItemPool = $cacheItemPool;
    $this->recordStrategy = $recordStrategy;
    $this->coolDownInterval = $coolDownInterval;
  }

  public function isAvailable(string $serviceName): bool {
    $item = $this->cacheItemPool->getItem("interrupt/{$serviceName}");
    if ($item->isHit() === false) {
      $item->set(
        [
          'state' => CircuitStateEnum::CLOSED,
          'record' => $this->recordStrategy,
          'updatedAt' => $this->clock->now()
        ]
      );
      $item->expiresAfter(Hours::IN_SECONDS);

      $this->cacheItemPool->save($item);

      return true;
    }

    /**
     * @var array{
     *   state: CircuitStateEnum,
     *   record: RecordStrategyInterface,
     *   updatedAt: \DateTimeImmutable
     * }
     */
    $data = $item->get();

    // if service is unavailable, determine if the cool down interval has passed
    if (
      $data['state'] === CircuitStateEnum::OPEN &&
      $data['updatedAt'] <= $this->clock->now()->sub($this->coolDownInterval)
    ) {
      $data['state'] = CircuitStateEnum::HALF_OPEN;
      $data['updatedAt'] = $this->clock->now();

      $item->set($data);
      $item->expiresAfter(Hours::IN_SECONDS);

      $this->cacheItemPool->save($item);

      return true;
    }

    return $data['state'] !== CircuitStateEnum::OPEN;
  }

  public function reset(string $serviceName): void {
    $this->cacheItemPool->deleteItem("interrupt/{$serviceName}");
  }
}
