<?php
declare(strict_types = 1);

namespace Interrupt\Contracts;

use Psr\Http\Message\RequestInterface;

interface ServiceNameInflectorInterface {
  public function extract(RequestInterface $request): string;
}
