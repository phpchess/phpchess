<?php

declare(strict_types=1);

namespace PhpChess\Tests\Kernel;

use PhpChess\Http\Request;
use PhpChess\Http\Response;
use PhpChess\Kernel\RouteMatch;
use PhpChess\Kernel\Router;
use PHPUnit\Framework\TestCase;

/**
 * Tests the router: matching, named path parts and method refusal (contract K7).
 */
final class RouterTest extends TestCase
{
    private Router $router;

    protected function setUp(): void
    {
        $this->router = new Router();
        $this->router->get('/', fn (): Response => Response::text('home'));
        $this->router->get('/game/{id}', fn (Request $request, array $parts): Response => Response::text('game ' . $parts['id']));
        $this->router->post('/game/{id}/move', fn (): Response => Response::text('moved'));
    }

    public function testFindsAPlainRoute(): void
    {
        $match = $this->router->match(new Request('GET', '/'));

        self::assertSame(RouteMatch::FOUND, $match->result);
    }

    public function testPassesNamedPathParts(): void
    {
        $match = $this->router->match(new Request('GET', '/game/42'));

        self::assertSame(RouteMatch::FOUND, $match->result);
        self::assertSame(['id' => '42'], $match->pathParts);
        self::assertNotNull($match->route);
        self::assertSame('game 42', ($match->route->handler)(new Request('GET', '/game/42'), $match->pathParts)->body);
    }

    public function testUnknownPathIsNotFound(): void
    {
        self::assertSame(RouteMatch::NOT_FOUND, $this->router->match(new Request('GET', '/nothing'))->result);
        self::assertSame(RouteMatch::NOT_FOUND, $this->router->match(new Request('GET', '/game'))->result);
    }

    public function testAChangingRouteRefusesGet(): void
    {
        $match = $this->router->match(new Request('GET', '/game/42/move'));

        self::assertSame(RouteMatch::METHOD_NOT_ALLOWED, $match->result);
        self::assertSame(['POST'], $match->allowedMethods);
    }

    public function testAReadingRouteRefusesPost(): void
    {
        $match = $this->router->match(new Request('POST', '/game/42'));

        self::assertSame(RouteMatch::METHOD_NOT_ALLOWED, $match->result);
        self::assertSame(['GET'], $match->allowedMethods);
    }

    public function testOtherMethodsAreRefused(): void
    {
        foreach (['PUT', 'DELETE', 'PATCH'] as $method) {
            self::assertSame(RouteMatch::METHOD_NOT_ALLOWED, $this->router->match(new Request($method, '/game/1/move'))->result);
        }
    }

    public function testHeadIsAnsweredLikeGet(): void
    {
        self::assertSame(RouteMatch::FOUND, $this->router->match(new Request('HEAD', '/'))->result);
    }
}
