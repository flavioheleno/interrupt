<?php
declare(strict_types = 1);

namespace Interrupt\Contracts;

use DateInterval;

interface RecordStrategyInterface {
  /**
   * Return the window size as a time interval
   */
  public function getWindowSize(): DateInterval;
  /**
   * Return the number recorded of _marks_ the given $key has within the window size
   */
  public function mark(string $key): int;
  /**
   * Remove all recorded - if any -, _marks_ of the given $key
   */
  public function clear(string $key): void;
  public function __serialize(): array;
  public function __unserialize(array $data): void;
}
