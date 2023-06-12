<?php
declare(strict_types = 1);

namespace Interrupt\Contracts;

use Psr\Http\Message\ResponseInterface;

interface FailureDetectorInterface {
  public function isFailure(ResponseInterface $response): bool;
}
