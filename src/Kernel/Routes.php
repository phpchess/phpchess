<?php

declare(strict_types=1);

namespace PhpChess\Kernel;

use PhpChess\Domain\System\FeatureSwitches;
use PhpChess\Http\Api\V1\GamesController as ApiGamesController;
use PhpChess\Http\Api\V1\StatusController;
use PhpChess\Http\Request;
use PhpChess\Http\Response;
use PhpChess\Http\Web\AccountController;
use PhpChess\Http\Web\Admin\AdminPages;
use PhpChess\Http\Web\Admin\AuditController;
use PhpChess\Http\Web\Admin\DashboardController;
use PhpChess\Http\Web\Admin\GamesController as AdminGamesController;
use PhpChess\Http\Web\Admin\SettingsController;
use PhpChess\Http\Web\Admin\UsersController;
use PhpChess\Http\Web\HomeController;
use PhpChess\Http\Web\Play\ChallengeController;
use PhpChess\Http\Web\Play\GameController;
use PhpChess\Http\Web\Play\NoticeController;
use PhpChess\Http\Web\Play\PlayPages;
use PhpChess\Http\Web\Play\RatingController;
use PhpChess\Http\Web\Play\VacationController;
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
        $adminGames = new AdminGamesController($services, $adminPages);
        $playPages = new PlayPages($services);
        $games = new GameController($services, $playPages);
        $challenges = new ChallengeController($services, $playPages);
        $ratings = new RatingController($services, $playPages);
        $notices = new NoticeController($services, $playPages);
        $vacation = new VacationController($services, $playPages);
        $playerList = FeatureSwitches::PLAYER_LIST_VISIBILITY;

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

        // --- Play: challenges, the Play button and invites (G-05, G-20, G-21) ---
        $router->get('/play', fn (Request $request): Response => $challenges->lobby($request), Access::MEMBERS);
        $router->post('/play/quick', fn (Request $request): Response => $challenges->quickPlay($request), Access::MEMBERS);
        $router->post('/challenges', fn (Request $request): Response => $challenges->create($request), Access::MEMBERS);
        $router->post('/challenges/{id}/accept', fn (Request $request, array $parts): Response => $challenges->accept($request, $parts), Access::MEMBERS);
        $router->post('/challenges/{id}/decline', fn (Request $request, array $parts): Response => $challenges->decline($request, $parts), Access::MEMBERS);
        $router->post('/challenges/{id}/cancel', fn (Request $request, array $parts): Response => $challenges->cancel($request, $parts), Access::MEMBERS);
        $router->get('/challenges/{id}/invite', fn (Request $request, array $parts): Response => $challenges->showNewInvite($request, $parts), Access::MEMBERS);
        $router->get('/invite/{token}', fn (Request $request, array $parts): Response => $challenges->showInvite($request, $parts));
        $router->post('/invite/{token}', fn (Request $request, array $parts): Response => $challenges->acceptInvite($request, $parts), Access::MEMBERS);

        // --- Games: board, moves, buttons, archive, watching, PGN (G-02 to G-13, G-22) ---
        $router->get('/games', fn (Request $request): Response => $games->myGames($request), Access::MEMBERS);
        $router->get('/games/watch', fn (Request $request): Response => $games->watchList($request), Access::MEMBERS, $playerList);
        $router->get('/games/{id}', fn (Request $request, array $parts): Response => $games->show($request, $parts), Access::MEMBERS);
        $router->get('/games/{id}/state', fn (Request $request, array $parts): Response => $games->state($request, $parts), Access::MEMBERS);
        $router->get('/games/{id}/pgn', fn (Request $request, array $parts): Response => $games->pgn($request, $parts), Access::MEMBERS);
        $router->post('/games/{id}/move', fn (Request $request, array $parts): Response => $games->move($request, $parts), Access::MEMBERS);
        $router->post('/games/{id}/resign', fn (Request $request, array $parts): Response => $games->resign($request, $parts), Access::MEMBERS);
        $router->post('/games/{id}/draw/offer', fn (Request $request, array $parts): Response => $games->offerDraw($request, $parts), Access::MEMBERS);
        $router->post('/games/{id}/draw/accept', fn (Request $request, array $parts): Response => $games->acceptDraw($request, $parts), Access::MEMBERS);
        $router->post('/games/{id}/draw/decline', fn (Request $request, array $parts): Response => $games->declineDraw($request, $parts), Access::MEMBERS);
        $router->post('/games/{id}/abort', fn (Request $request, array $parts): Response => $games->abort($request, $parts), Access::MEMBERS);
        $router->post('/games/{id}/abort/request', fn (Request $request, array $parts): Response => $games->requestAbort($request, $parts), Access::MEMBERS);
        $router->post('/games/{id}/rematch', fn (Request $request, array $parts): Response => $games->rematch($request, $parts), Access::MEMBERS);

        // --- Ratings (R-03, R-04), notices (N-01), vacation (G-08) ---
        $router->get('/ratings', fn (Request $request): Response => $ratings->list($request), Access::MEMBERS, $playerList);
        $router->get('/players/{id}/ratings', fn (Request $request, array $parts): Response => $ratings->history($request, $parts), Access::MEMBERS, $playerList);
        $router->get('/notices', fn (Request $request): Response => $notices->list($request), Access::MEMBERS);
        $router->post('/notices/read-all', fn (Request $request): Response => $notices->markAllRead($request), Access::MEMBERS);
        $router->get('/account/vacation', fn (Request $request): Response => $vacation->show($request), Access::MEMBERS);
        $router->post('/account/vacation', fn (Request $request): Response => $vacation->take($request), Access::MEMBERS);
        $router->post('/account/vacation/cancel', fn (Request $request): Response => $vacation->cancel($request), Access::MEMBERS);

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
        $router->get('/admin/games', fn (Request $request): Response => $adminGames->list($request), Access::ADMINS);
        $router->post('/admin/games/new', fn (Request $request): Response => $adminGames->create($request), Access::ADMINS);
        $router->post('/admin/games/{id}/adjudicate', fn (Request $request, array $parts): Response => $adminGames->adjudicate($request, $parts), Access::ADMINS);
        $router->get('/admin/play', fn (Request $request): Response => $adminGames->showSettings($request), Access::ADMINS);
        $router->post('/admin/play', fn (Request $request): Response => $adminGames->saveSettings($request), Access::ADMINS);

        // --- API v1: status (API-01) ---
        $apiStatus = new StatusController();
        $router->get('/api/v1/status', fn (Request $request): Response => $apiStatus->status($request));

        // --- API v1: games (API-02) ---
        $apiGames = new ApiGamesController($services);
        $router->get('/api/v1/games', fn (Request $request): Response => $apiGames->list($request), Access::MEMBERS);
        $router->get('/api/v1/games/{id}', fn (Request $request, array $parts): Response => $apiGames->show($request, $parts), Access::MEMBERS);
        $router->post('/api/v1/games/{id}/move', fn (Request $request, array $parts): Response => $apiGames->move($request, $parts), Access::MEMBERS);
    }
}
