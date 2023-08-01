<?php
declare(strict_types = 1);

namespace Interrupt\Contracts;

use Psr\Http\Message\RequestInterface;

interface ServiceNameResolverInterface {
  /**
   * Return a service name from the given $request
   */
  public function handle(RequestInterface $request): string;
}
