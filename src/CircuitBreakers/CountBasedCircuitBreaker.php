<?php
declare(strict_types = 1);

namespace Interrupt\CircuitBreakers;

use DateInterval;
use Interrupt\Contracts\RecordStrategyInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Clock\ClockInterface;
use Scale\Time\Hours;

class CountBasedCircuitBreaker extends AbstractCircuitBreaker {
  /**
   * Failure count threshold, after which CircuitBreaker state goes from CircuitStateEnum::CLOSED
   * to CircuitStateEnum::OPEN
   * Default: 20
   */
  protected int $failureCount;

  public function __construct(
    ClockInterface $clock,
    CacheItemPoolInterface $cacheItemPool,
    RecordStrategyInterface $recordStrategy,
    DateInterval $coolDownInterval = new DateInterval('PT10S'),
    int $failureCount = 20
  ) {
    parent::__construct($clock, $cacheItemPool, $recordStrategy, $coolDownInterval);

    $this->failureCount = $failureCount;
  }

  public function successful(string $serviceName): CircuitStateEnum {
    $item = $this->cacheItemPool->getItem("interrupt/{$serviceName}");
    if ($item->isHit() === false) {
      $item->set(
        [
          'state' => CircuitStateEnum::CLOSED,
          'record' => $this->recordStrategy,
          'updatedAt' => $this->clock->now()
        ]
      );
    }

    /**
     * @var array{
     *   state: \Interrupt\CircuitBreakers\CircuitStateEnum,
     *   record: \Interrupt\Contracts\RecordStrategyInterface,
     *   updatedAt: \DateTimeImmutable
     * }
     */
    $data = $item->get();

    // half-open && success -> closed
    if (
      $data['state'] === CircuitStateEnum::HALF_OPEN
    ) {
      $data['state'] = CircuitStateEnum::CLOSED;
      $data['record']->clear("{$serviceName}.failure");
      $data['updatedAt'] = $this->clock->now();
    }

    $item->set($data);
    $item->expiresAfter(Hours::IN_SECONDS);

    $this->cacheItemPool->save($item);

    return $data['state'];
  }

  public function failed(string $serviceName): CircuitStateEnum {
    $item = $this->cacheItemPool->getItem("interrupt/{$serviceName}");
    if ($item->isHit() === false) {
      $item->set(
        [
          'state' => CircuitStateEnum::CLOSED,
          'record' => $this->recordStrategy,
          'updatedAt' => $this->clock->now()
        ]
      );
    }

    /**
     * @var array{
     *   state: \Interrupt\CircuitBreakers\CircuitStateEnum,
     *   record: \Interrupt\Contracts\RecordStrategyInterface,
     *   updatedAt: \DateTimeImmutable
     * }
     */
    $data = $item->get();
    $failCount = $data['record']->mark("{$serviceName}.failure");

    // closed && failure over threshold -> open
    // half-open && failure -> open
    if (
      ($data['state'] === CircuitStateEnum::CLOSED && $failCount >= $this->failureCount) ||
      $data['state'] === CircuitStateEnum::HALF_OPEN
    ) {
      $data['state'] = CircuitStateEnum::OPEN;
    }

    $data['updatedAt'] = $this->clock->now();

    $item->set($data);
    $item->expiresAfter(Hours::IN_SECONDS);

    $this->cacheItemPool->save($item);

    return $data['state'];
  }
}
