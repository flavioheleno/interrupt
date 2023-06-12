<?php
declare(strict_types = 1);

namespace Interrupt;

use Exception;
use Interrupt\Contracts\FailureDetectorInterface;
use Interrupt\Contracts\CircuitBreakerInterface;
use Interrupt\Contracts\ServiceNameInflectorInterface;
use Interrupt\Exceptions\ServiceUnavailableException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\RequestInterface;

class Psr18Wrapper implements ClientInterface {
  protected ClientInterface $client;
  protected ServiceNameInflectorInterface $serviceNameInflector;
  protected CircuitBreakerInterface $circuitBreaker;
  protected FailureDetectorInterface $failureDetector;

  public function __construct(
    ClientInterface $client,
    ServiceNameInflectorInterface $serviceNameInflector,
    CircuitBreakerInterface $circuitBreaker,
    FailureDetectorInterface $failureDetector
  ) {
    $this->client = $client;
    $this->serviceNameInflector = $serviceNameInflector;
    $this->circuitBreaker = $circuitBreaker;
    $this->failureDetector = $failureDetector;
  }

  /**
   * @throws \Interrupt\Exceptions\ServiceUnavailableException
   */
  public function sendRequest(RequestInterface $request): ResponseInterface {
    $serviceName = $this->serviceNameInflector->extract($request);
    if ($this->circuitBreaker->isAvailable($serviceName) === false) {
      throw new ServiceUnavailableException("Service \"{$serviceName}\" is currently unavailable");
    }

    try {
      $response = $this->client->sendRequest($request);
      if ($this->failureDetector->isFailure($response) === true) {
        $this->circuitBreaker->failed($serviceName);

        return $response;
      }

      $this->circuitBreaker->successful($serviceName);

      return $response;
    } catch (Exception $exception) {
      if ($exception instanceof ClientExceptionInterface) {
        $this->circuitBreaker->failed($serviceName);
      }

      throw $exception;
    }
  }
}
