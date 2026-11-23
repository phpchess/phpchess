<?php

declare(strict_types=1);

namespace PhpChess\Kernel;

use PhpChess\Domain\System\FeatureSwitches;
use PhpChess\Http\Api\V1\StatusController;
use PhpChess\Http\Request;
use PhpChess\Http\Response;
use PhpChess\Http\Web\AccountController;
use PhpChess\Http\Web\Admin\AdminPages;
use PhpChess\Http\Web\Admin\AuditController;
use PhpChess\Http\Web\Admin\DashboardController;
use PhpChess\Http\Web\Admin\SettingsController;
use PhpChess\Http\Web\Admin\UsersController;
use PhpChess\Http\Web\HomeController;
use PhpChess\Http\Web\PreferencesController;
use PhpChess\Http\Web\SkinController;
use PhpChess\Install\InstallerController;

/**
 * Every address of the site, in one list (ARCHITECTURE.md §5).
 *
 * To find the code behind a URL, look it up here: each line names the
 * controller method that answers it, who may open it (Access, K14) and, where
 * it has one, its feature switch (K8). GET routes only read; POST routes
 * change something and need a CSRF token (K7). API routes live under /api/v1/.
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
    public static function addApplication(Router $router, Services $services): void
    {
        $home = new HomeController($services);
        $account = new AccountController($services);
        $preferences = new PreferencesController($services);
        $skin = new SkinController($services);
        $adminPages = new AdminPages($services);
        $dashboard = new DashboardController($services, $adminPages);
        $users = new UsersController($services, $adminPages);
        $settings = new SettingsController($services, $adminPages);
        $audit = new AuditController($services, $adminPages);

        // --- Pages for everyone ---
        $router->get('/', fn (Request $request): Response => $home->home($request));
        $router->get('/skin/theme.css', fn (Request $request): Response => $skin->themeCss($request));
        $router->get('/skin/logo', fn (Request $request): Response => $skin->logo($request));

        // --- Accounts (ID-01 to ID-06) ---
        $registration = FeatureSwitches::PUBLIC_REGISTRATION;
        $router->get('/register', fn (Request $request): Response => $account->showRegistration($request), Access::EVERYONE, $registration);
        $router->post('/register', fn (Request $request): Response => $account->register($request), Access::EVERYONE, $registration);
        $router->get('/verify-email', fn (Request $request): Response => $account->showVerification($request));
        $router->post('/verify-email', fn (Request $request): Response => $account->verify($request));
        $router->post('/verify-email/send', fn (Request $request): Response => $account->resendVerification($request));
        $router->get('/login', fn (Request $request): Response => $account->showLogin($request));
        $router->post('/login', fn (Request $request): Response => $account->logIn($request));
        $router->post('/logout', fn (Request $request): Response => $account->logOut($request), Access::MEMBERS);
        $router->get('/forgot-password', fn (Request $request): Response => $account->showForgotPassword($request));
        $router->post('/forgot-password', fn (Request $request): Response => $account->requestPasswordReset($request));
        $router->get('/reset-password', fn (Request $request): Response => $account->showPasswordReset($request));
        $router->post('/reset-password', fn (Request $request): Response => $account->resetPassword($request));

        // --- The member's own settings (ID-13) ---
        $router->get('/account/preferences', fn (Request $request): Response => $preferences->showPreferences($request), Access::MEMBERS);
        $router->post('/account/preferences', fn (Request $request): Response => $preferences->savePreferences($request), Access::MEMBERS);
        $router->get('/account/password', fn (Request $request): Response => $preferences->showPasswordForm($request), Access::MEMBERS);
        $router->post('/account/password', fn (Request $request): Response => $preferences->changePassword($request), Access::MEMBERS);

        // --- Admin (ADM-01 to ADM-03, SCH-01, INS-06); the password is confirmed first (ID-08) ---
        $router->get('/admin/confirm', fn (Request $request): Response => $dashboard->showConfirm($request), Access::ADMINS_BEFORE_CONFIRMATION);
        $router->post('/admin/confirm', fn (Request $request): Response => $dashboard->confirm($request), Access::ADMINS_BEFORE_CONFIRMATION);
        $router->get('/admin', fn (Request $request): Response => $dashboard->dashboard($request), Access::ADMINS);
        $router->post('/admin/setup-token', fn (Request $request): Response => $dashboard->createSetupToken($request), Access::ADMINS);

        $router->get('/admin/users', fn (Request $request): Response => $users->list($request), Access::ADMINS);
        $router->post('/admin/users/{id}/approve', fn (Request $request, array $parts): Response => $users->approve($request, $parts), Access::ADMINS);
        $router->post('/admin/users/{id}/state', fn (Request $request, array $parts): Response => $users->changeState($request, $parts), Access::ADMINS);
        $router->post('/admin/users/{id}/role', fn (Request $request, array $parts): Response => $users->changeRole($request, $parts), Access::ADMINS);
        $router->post('/admin/users/{id}/rename', fn (Request $request, array $parts): Response => $users->rename($request, $parts), Access::ADMINS);

        $router->get('/admin/settings', fn (Request $request): Response => $settings->showSettings($request), Access::ADMINS);
        $router->post('/admin/settings', fn (Request $request): Response => $settings->saveSettings($request), Access::ADMINS);
        $router->get('/admin/mail', fn (Request $request): Response => $settings->showMail($request), Access::ADMINS);
        $router->post('/admin/mail', fn (Request $request): Response => $settings->saveMail($request), Access::ADMINS);
        $router->post('/admin/mail/test', fn (Request $request): Response => $settings->sendTestMail($request), Access::ADMINS);
        $router->get('/admin/switches', fn (Request $request): Response => $settings->showSwitches($request), Access::ADMINS);
        $router->post('/admin/switches', fn (Request $request): Response => $settings->saveSwitches($request), Access::ADMINS);
        $router->get('/admin/audit', fn (Request $request): Response => $audit->list($request), Access::ADMINS);

        // --- API v1 (API-01) ---
        $apiStatus = new StatusController();
        $router->get('/api/v1/status', fn (Request $request): Response => $apiStatus->status($request));
    }
}
