<?php
declare(strict_types = 1);

namespace Interrupt\FailureDetectors;

use Interrupt\Contracts\FailureDetectorInterface;
use Psr\Http\Message\ResponseInterface;
use Teapot\StatusCode\All;
use Teapot\StatusCode\RFC\RFC2774;
use Teapot\StatusCode\RFC\RFC6585;

/**
 * Detects failure based on HTTP Response Status Code (5xx range)
 */
class HttpStatusBasedFailureDetector implements FailureDetectorInterface {
  /**
   * @var int[]
   */
  protected array $failureCodes;

  /**
   * @link https://github.com/ackintosh/ganesha/blob/master/src/Ganesha/HttpClient/RestFailureDetector.php#L20-L41
   */
  public const FAILURE_CODES = [
    All::INTERNAL_SERVER_ERROR,
    All::NOT_IMPLEMENTED,
    All::BAD_GATEWAY,
    All::SERVICE_UNAVAILABLE,
    All::GATEWAY_TIMEOUT,
    All::HTTP_VERSION_NOT_SUPPORTED,
    All::VARIANT_ALSO_NEGOTIATES,
    All::INSUFFICIENT_STORAGE,
    All::LOOP_DETECTED,
    All::BANDWIDTH_LIMIT_EXCEEDED,
    RFC2774::NOT_EXTENDED,
    RFC6585::NETWORK_AUTHENTICATION_REQUIRED,
    All::ORIGIN_ERROR,
    All::ORIGIN_DECLINED_REQUEST,
    All::CONNECTION_TIMED_OUT,
    All::PROXY_DECLINED_REQUEST,
    All::TIMEOUT_OCCURRED,
    525, // SSL Handshake Failed
    526, // Invalid SSL Certificate
    527  // Railgun Error
  ];

  /**
   * @param int[] $failureCodes
   */
  public function __construct(array $failureCodes = []) {
    $this->failureCodes = $failureCodes ?: self::FAILURE_CODES;
  }

  public function isFailure(ResponseInterface $response): bool {
    return in_array($response->getStatusCode(), $this->failureCodes, true);
  }
}
