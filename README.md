# Interrupt

PSR-18 compliant Circuit Breaker wrapper.

## Acknowledgement

This library is heavily inspired by:

* [ackintosh/ganesha](https://github.com/ackintosh/ganesha)
* [PrestaShop/circuit-breaker](https://github.com/PrestaShop/circuit-breaker)

## Installation

To use Interim, simple run:

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

## License

This library is licensed under the [MIT License](LICENSE).
