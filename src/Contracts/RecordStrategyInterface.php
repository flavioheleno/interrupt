<?php
declare(strict_types = 1);

namespace Interrupt\Contracts;

use DateInterval;
use Serializable;

interface RecordStrategyInterface extends Serializable {
  public function getWindowSize(): DateInterval;
  public function mark(string $key): int;
  public function clear(string $key): void;
}
