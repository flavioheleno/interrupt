<?php
declare(strict_types = 1);

namespace Interrupt\RecordStrategies;

use DateInterval;
use Interrupt\Contracts\RecordStrategyInterface;
use Psr\Clock\ClockInterface;

/**
 * @link https://gist.github.com/tengergou/822dbb51ab6d15751135e570d14b0a29
 */
final class SlidingTimeWindowBasedRecordStrategy implements RecordStrategyInterface {
  private ClockInterface $clock;
  /**
   * Window size interval.
   * Default: 15 seconds
   */
  private DateInterval $windowSize;
  /**
   * Max number of stored records.
   * Default: 50 items
   */
  private int $maxRecords;
  /**
   * @var array<string, \DateTimeImmutable[]>
   */
  private array $records = [];

  public function __construct(
    ClockInterface $clock,
    DateInterval $windowSize = new DateInterval('PT15S'),
    int $maxRecords = 50
  ) {
    $this->clock = $clock;
    $this->windowSize = $windowSize;
    $this->maxRecords = $maxRecords;
  }

  public function getWindowSize(): DateInterval {
    return $this->windowSize;
  }

  public function mark(string $key): int {
    if (isset($this->records[$key]) === false || count($this->records[$key]) === 0) {
      $this->records[$key][] = $this->clock->now();

      return 1;
    }

    $now = $this->clock->now();
    $startTime = $this->records[$key][0];
    $check = count($this->records[$key]);
    while ($check > 0) {
      if ($now >= $startTime->add($this->windowSize)) {
        $startTime = array_shift($this->records[$key]);
        $check--;

        continue;
      }

      break;
    }

    if ($now <= $startTime->add($this->windowSize)) {
      if (count($this->records[$key]) < $this->maxRecords) {
        $this->records[$key][] = $now;
      }

      return count($this->records[$key]);
    }

    $this->records[$key][] = $now;

    return count($this->records[$key]);
  }

  public function clear(string $key): void {
    unset($this->records[$key]);
  }

  /**
   * @return array{
   *   0: \Psr\Clock\ClockInterface,
   *   1: \DateInterval,
   *   2: int,
   *   3: array<string, \DateTimeImmutable[]>
   * }
   */
  public function __serialize(): array {
    return [
      $this->clock,
      $this->windowSize,
      $this->maxRecords,
      $this->records
    ];
  }

  /**
   * @param array{
   *   0: \Psr\Clock\ClockInterface,
   *   1: \DateInterval,
   *   2: int,
   *   3: array<string, \DateTimeImmutable[]>
   * } $data
   */
  public function __unserialize(array $data): void {
    [
      $this->clock,
      $this->windowSize,
      $this->maxRecords,
      $this->records
    ] = $data;
  }
}
