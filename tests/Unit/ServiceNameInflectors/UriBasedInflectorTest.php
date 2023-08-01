<?php
declare(strict_types = 1);

namespace Interrupt\Test\Unit\ServiceNameInflectors;

use Interrupt\ServiceNameInflectors\UriBasedInflector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

#[CoversClass(UriBasedInflector::class)]
final class UriBasedInflectorTest extends TestCase {
  public function testExtractStdPort(): void {
    $uri = $this->createMock(UriInterface::class);
    $uri->method('getScheme')->willReturn('http');
    $uri->method('getHost')->willReturn('example.com');
    $uri->method('getPort')->willReturn(null);

    $request = $this->createMock(ServerRequestInterface::class);
    $request->method('getUri')->willReturn($uri);

    $inflector = new UriBasedInflector();

    $this->assertSame('http://example.com', $inflector->extract($request));
  }

  public function testExtractCustomPort(): void {
    $uri = $this->createMock(UriInterface::class);
    $uri->method('getScheme')->willReturn('http');
    $uri->method('getHost')->willReturn('example.com');
    $uri->method('getPort')->willReturn(8080);

    $request = $this->createMock(ServerRequestInterface::class);
    $request->method('getUri')->willReturn($uri);

    $inflector = new UriBasedInflector();

    $this->assertSame('http://example.com:8080', $inflector->extract($request));
  }
}
