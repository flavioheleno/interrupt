<?php
declare(strict_types = 1);

namespace Interrupt\Contracts;

use Psr\Http\Message\RequestInterface;

interface ServiceNameInflectorInterface {
  /**
   * Return a service name from the given $request
   */
  public function extract(RequestInterface $request): string;
}
