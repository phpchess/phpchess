<?php

declare(strict_types=1);

namespace PhpChess\Infra\Notify;

use Psr\Http\Client\ClientExceptionInterface;
use RuntimeException;

/**
 * Thrown by CurlHttpClient when the push service can't be reached (N-03).
 * web-push catches it and reports the push as failed; it is tried again later.
 */
final class CurlRequestFailed extends RuntimeException implements ClientExceptionInterface
{
}
