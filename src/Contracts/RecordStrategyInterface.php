<?php
declare(strict_types = 1);

namespace Interrupt\Contracts;

use DateInterval;

interface RecordStrategyInterface {
  public function getWindowSize(): DateInterval;
  public function mark(string $key): int;
  public function clear(string $key): void;
  public function __serialize(): array;
  public function __unserialize(array $data): void;
}
