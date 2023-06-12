<?php
declare(strict_types = 1);

namespace Interrupt\Test\Unit\CircuitBreakers;

use DateInterval;
use DateTimeImmutable;
use Interrupt\CircuitBreakers\AbstractCircuitBreaker;
use Interrupt\CircuitBreakers\CircuitStateEnum;
use Interrupt\CircuitBreakers\CountBasedCircuitBreaker;
use Interrupt\Contracts\RecordStrategyInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Clock\ClockInterface;

#[CoversClass(AbstractCircuitBreaker::class)]
#[CoversClass(CountBasedCircuitBreaker::class)]
final class CountBasedCircuitBreakerTest extends TestCase {
  public function testSuccessfulCacheInit(): void {
    /** CLOCK MOCK **/
    $now = new DateTimeImmutable();

    $clock = $this->createMock(ClockInterface::class);
    $clock
      ->expects($this->any())
      ->method('now')
      ->willReturn($now);

    /** RECORD MOCK **/
    $record = $this->createMock(RecordStrategyInterface::class);

    /** CACHE ITEM MOCK **/
    // initial state: CLOSED
    $cacheInit = [
      'state' => CircuitStateEnum::CLOSED,
      'record' => $record,
      'updatedAt' => $now
    ];

    $item = $this->createMock(CacheItemInterface::class);
    $item
      ->expects($this->once())
      ->method('isHit')
      ->willReturn(false);
    $item
      ->expects($this->atLeast(1))
      ->method('set')
      ->with($this->identicalTo($cacheInit));
    $item
      ->expects($this->once())
      ->method('get')
      ->willReturn($cacheInit);

    /** CACHE POOL MOCK **/
    $cache = $this->createMock(CacheItemPoolInterface::class);
    $cache
      ->expects($this->once())
      ->method('getItem')
      ->with($this->identicalTo('interrupt/test'))
      ->willReturn($item);

    /** ACTUAL TEST **/
    $circuitBreaker = new CountBasedCircuitBreaker($clock, $cache, $record);

    $this->assertSame(CircuitStateEnum::CLOSED, $circuitBreaker->successful('test'));
  }

  public function testFailedCacheInit(): void {
    /** CLOCK MOCK **/
    $now = new DateTimeImmutable();

    $clock = $this->createMock(ClockInterface::class);
    $clock
      ->expects($this->any())
      ->method('now')
      ->willReturn($now);

    /** RECORD MOCK **/
    $record = $this->createMock(RecordStrategyInterface::class);

    /** CACHE ITEM MOCK **/
    // initial state: CLOSED
    $cacheInit = [
      'state' => CircuitStateEnum::CLOSED,
      'record' => $record,
      'updatedAt' => $now
    ];

    $item = $this->createMock(CacheItemInterface::class);
    $item
      ->expects($this->once())
      ->method('isHit')
      ->willReturn(false);
    $item
      ->expects($this->atLeast(1))
      ->method('set')
      ->with($this->identicalTo($cacheInit));
    $item
      ->expects($this->once())
      ->method('get')
      ->willReturn($cacheInit);

    /** CACHE POOL MOCK **/
    $cache = $this->createMock(CacheItemPoolInterface::class);
    $cache
      ->expects($this->once())
      ->method('getItem')
      ->with($this->identicalTo('interrupt/test'))
      ->willReturn($item);

    /** ACTUAL TEST **/
    $circuitBreaker = new CountBasedCircuitBreaker($clock, $cache, $record);

    $this->assertSame(CircuitStateEnum::CLOSED, $circuitBreaker->failed('test'));
  }

  /**
   * Given that the Circuit state is HALF_OPEN, once a success is recorded, the Circuit state must be set to CLOSED.
   * Beside the state change, it is also required that previously recorded failure get cleared.
   */
  public function testHalfOpenToClosed(): void {
    /** CLOCK MOCK **/
    $now = new DateTimeImmutable();

    $clock = $this->createMock(ClockInterface::class);
    $clock
      ->expects($this->any())
      ->method('now')
      ->willReturn($now->add(new DateInterval('PT1S')));

    /** RECORD MOCK **/
    $record = $this->createMock(RecordStrategyInterface::class);
    $record
      ->expects($this->never())
      ->method('mark');
    $record
      ->expects($this->once())
      ->method('clear')
      ->with('test.failure');

    /** CACHE ITEM MOCK **/
    // initial state: HALF_OPEN
    $cacheInit = [
      'state' => CircuitStateEnum::HALF_OPEN,
      'record' => $record,
      'updatedAt' => $now
    ];

    // final state: CLOSED
    $cacheSave = [
      'state' => CircuitStateEnum::CLOSED,
      'record' => $record,
      'updatedAt' => $now->add(new DateInterval('PT1S'))
    ];

    $item = $this->createMock(CacheItemInterface::class);
    $item
      ->expects($this->once())
      ->method('isHit')
      ->willReturn(true);
    $item
      ->expects($this->once())
      ->method('get')
      ->willReturn($cacheInit);
    $item
      ->expects($this->once())
      ->method('set')
      ->with($cacheSave);

    /** CACHE POOL MOCK **/
    $cache = $this->createMock(CacheItemPoolInterface::class);
    $cache
      ->expects($this->once())
      ->method('getItem')
      ->with($this->identicalTo('interrupt/test'))
      ->willReturn($item);
    $cache
      ->expects($this->once())
      ->method('save');
    $circuitBreaker = new CountBasedCircuitBreaker($clock, $cache, $record);

    $this->assertSame(CircuitStateEnum::CLOSED, $circuitBreaker->successful('test'));
  }

