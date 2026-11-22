<?php

declare(strict_types=1);

namespace PhpChess\Kernel;

use Closure;
use PhpChess\Http\Request;
use PhpChess\Http\Response;

/**
 * One route: an HTTP method, a path pattern and the code that answers it.
 *
 * Path patterns are plain paths with named parts in braces, for example
 * "/game/{id}". A named part matches one path segment (no "/").
 *
 * Only GET and POST exist (contract K7): GET reads, POST changes. A route that
 * changes state is registered as POST, so the router refuses it on GET.
 */
final class Route
{
    /**
     * @param 'GET'|'POST' $method
     * @param Closure(Request, array<string, string>): Response $handler
     *        receives the request and the named path parts, returns the response
     */
    public function __construct(
        public readonly string $method,
        public readonly string $pattern,
        public readonly Closure $handler,
    ) {
    }

    /**
     * Compares a path with this route's pattern.
     *
     * @return array<string, string>|null the named parts when it matches, null when not
     */
    public function matchPath(string $path): ?array
    {
        $patternParts = explode('/', trim($this->pattern, '/'));
        $pathParts = explode('/', trim($path, '/'));

        if (count($patternParts) !== count($pathParts)) {
            return null;
        }

        $namedParts = [];
        foreach ($patternParts as $index => $patternPart) {
            $pathPart = $pathParts[$index];

            $isNamedPart = str_starts_with($patternPart, '{') && str_ends_with($patternPart, '}');
            if ($isNamedPart) {
                if ($pathPart === '') {
                    return null;
                }
                $namedParts[substr($patternPart, 1, -1)] = rawurldecode($pathPart);
                continue;
            }

            if ($patternPart !== $pathPart) {
                return null;
            }
        }

        return $namedParts;
    }
}
