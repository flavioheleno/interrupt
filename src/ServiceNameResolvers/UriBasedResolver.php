<?php
declare(strict_types = 1);

namespace Interrupt\ServiceNameResolvers;

use Interrupt\Contracts\ServiceNameResolverInterface;
use Psr\Http\Message\RequestInterface;

final class UriBasedResolver implements ServiceNameResolverInterface {
  public function handle(RequestInterface $request): string {
    $uri = $request->getUri();

    $name = $uri->getScheme();
    $name .= '://';
    $name .= $uri->getHost();
    if ($uri->getPort() === null) {
      return $name;
    }

    $name .= ':';
    $name .= $uri->getPort();

    return $name;
  }
}
