<?php

declare(strict_types=1);

namespace PhpChess\Kernel;

use PhpChess\Http\Api\V1\StatusController;
use PhpChess\Http\Request;
use PhpChess\Http\Response;
use PhpChess\Http\Web\HomeController;
use PhpChess\Install\InstallerController;

/**
 * Every address of the site, in one list (ARCHITECTURE.md §5).
 *
 * To find the code behind a URL, look it up here: each line names the
 * controller method that answers it. GET routes only read; POST routes change
 * something and need a CSRF token (K7). API routes live under /api/v1/.
 */
final class Routes
{
    /** The installer's addresses (INS-02 to INS-06). */
    public static function addInstaller(Router $router, InstallerController $installer): void
    {
        $router->get('/install', fn (Request $request): Response => $installer->showStep($request));
        $router->post('/install/preflight', fn (Request $request): Response => $installer->confirmPreflight($request));
        $router->post('/install/protection', fn (Request $request): Response => $installer->runProtectionTest($request));
        $router->post('/install/database', fn (Request $request): Response => $installer->saveDatabase($request));
        $router->post('/install/admin', fn (Request $request): Response => $installer->saveAdmin($request));
        $router->post('/install/migrations', fn (Request $request): Response => $installer->runMigrations($request));
        $router->post('/install/finish', fn (Request $request): Response => $installer->finish($request));
    }

    /** The application's addresses, once installed. Later chunks add theirs here. */
    public static function addApplication(Router $router, HomeController $home, StatusController $apiStatus): void
    {
        // --- Pages ---
        $router->get('/', fn (Request $request): Response => $home->home($request));

        // --- API v1 (API-01) ---
        $router->get('/api/v1/status', fn (Request $request): Response => $apiStatus->status($request));
    }
}