  /**
   * Given that the Circuit state is CLOSED, once a failure is recorded and triggers the count threshold, the Circuit
   * state must be set to OPEN.
   */
  public function testClosedToOpen(): void {
    /** CLOCK MOCK **/
    $now = new DateTimeImmutable();

    $clock = $this->createMock(ClockInterface::class);
    $clock
      ->expects($this->any())
      ->method('now')
      ->willReturn($now->add(new DateInterval('PT1S')));

    /** RECORD MOCK **/
    $record = $this->createMock(RecordStrategyInterface::class);
    $record
      ->expects($this->once())
      ->method('mark')
      ->with($this->identicalTo('test.failure'))
      ->willReturn(2); // will trigger failureCount threshold

    /** CACHE ITEM MOCK **/
    // initial state: CLOSED
    $cacheInit = [
      'state' => CircuitStateEnum::CLOSED,
      'record' => $record,
      'updatedAt' => $now
    ];

    // final state: OPEN
    $cacheSave = [
      'state' => CircuitStateEnum::OPEN,
      'record' => $record,
      'updatedAt' => $now->add(new DateInterval('PT1S'))
    ];

    $item = $this->createMock(CacheItemInterface::class);
    $item
      ->expects($this->once())
      ->method('isHit')
      ->willReturn(true);
    $item
      ->expects($this->once())
      ->method('get')
      ->willReturn($cacheInit);
    $item
      ->expects($this->once())
      ->method('set')
      ->with($cacheSave);

    /** CACHE POOL MOCK **/
    $cache = $this->createMock(CacheItemPoolInterface::class);
    $cache
      ->expects($this->once())
      ->method('getItem')
      ->with($this->identicalTo('interrupt/test'))
      ->willReturn($item);
    $cache
      ->expects($this->once())
      ->method('save');

    /** ACTUAL TEST **/
    $circuitBreaker = new CountBasedCircuitBreaker($clock, $cache, $record, failureCount: 2);

    $this->assertEquals(CircuitStateEnum::OPEN, $circuitBreaker->failed('test'));
  }

  /**
   * Given that the Circuit state is HALF_OPEN, once a failure is recorded, the Circuit state must be set to OPEN.
   * Note that the threshold is ignored as the current state (HALF_OPEN) has higher precedence.
   */
  public function testHalfOpenToOpen(): void {
    /** CLOCK MOCK **/
    $now = new DateTimeImmutable();

    $clock = $this->createMock(ClockInterface::class);
    $clock
      ->expects($this->any())
      ->method('now')
      ->willReturn($now->add(new DateInterval('PT1S')));

    /** RECORD MOCK **/
    $record = $this->createMock(RecordStrategyInterface::class);
    $record
      ->expects($this->once())
      ->method('mark')
      ->with('test.failure')
      ->willReturn(1); // this could be any value as HALF_OPEN + failure = OPEN

    /** CACHE ITEM MOCK **/
    // initial state: HALF_OPEN
    $cacheInit = [
      'state' => CircuitStateEnum::HALF_OPEN,
      'record' => $record,
      'updatedAt' => $now
    ];

    // final state: OPEN
    $cacheSave = [
      'state' => CircuitStateEnum::OPEN,
      'record' => $record,
      'updatedAt' => $now->add(new DateInterval('PT1S'))
    ];

    $item = $this->createMock(CacheItemInterface::class);
    $item
      ->expects($this->once())
      ->method('isHit')
      ->willReturn(true);
    $item
      ->expects($this->once())
      ->method('get')
      ->willReturn($cacheInit);
    $item
      ->expects($this->once())
      ->method('set')
      ->with($cacheSave);

    /** CACHE POOL MOCK **/
    $cache = $this->createMock(CacheItemPoolInterface::class);
    $cache
      ->expects($this->once())
      ->method('getItem')
      ->with($this->identicalTo('interrupt/test'))
      ->willReturn($item);
    $cache
      ->expects($this->once())
      ->method('save');

    /** ACTUAL TEST **/
    $circuitBreaker = new CountBasedCircuitBreaker($clock, $cache, $record);

    $this->assertEquals(CircuitStateEnum::OPEN, $circuitBreaker->failed('test'));
  }
}
