<?php

declare(strict_types=1);

namespace PhpChess\Tests\Identity;

use PhpChess\Domain\Identity\User;
use PhpChess\Domain\System\FeatureSwitches;
use PhpChess\Http\Request;
use PhpChess\Kernel\Access;
use PhpChess\Kernel\Config;
use PhpChess\Kernel\Csrf;
use PhpChess\Kernel\Router;
use PhpChess\Kernel\Routes;
use PhpChess\Kernel\Services;
use PhpChess\Kernel\Session;
use PhpChess\Kernel\Route;
use PhpChess\Tests\Support\InstalledSite;
use PhpChess\Tests\Support\TestBrowser;
use PhpChess\Tests\Support\TestDatabase;
use PHPUnit\Framework\TestCase;

/**
 * Tests every route as every kind of visitor (K14, ID-08) and every feature
 * switch when off (K8). The list of routes comes from Routes.php itself, so a
 * new admin page is tested without anyone remembering to add it here.
 */
final class AccessTest extends TestCase
{
    private InstalledSite $site;

    protected function setUp(): void
    {
        $this->site = new InstalledSite($this);
    }

    protected function tearDown(): void
    {
        if (isset($this->site)) {
            $this->site->remove();
        }
    }

    public function testVisitorsAreSentToTheLoginPageFromEveryMembersAndAdminPage(): void
    {
        $browser = $this->site->browser();
        // Every switch on, so no route hides behind its switch's 404 (K8).
        $switches = new FeatureSwitches($this->site->settings());
        foreach (array_keys(FeatureSwitches::ALL) as $switchName) {
            $switches->set($switchName, true);
        }

        foreach ($this->routesWith([Access::MEMBERS, Access::TEACHERS, Access::ADMINS, Access::ADMINS_BEFORE_CONFIRMATION]) as $route) {
            $response = $this->open($browser, $route);
            if (str_starts_with($route->pattern, '/api/')) {
                // The API answers in JSON instead of sending to the login page.
                self::assertSame(401, $response->status, "{$route->method} {$route->pattern}");
                continue;
            }
            self::assertSame(303, $response->status, "{$route->method} {$route->pattern}");
            self::assertStringContainsString('/chess/login?next=', $response->header('Location'), "{$route->method} {$route->pattern}");
        }
    }

    public function testPlayersAndTeachersAreRefusedEveryAdminPage(): void
    {
        foreach ([User::ROLE_PLAYER, User::ROLE_TEACHER, User::ROLE_STUDENT] as $role) {
            $this->site->createUser('someone-' . $role, $role);
            $browser = $this->site->loggedInBrowser('someone-' . $role);

            foreach ($this->routesWith([Access::ADMINS, Access::ADMINS_BEFORE_CONFIRMATION]) as $route) {
                self::assertSame(403, $this->open($browser, $route)->status, "{$role}: {$route->method} {$route->pattern}");
            }
        }
    }

    public function testAnAdminMustConfirmThePasswordFirst(): void
    {
        $browser = $this->site->loggedInBrowser(InstalledSite::ADMIN_NAME);

        foreach ($this->routesWith([Access::ADMINS]) as $route) {
            $response = $this->open($browser, $route);
            self::assertSame(303, $response->status, "{$route->method} {$route->pattern}");
            self::assertStringContainsString('/chess/admin/confirm', $response->header('Location'));
        }
    }

    public function testAWrongConfirmationPasswordIsRefused(): void
    {
        $browser = $this->site->loggedInBrowser(InstalledSite::ADMIN_NAME);

        $response = $browser->submit('/admin/confirm', '/admin/confirm', ['password' => 'wrong', 'next' => '/admin']);

        self::assertSame(422, $response->status);
        self::assertSame(303, $browser->get('/admin')->status, 'still not confirmed');
    }

    public function testAConfirmedAdminOpensEveryAdminPage(): void
    {
        $browser = $this->site->loggedInBrowser(InstalledSite::ADMIN_NAME, true);

        foreach (['/admin', '/admin/users', '/admin/settings', '/admin/mail', '/admin/switches', '/admin/audit'] as $path) {
            self::assertSame(200, $browser->get($path)->status, $path);
        }
    }

    public function testTheConfirmationRunsOutAfterThirtyMinutes(): void
    {
        $browser = $this->site->loggedInBrowser(InstalledSite::ADMIN_NAME, true);
        $browser->session->set('admin_confirmed_at', time() - 1801);

        self::assertSame(303, $browser->get('/admin')->status);
    }

    public function testASwitchedOffFeatureAnswersNotFound(): void
    {
        $routesWithSwitch = array_filter($this->allRoutes(), static fn (Route $route): bool => $route->featureSwitch !== null);
        self::assertNotEmpty($routesWithSwitch);

        foreach ($routesWithSwitch as $route) {
            $switch = (string) $route->featureSwitch;
            self::assertArrayHasKey($switch, FeatureSwitches::ALL, "{$route->pattern} names a known switch");

            $this->site->settings()->set('switch.' . $switch, false);
            self::assertSame(404, $this->open($this->site->browser(), $route)->status, "{$route->method} {$route->pattern} with {$switch} off");

            $this->site->settings()->set('switch.' . $switch, true);
            self::assertNotSame(404, $this->open($this->site->browser(), $route)->status, "{$route->method} {$route->pattern} with {$switch} on");
        }
    }

    // --- Helpers ---

    /** Opens a route: GET directly, POST after loading a page for the CSRF token. */
    private function open(TestBrowser $browser, Route $route): \PhpChess\Http\Response
    {
        $path = str_replace('{id}', '1', $route->pattern);
        if ($route->method === 'GET') {
            return $browser->get($path);
        }
        $browser->get('/login');

        return $browser->post($path);
    }

    /**
     * @param list<string> $accessLevels
     * @return list<Route>
     */
    private function routesWith(array $accessLevels): array
    {
        $routes = array_values(array_filter($this->allRoutes(), static fn (Route $route): bool => in_array($route->access, $accessLevels, true)));
        self::assertNotEmpty($routes);

        return $routes;
    }

    /**
     * @return list<Route>
     */
    private function allRoutes(): array
    {
        $config = TestDatabase::config($this)->withInstallState(true, '');
        $session = Session::inMemory();
        $services = new Services(
            $this->site->folder->path,
            $config,
            new Request('GET', '/'),
            $session,
            new Csrf($session),
            time(),
            static fn (Config $config) => \PhpChess\Infra\Database::connect($config),
        );
        $router = new Router();
        Routes::addApplication($router, $services);

        return $router->allRoutes();
    }
}
