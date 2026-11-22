<?php

declare(strict_types=1);

namespace PhpChess\Kernel;

use Closure;
use PhpChess\Http\Request;
use PhpChess\Http\Response;

/**
 * The router: finds the route for a request's method and path (ARCHITECTURE.md §5).
 *
 * Routes are listed in one place, Routes.php, so every address of the site can
 * be found there by name. The router itself only matches; checking CSRF and
 * turning a miss into an error page is the Kernel's job.
 *
 * Contract K7: only GET and POST are known. A path registered for POST answers
 * "405 Method Not Allowed" to GET, so a link or an image tag can never change
 * anything. HEAD is answered like GET (the body is dropped by the web server).
 */
final class Router
{
    /** @var list<Route> */
    private array $routes = [];

    // --- Registering routes ---

    /**
     * Adds a route that only reads.
     *
     * @param Closure(Request, array<string, string>): Response $handler
     */
    public function get(string $pattern, Closure $handler): void
    {
        $this->routes[] = new Route('GET', $pattern, $handler);
    }

    /**
     * Adds a route that changes something (K7: POST with a CSRF token).
     *
     * @param Closure(Request, array<string, string>): Response $handler
     */
    public function post(string $pattern, Closure $handler): void
    {
        $this->routes[] = new Route('POST', $pattern, $handler);
    }

    // --- Matching a request ---

    /**
     * Finds the route for the request.
     *
     * Returns FOUND with the route, METHOD_NOT_ALLOWED when the path exists
     * only for another method, or NOT_FOUND.
     */
    public function match(Request $request): RouteMatch
    {
        $method = $request->method === 'HEAD' ? 'GET' : $request->method;
        $allowedMethods = [];

        foreach ($this->routes as $route) {
            $pathParts = $route->matchPath($request->path);
            if ($pathParts === null) {
                continue;
            }

            if ($route->method === $method) {
                return new RouteMatch(RouteMatch::FOUND, $route, $pathParts);
            }

            $allowedMethods[] = $route->method;
        }

        if ($allowedMethods !== []) {
            return new RouteMatch(RouteMatch::METHOD_NOT_ALLOWED, null, [], array_values(array_unique($allowedMethods)));
        }

        return new RouteMatch(RouteMatch::NOT_FOUND);
    }
}
