<?php

declare(strict_types=1);

namespace PhpChess\Kernel;

/**
 * What the router found for a request: a route, "no such page" or "wrong method".
 *
 * Used by Router::match() and read by the Kernel, which turns a miss into a
 * 404 or 405 answer (HTML for pages, the API error format for the API).
 */
final class RouteMatch
{
    public const int FOUND = 200;
    public const int NOT_FOUND = 404;
    public const int METHOD_NOT_ALLOWED = 405;

    /**
     * @param self::FOUND|self::NOT_FOUND|self::METHOD_NOT_ALLOWED $result
     * @param array<string, string> $pathParts       the named parts of the path
     * @param list<string>          $allowedMethods  for a 405: the methods that would work
     */
    public function __construct(
        public readonly int $result,
        public readonly ?Route $route = null,
        public readonly array $pathParts = [],
        public readonly array $allowedMethods = [],
    ) {
    }
}
