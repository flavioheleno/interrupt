<?php
declare(strict_types = 1);

namespace Interrupt\Contracts;

use Interrupt\CircuitBreakers\CircuitStateEnum;

interface CircuitBreakerInterface {
  public function isAvailable(string $serviceName): bool;
  public function successful(string $serviceName): CircuitStateEnum;
  public function failed(string $serviceName): CircuitStateEnum;
  public function reset(string $serviceName): void;
}
