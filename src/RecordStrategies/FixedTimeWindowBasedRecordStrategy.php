<?php
declare(strict_types = 1);

namespace Interrupt\RecordStrategies;

use DateInterval;
use Interrupt\Contracts\RecordStrategyInterface;
use Psr\Clock\ClockInterface;

/**
 * @link https://gist.github.com/tengergou/b823b217180005224362ec1a82dac79a
 */
final class FixedTimeWindowBasedRecordStrategy implements RecordStrategyInterface {
  private ClockInterface $clock;
  /**
   * Window size interval.
   * Default: 15 seconds
   */
  private DateInterval $windowSize;
  /**
   * @var array<string, array{\DateTimeImmutable, int}>
   */
  private array $records = [];

  public function __construct(ClockInterface $clock, DateInterval $windowSize = new DateInterval('PT15S')) {
    $this->clock = $clock;
    $this->windowSize = $windowSize;
  }

  public function getWindowSize(): DateInterval {
    return $this->windowSize;
  }

  public function mark(string $key): int {
    if (isset($this->records[$key]) === false || count($this->records[$key]) === 0) {
      $this->records[$key] = [$this->clock->now(), 1];

      return 1;
    }

    $now = $this->clock->now();
    [$timestamp, $recordCount] = $this->records[$key];
    if ($now < $timestamp->add($this->windowSize)) {
      $this->records[$key] = [$timestamp, ++$recordCount];

      return $recordCount;
    }

    $this->records[$key] = [$now, 1];

    return 1;
  }

  public function clear(string $key): void {
    unset($this->records[$key]);
  }

  /**
   * @return array{
   *   0: \Psr\Clock\ClockInterface,
   *   1: \DateInterval,
   *   2: array<string, array<\DateTimeImmutable, int>>
   * }
   */
  public function __serialize(): array {
    return [
      $this->clock,
      $this->windowSize,
      $this->records
    ];
  }

  /**
   * @param array{
   *   0: \Psr\Clock\ClockInterface,
   *   1: \DateInterval,
   *   2: array<string, array<\DateTimeImmutable, int>>
   * } $data
   */
  public function __unserialize(array $data): void {
    [
      $this->clock,
      $this->windowSize,
      $this->records
    ] = $data;
  }
}
