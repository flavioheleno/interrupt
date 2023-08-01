<?php
declare(strict_types = 1);

namespace Interrupt\Contracts;

use Psr\Http\Message\ResponseInterface;

interface FailureDetectorInterface {
  /**
   * Return true if the given $response contains a failure (concrete implementation dependent) request
   * and false otherwise
   */
  public function isFailure(ResponseInterface $response): bool;
}
