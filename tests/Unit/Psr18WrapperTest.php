<?php
declare(strict_types = 1);

namespace Interrupt\Test\Unit;

use Exception;
use Interrupt\CircuitBreakers\CircuitStateEnum;
use Interrupt\Contracts\CircuitBreakerInterface;
use Interrupt\Contracts\FailureDetectorInterface;
use Interrupt\Contracts\ServiceNameResolverInterface;
use Interrupt\Exceptions\ServiceUnavailableException;
use Interrupt\Psr18Wrapper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

#[CoversClass(Psr18Wrapper::class)]
final class Psr18WrapperTest extends TestCase {
  public function testServiceUnavailableException(): void {
    $serviceNameResolver = $this->createMock(ServiceNameResolverInterface::class);
    $serviceNameResolver
      ->expects($this->once())
      ->method('handle')
      ->willReturn('test');

    $circuitBreaker = $this->createMock(CircuitBreakerInterface::class);
    $circuitBreaker
      ->expects($this->once())
      ->method('isAvailable')
      ->with($this->identicalTo('test'))
      ->willReturn(false);

    $psr18Wrapper = new Psr18Wrapper(
      $this->createMock(ClientInterface::class),
      $serviceNameResolver,
      $circuitBreaker,
      $this->createMock(FailureDetectorInterface::class)
    );

    $this->expectException(ServiceUnavailableException::class);
    $this->expectExceptionMessage('Service "test" is currently unavailable');

    $psr18Wrapper->sendRequest($this->createMock(RequestInterface::class));
  }

  public function testRequestFailure(): void {
    $serviceNameResolver = $this->createMock(ServiceNameResolverInterface::class);
    $serviceNameResolver
      ->expects($this->once())
      ->method('handle')
      ->willReturn('test');

    $circuitBreaker = $this->createMock(CircuitBreakerInterface::class);
    $circuitBreaker
      ->expects($this->once())
      ->method('isAvailable')
      ->with($this->identicalTo('test'))
      ->willReturn(true);
    $circuitBreaker
      ->expects($this->once())
      ->method('failed')
      ->with($this->identicalTo('test'))
      ->willReturn(CircuitStateEnum::CLOSED);

    $response = $this->createMock(ResponseInterface::class);
    $request = $this->createMock(RequestInterface::class);

    $client = $this->createMock(ClientInterface::class);
    $client
      ->expects($this->once())
      ->method('sendRequest')
      ->with($this->identicalTo($request))
      ->willReturn($response);

    $failureDetector = $this->createMock(FailureDetectorInterface::class);
    $failureDetector
      ->expects($this->once())
      ->method('isFailure')
      ->with($this->identicalTo($response))
      ->willReturn(true);

    $psr18Wrapper = new Psr18Wrapper(
      $client,
      $serviceNameResolver,
      $circuitBreaker,
      $failureDetector
    );

    $this->assertSame($response, $psr18Wrapper->sendRequest($request));
  }

  public function testRequestSuccess(): void {
    $serviceNameResolver = $this->createMock(ServiceNameResolverInterface::class);
    $serviceNameResolver
      ->expects($this->once())
      ->method('handle')
      ->willReturn('test');

    $circuitBreaker = $this->createMock(CircuitBreakerInterface::class);
    $circuitBreaker
      ->expects($this->once())
      ->method('isAvailable')
      ->with($this->identicalTo('test'))
      ->willReturn(true);
    $circuitBreaker
      ->expects($this->once())
      ->method('successful')
      ->with($this->identicalTo('test'))
      ->willReturn(CircuitStateEnum::CLOSED);

    $response = $this->createMock(ResponseInterface::class);
    $request = $this->createMock(RequestInterface::class);

    $client = $this->createMock(ClientInterface::class);
    $client
      ->expects($this->once())
      ->method('sendRequest')
      ->with($this->identicalTo($request))
      ->willReturn($response);

    $failureDetector = $this->createMock(FailureDetectorInterface::class);
    $failureDetector
      ->expects($this->once())
      ->method('isFailure')
      ->with($this->identicalTo($response))
      ->willReturn(false);

    $psr18Wrapper = new Psr18Wrapper(
      $client,
      $serviceNameResolver,
      $circuitBreaker,
      $failureDetector
    );

    $this->assertSame($response, $psr18Wrapper->sendRequest($request));
  }

  public function testRequestThrowsClientException(): void {
    $serviceNameResolver = $this->createMock(ServiceNameResolverInterface::class);
    $serviceNameResolver
      ->expects($this->once())
      ->method('handle')
      ->willReturn('test');

    $circuitBreaker = $this->createMock(CircuitBreakerInterface::class);
    $circuitBreaker
      ->expects($this->once())
      ->method('isAvailable')
      ->with($this->identicalTo('test'))
      ->willReturn(true);
    $circuitBreaker
      ->expects($this->once())
      ->method('failed')
      ->with($this->identicalTo('test'))
      ->willReturn(CircuitStateEnum::CLOSED);

    $request = $this->createMock(RequestInterface::class);

    $client = $this->createMock(ClientInterface::class);
    $client
      ->expects($this->once())
      ->method('sendRequest')
      ->with($this->identicalTo($request))
      ->willThrowException(new class extends Exception implements ClientExceptionInterface {});

    $psr18Wrapper = new Psr18Wrapper(
      $client,
      $serviceNameResolver,
      $circuitBreaker,
      $this->createMock(FailureDetectorInterface::class)
    );

    $this->expectException(ClientExceptionInterface::class);

    $psr18Wrapper->sendRequest($request);
  }

  public function testRequestThrowsGenericException(): void {
    $serviceNameResolver = $this->createMock(ServiceNameResolverInterface::class);
    $serviceNameResolver
      ->expects($this->once())
      ->method('handle')
      ->willReturn('test');

    $circuitBreaker = $this->createMock(CircuitBreakerInterface::class);
    $circuitBreaker
      ->expects($this->once())
      ->method('isAvailable')
      ->with($this->identicalTo('test'))
      ->willReturn(true);
    $circuitBreaker
      ->expects($this->never())
      ->method('failed');

    $request = $this->createMock(RequestInterface::class);

    $client = $this->createMock(ClientInterface::class);
    $client
      ->expects($this->once())
      ->method('sendRequest')
      ->with($this->identicalTo($request))
      ->willThrowException(new RuntimeException());

    $psr18Wrapper = new Psr18Wrapper(
      $client,
      $serviceNameResolver,
      $circuitBreaker,
      $this->createMock(FailureDetectorInterface::class)
    );

    $this->expectException(RuntimeException::class);

    $psr18Wrapper->sendRequest($request);
  }
}
