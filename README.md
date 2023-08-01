# Interrupt - Uninterrupted Reliability for HTTP Requests

This library implements a PSR-18 compliant Circuit Breaker wrapper that can be used to ensure service stability and
prevent overload or degradation in remote service calls.

## Acknowledgement

This library is heavily inspired by:

* [ackintosh/ganesha](https://github.com/ackintosh/ganesha)
* [PrestaShop/circuit-breaker](https://github.com/PrestaShop/circuit-breaker)

## Installation

To use Interim, install it using composer:

```bash
composer require flavioheleno/interrupt
```

## Usage

```php
// any PSR-18 compliant HTTP Client implementation
// $httpClient = new ...

// service name is based on its scheme + host + port
// eg. https://api.example.org/v1 -> https://api.example.org
// eg. https://api.example.org:5000/v1 -> https://api.example.org:5000
$serviceNameInflector = Interrupt\ServiceNameInflectors\UriBasedInflector();

// any PSR-6 compliant Cache Item Pool implementation
// $cacheItemPool = new ...

// any PSR-20 compliant Clock implementation
// $clock = new ...

// can be replaced by FixedTimeWindowBasedRecordStrategy
$recordStrategy = new Interrupt\RecordStrategies\SlidingTimeWindowBasedRecordStrategy(
  $clock
);

// can be replaced by CountBasedCircuitBreaker
$circuitBreaker = new Interrupt\CircuitBreakers\RateBasedCircuitBreaker(
  $clock,
  $cacheItemPool,
  $recordStrategy
);

$failureDetector = new Interrupt\FailureDetectors\HttpStatusBasedFailureDetector();

$client = new Psr18Wrapper(
  $httpClient,
  $serviceNameInflector,
  $circuitBreaker,
  $failureDetector
);

// when the called service is unavailable, $client will throw an
// Interrupt\Exceptions\ServiceUnavailableException exception.
```

## Components

Interrupt is built around the concept of components to allow easy integration with different environments or frameworks,
making it a flexible and customizable library.

### Service Name Inflector

To keep track of services accessed by the wrapped client, Interrupt uses the concept of
[Service Name Inflector](src/Contracts/ServiceNameInflectorInterface.php), that generates consistent service names
based on request attributes.

Interrupt is distributed with [UriBasedInflector](src/ServiceNameInflectors/UriBasedInflector.php), an inflector
implementation that generates service names from the request URI components.

### Record Strategy

The [Record Strategy](src/Contracts/RecordStrategyInterface.php) determines how Interrupt keeps track of failure events
during a period of time.

A [FixedTimeWindowBasedRecordStrategy](src/RecordStrategies/FixedTimeWindowBasedRecordStrategy.php) that uses a
predefined time interval to register failure records within its duration. Once the interval is over, the recorded
failures are cleaned up and a new fixed interval starts.

A [SlidingTimeWindowBasedRecordStrategy](src/RecordStrategies/SlidingTimeWindowBasedRecordStrategy.php) that uses a
moving or shifting time interval to register failure records. Instead of fixed intervals, the time window slides (or
moves along) with the failure stream.

The *Sliding Time Window* approach allows for continuous analysis of the most recent data while still considering a
specific timeframe.

> **Note**
> Both strategies require a [PSR-20](https://www.php-fig.org/psr/psr-20/) compatible Clock implementation.

### Failure Detector

Failure can be subjective to context so Interrupt relies on [Failure Detector](src/Contracts/FailureDetectorInterface.php)
to detect context-dependant failures.

Embedded in [Psr18Wrapper](src/Psr18Wrapper.php) is the failure detection for network issues, timeouts and any other
thrown exception that extends `Psr\Http\Client\ClientExceptionInterface`.

In addition to that, the [HttpStatusBasedFailureDetector](src/FailureDetectors/HttpStatusBasedFailureDetector.php)
interprets the HTTP Status Code as signal of failure.

### Circuit Breakers

Interrupt comes with two [Circuit Breakers](src/Contracts/CircuitBreakerInterface.php) implementations:

A [CountBasedCircuitBreaker](src/CircuitBreakers/CountBasedCircuitBreaker.php) that monitors the number of failures
recorded and automatically interrupts the flow of requests if the threshold is exceeded.

A [RateBasedCircuitBreaker](src/CircuitBreakers/RateBasedCircuitBreaker.php) that monitors the rate or frequency of
failures recorded and automatically interrupts the flow of requests if the error rate surpasses a predefined threshold.

> **Note**
> Both circuit breakers require a [PSR-6](https://www.php-fig.org/psr/psr-6/) compatible Cache implementation.

## License

This library is licensed under the [MIT License](LICENSE).
