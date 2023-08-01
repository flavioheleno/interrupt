<?php
declare(strict_types = 1);

namespace Interrupt\Contracts;

use Interrupt\CircuitBreakers\CircuitStateEnum;

interface CircuitBreakerInterface {
  /**
   * Return true if the current circuit state is CircuitStateEnum::HALF_OPEN or CircuitStateEnum::CLOSE for
   * the given $serviceName and false otherwise (CircuitStateEnum::OPEN)
   */
  public function isAvailable(string $serviceName): bool;
  /**
   * Record a successful request to $serviceName and returns the current circuit state
   */
  public function successful(string $serviceName): CircuitStateEnum;
  /**
   * Record a failed request to $serviceName and returns the current circuit state
   */
  public function failed(string $serviceName): CircuitStateEnum;
  /**
   * Reset the current circuit state for the given $serviceName
   */
  public function reset(string $serviceName): void;
}
