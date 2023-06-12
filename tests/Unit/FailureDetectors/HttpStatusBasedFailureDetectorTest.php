<?php
declare(strict_types = 1);

namespace Interrupt\Test\Unit\FailureDetectors;

use Interrupt\FailureDetectors\HttpStatusBasedFailureDetector;
use Iterator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Teapot\StatusCode\All;

#[CoversClass(HttpStatusBasedFailureDetector::class)]
final class HttpStatusBasedFailureDetectorTest extends TestCase {
  private HttpStatusBasedFailureDetector $failureDetector;

  protected function setUp(): void {
    $this->failureDetector = new HttpStatusBasedFailureDetector();
  }

  #[DataProvider('isFailureDataProvider')]
  public function testIsFailure(int $statusCode, bool $expectedResult): void {
    $responseMock = $this->createMock(ResponseInterface::class);
    $responseMock->method('getStatusCode')->willReturn($statusCode);

    $this->assertSame(
      $expectedResult,
      $this->failureDetector->isFailure($responseMock)
    );
  }

  public static function isFailureDataProvider(): Iterator {
    yield [All::INTERNAL_SERVER_ERROR, true];
    yield [All::NOT_IMPLEMENTED, true];
    yield [All::BAD_GATEWAY, true];
    yield [All::OK, false];
  }
}
