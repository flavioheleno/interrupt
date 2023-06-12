<?php
declare(strict_types = 1);

namespace Interrupt\ServiceNameInflectors;

use Interrupt\Contracts\ServiceNameInflectorInterface;
use Psr\Http\Message\RequestInterface;

final class UriBasedInflector implements ServiceNameInflectorInterface {
  public function extract(RequestInterface $request): string {
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
