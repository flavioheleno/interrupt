<?php
declare(strict_types = 1);

namespace Interrupt\Test\Unit\RecordStrategies;

use DateInterval;
use DateTimeImmutable;
use Interrupt\RecordStrategies\FixedTimeWindowBasedRecordStrategy;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;

#[CoversClass(FixedTimeWindowBasedRecordStrategy::class)]
final class FixedTimeWindowBasedRecordStrategyTest extends TestCase {
  public function testMark(): void {
    $clock = $this->createMock(ClockInterface::class);
    $strategy = new FixedTimeWindowBasedRecordStrategy($clock);

    $dateTime = new DateTimeImmutable();
    $clock->method('now')
      ->willReturn(
        $dateTime,
        $dateTime->add(new DateInterval('PT1S')),
        $dateTime->add(new DateInterval('PT5S')),
        $dateTime->add($strategy->getWindowSize()),
        $dateTime->add($strategy->getWindowSize())->add(new DateInterval('PT1S'))
      );

    $this->assertSame(1, $strategy->mark('key')); // A at T0 = {A}
    $this->assertSame(2, $strategy->mark('key')); // B at T0 + 1s = {A, B}
    $this->assertSame(3, $strategy->mark('key')); // C at T0 + 5s = {A, B, C}
    $this->assertSame(1, $strategy->mark('key')); // D at T0 + Window Size = {D}
    $this->assertSame(2, $strategy->mark('key')); // E at T0 + Window Size + 1s = {D, E}
  }

  public function testClear(): void {
    $clock = $this->createMock(ClockInterface::class);
    $strategy = new FixedTimeWindowBasedRecordStrategy($clock);

    $dateTime = new DateTimeImmutable();
    $clock->method('now')
      ->willReturn(
        $dateTime,
        $dateTime->add(new DateInterval('PT1S')),
        $dateTime->add(new DateInterval('PT2S')),
        $dateTime->add(new DateInterval('PT3S'))
      );

    $this->assertSame(1, $strategy->mark('key'));
    $this->assertSame(2, $strategy->mark('key'));
    $this->assertSame(3, $strategy->mark('key'));
    $strategy->clear('key');
    $this->assertSame(1, $strategy->mark('key'));
  }

  public function testSerialization(): void {
    $clock = $this->createMock(ClockInterface::class);
    $strategy = new FixedTimeWindowBasedRecordStrategy($clock);

    $this->assertEquals($strategy, unserialize(serialize($strategy)));

    $strategy->mark('key');

    $this->assertEquals($strategy, unserialize(serialize($strategy)));
  }
}
