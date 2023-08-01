<?php
declare(strict_types = 1);

namespace Interrupt\Test\Unit\ServiceNameResolvers;

use Interrupt\ServiceNameResolvers\UriBasedResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

#[CoversClass(UriBasedResolver::class)]
final class UriBasedResolverTest extends TestCase {
  public function testExtractStdPort(): void {
    $uri = $this->createMock(UriInterface::class);
    $uri->method('getScheme')->willReturn('http');
    $uri->method('getHost')->willReturn('example.com');
    $uri->method('getPort')->willReturn(null);

    $request = $this->createMock(ServerRequestInterface::class);
    $request->method('getUri')->willReturn($uri);

    $resolver = new UriBasedResolver();

    $this->assertSame('http://example.com', $resolver->handle($request));
  }

  public function testExtractCustomPort(): void {
    $uri = $this->createMock(UriInterface::class);
    $uri->method('getScheme')->willReturn('http');
    $uri->method('getHost')->willReturn('example.com');
    $uri->method('getPort')->willReturn(8080);

    $request = $this->createMock(ServerRequestInterface::class);
    $request->method('getUri')->willReturn($uri);

    $resolver = new UriBasedResolver();

    $this->assertSame('http://example.com:8080', $resolver->handle($request));
  }
}
