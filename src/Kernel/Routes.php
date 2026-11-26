<?php

declare(strict_types=1);

namespace PhpChess\Kernel;

use PhpChess\Domain\System\FeatureSwitches;
use PhpChess\Http\Api\V1\GamesController as ApiGamesController;
use PhpChess\Http\Api\V1\MembersController as ApiMembersController;
use PhpChess\Http\Api\V1\StatusController;
use PhpChess\Http\Request;
use PhpChess\Http\Response;
use PhpChess\Http\Web\Account\DevicesController;
use PhpChess\Http\Web\Account\NotificationsController;
use PhpChess\Http\Web\Account\OwnDataController;
use PhpChess\Http\Web\AccountController;
use PhpChess\Http\Web\Admin\ArchiveController;
use PhpChess\Http\Web\Admin\CommunityAdminController;
use PhpChess\Http\Web\AppController;
use PhpChess\Http\Web\Community\MessagesController;
use PhpChess\Http\Web\Community\PlayersController;
use PhpChess\Http\Web\ContentController;
use PhpChess\Http\Web\MemberPages;
use PhpChess\Http\Web\School\SchoolController;
use PhpChess\Http\Web\Admin\AdminPages;
use PhpChess\Http\Web\Admin\AuditController;
use PhpChess\Http\Web\Admin\DashboardController;
use PhpChess\Http\Web\Admin\GamesController as AdminGamesController;
use PhpChess\Http\Web\Admin\SettingsController;
use PhpChess\Http\Web\Admin\UsersController;
use PhpChess\Http\Web\HomeController;
use PhpChess\Http\Web\Play\ChallengeController;
use PhpChess\Http\Web\Play\ComputerController;
use PhpChess\Http\Web\Admin\EngineController as AdminEngineController;
use PhpChess\Http\Web\Play\GameController;
use PhpChess\Http\Web\Play\PlayPages;
use PhpChess\Http\Web\Play\RatingController;
use PhpChess\Http\Web\Play\VacationController;
use PhpChess\Http\Web\PreferencesController;
use PhpChess\Http\Web\SiteNewsController;
use PhpChess\Http\Web\StrengthTestController;
use PhpChess\Http\Web\LearnController;
use PhpChess\Http\Web\LichessPagesController;
use PhpChess\Http\Web\PuzzlesController;
use PhpChess\Http\Web\Admin\LichessController as AdminLichessController;
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
        $strengthTest = new StrengthTestController($services);
        $siteNews = new SiteNewsController($services);
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
        $vacation = new VacationController($services, $playPages);
        $computer = new ComputerController($services, $playPages);
        $adminEngine = new AdminEngineController($services, $adminPages);

        // --- Play the computer (C3-2: AI-03, AI-06, AI-07) ---
        $router->get('/play/computer', fn (Request $request): Response => $computer->list($request), Access::MEMBERS);
        $router->post('/play/computer', fn (Request $request): Response => $computer->start($request), Access::MEMBERS);
        $router->post('/games/{id}/computer-move', fn (Request $request, array $parts): Response => $games->computerMove($request, $parts), Access::MEMBERS);
        $router->get('/admin/engine', fn (Request $request): Response => $adminEngine->show($request), Access::ADMINS);
        $router->post('/admin/engine', fn (Request $request): Response => $adminEngine->save($request), Access::ADMINS);
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

        // --- The site-wide news poll (B21) ---
        $router->get('/news', fn (Request $request): Response => $siteNews->news($request), Access::MEMBERS);

        // --- The strength test (B15) ---
        $router->get('/strength-test', fn (Request $request): Response => $strengthTest->show($request), Access::MEMBERS);
        $router->post('/strength-test/start', fn (Request $request): Response => $strengthTest->start($request), Access::MEMBERS);
        $router->post('/strength-test/answer', fn (Request $request): Response => $strengthTest->answer($request), Access::MEMBERS);

        // --- Learn: puzzles, study games, Lichess TV (C5-1: L-01, L-03; A74-A88) ---
        // The bundled puzzles and the study themes are local: no switch.
        // Everything that asks lichess.org needs "external content" (K8, K9).
        $puzzles = new PuzzlesController($services);
        $learn = new LearnController($services, new MemberPages($services));
        $lichessSwitch = FeatureSwitches::EXTERNAL_CONTENT;
        $router->get('/learn', fn (Request $request): Response => $learn->hub($request), Access::MEMBERS);
        $router->get('/learn/puzzles', fn (Request $request): Response => $puzzles->show($request), Access::MEMBERS);
        $router->post('/learn/puzzles/next', fn (Request $request): Response => $puzzles->next($request), Access::MEMBERS);
        $router->get('/learn/themes/{id}', fn (Request $request, array $parts): Response => $learn->theme($request, $parts), Access::MEMBERS);
        $router->get('/learn/games/{id}', fn (Request $request, array $parts): Response => $learn->game($request, $parts), Access::MEMBERS);
        $router->post('/learn/games/{id}/remove', fn (Request $request, array $parts): Response => $learn->removeOwnGame($request, $parts), Access::MEMBERS);
        $router->post('/learn/games/{id}/keep', fn (Request $request, array $parts): Response => $learn->keepOwnGame($request, $parts), Access::MEMBERS);
        $router->post('/learn/import', fn (Request $request): Response => $learn->import($request), Access::MEMBERS, $lichessSwitch);
        // The Lichess page (A96); each part also checks the admin's choice.
        $lichessPages = new LichessPagesController($services, new MemberPages($services));
        $router->get('/learn/lichess', fn (Request $request): Response => $lichessPages->show($request), Access::MEMBERS, $lichessSwitch);
        $router->get('/learn/lichess/daily', fn (Request $request): Response => $lichessPages->daily($request), Access::MEMBERS, $lichessSwitch);
        $router->get('/learn/lichess/broadcast/{round}', fn (Request $request, array $parts): Response => $lichessPages->broadcastRound($request, $parts), Access::MEMBERS, $lichessSwitch);
        $router->get('/learn/lichess/broadcast/{round}/{n}', fn (Request $request, array $parts): Response => $lichessPages->broadcastGame($request, $parts), Access::MEMBERS, $lichessSwitch);
        $router->post('/learn/paste', fn (Request $request): Response => $learn->paste($request), Access::MEMBERS);
        $router->get('/learn/tv', fn (Request $request): Response => $learn->tv($request), Access::MEMBERS, $lichessSwitch);
        $router->get('/learn/tv/now', fn (Request $request): Response => $learn->tvNow($request), Access::MEMBERS, $lichessSwitch);

        // --- Admin: Lichess connection, study themes and imports (C5-1: A80, A81, A87) ---
        $adminLichess = new AdminLichessController($services, $adminPages);
        $router->get('/admin/lichess', fn (Request $request): Response => $adminLichess->show($request), Access::ADMINS);
        $router->post('/admin/lichess/test', fn (Request $request): Response => $adminLichess->test($request), Access::ADMINS);
        $router->post('/admin/lichess/proxy', fn (Request $request): Response => $adminLichess->saveProxy($request), Access::ADMINS);
        $router->post('/admin/lichess/content', fn (Request $request): Response => $adminLichess->saveContent($request), Access::ADMINS);
        $router->post('/admin/lichess/themes', fn (Request $request): Response => $adminLichess->createTheme($request), Access::ADMINS);
        $router->post('/admin/lichess/themes/{id}/remove', fn (Request $request, array $parts): Response => $adminLichess->removeTheme($request, $parts), Access::ADMINS);
        $router->post('/admin/lichess/upload', fn (Request $request): Response => $adminLichess->upload($request), Access::ADMINS);
        $router->post('/admin/lichess/paste', fn (Request $request): Response => $adminLichess->paste($request), Access::ADMINS);
        $router->post('/admin/lichess/settings', fn (Request $request): Response => $adminLichess->saveSettings($request), Access::ADMINS);
        $router->post('/admin/lichess/import-game', fn (Request $request): Response => $adminLichess->importGame($request), Access::ADMINS, $lichessSwitch);
        $router->post('/admin/lichess/import-player', fn (Request $request): Response => $adminLichess->importPlayer($request), Access::ADMINS, $lichessSwitch);
        $router->post('/admin/lichess/import-study', fn (Request $request): Response => $adminLichess->importStudy($request), Access::ADMINS, $lichessSwitch);

        // --- Play: challenges, the Play button and invites (G-05, G-20, G-21) ---
        $router->get('/play', fn (Request $request): Response => $challenges->lobby($request), Access::MEMBERS);
        $router->get('/play/news', fn (Request $request): Response => $challenges->news($request), Access::MEMBERS);
        $router->get('/play/boxes', fn (Request $request): Response => $challenges->boxes($request), Access::MEMBERS);
        $router->get('/play/challenge', fn (Request $request): Response => $challenges->newChallengePage($request), Access::MEMBERS);
        $router->post('/play/start', fn (Request $request): Response => $challenges->startNewGame($request), Access::MEMBERS);
        $router->get('/rules', fn (Request $request): Response => $challenges->rules($request), Access::MEMBERS);
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
        $router->get('/games/{id}/parts', fn (Request $request, array $parts): Response => $games->parts($request, $parts), Access::MEMBERS);
        $router->get('/games/{id}/state', fn (Request $request, array $parts): Response => $games->state($request, $parts), Access::MEMBERS);
        $router->get('/games/{id}/pgn', fn (Request $request, array $parts): Response => $games->pgn($request, $parts), Access::MEMBERS);
        $router->post('/games/{id}/move', fn (Request $request, array $parts): Response => $games->move($request, $parts), Access::MEMBERS);
        $router->post('/games/{id}/resign', fn (Request $request, array $parts): Response => $games->resign($request, $parts), Access::MEMBERS);
        $router->post('/games/{id}/draw/offer', fn (Request $request, array $parts): Response => $games->offerDraw($request, $parts), Access::MEMBERS);
        $router->post('/games/{id}/draw/accept', fn (Request $request, array $parts): Response => $games->acceptDraw($request, $parts), Access::MEMBERS);
        $router->post('/games/{id}/draw/decline', fn (Request $request, array $parts): Response => $games->declineDraw($request, $parts), Access::MEMBERS);
        $router->post('/games/{id}/abort', fn (Request $request, array $parts): Response => $games->abort($request, $parts), Access::MEMBERS);
        $router->post('/games/{id}/abort/request', fn (Request $request, array $parts): Response => $games->requestAbort($request, $parts), Access::MEMBERS);
        $router->post('/games/{id}/premove', fn (Request $request, array $parts): Response => $games->premove($request, $parts), Access::MEMBERS);
        $router->post('/games/{id}/premove/clear', fn (Request $request, array $parts): Response => $games->clearPremove($request, $parts), Access::MEMBERS);
        $router->post('/games/{id}/pace/offer', fn (Request $request, array $parts): Response => $games->offerPace($request, $parts), Access::MEMBERS);
        $router->post('/games/{id}/pace/accept', fn (Request $request, array $parts): Response => $games->acceptPace($request, $parts), Access::MEMBERS);
        $router->post('/games/{id}/pace/decline', fn (Request $request, array $parts): Response => $games->declinePace($request, $parts), Access::MEMBERS);
        $router->post('/games/{id}/rematch', fn (Request $request, array $parts): Response => $games->rematch($request, $parts), Access::MEMBERS);
        $router->post('/games/{id}/chat', fn (Request $request, array $parts): Response => $games->chat($request, $parts), Access::MEMBERS, FeatureSwitches::GAME_CHAT);

        // --- Ratings (R-03, R-04), vacation (G-08); notices are kept for admins only (A44) ---
        $router->get('/ratings', fn (Request $request): Response => $ratings->list($request), Access::MEMBERS, $playerList);
        $router->get('/players/{id}/ratings', fn (Request $request, array $parts): Response => $ratings->history($request, $parts), Access::MEMBERS, $playerList);
        $router->get('/account/vacation', fn (Request $request): Response => $vacation->show($request), Access::MEMBERS);
        $router->post('/account/vacation', fn (Request $request): Response => $vacation->take($request), Access::MEMBERS);
        $router->post('/account/vacation/cancel', fn (Request $request): Response => $vacation->cancel($request), Access::MEMBERS);

        self::addCommunityAndSchool($router, $services);

        // --- Admin (ADM-01 to ADM-03, SCH-01, INS-06); the password is confirmed first (ID-08) ---
        $router->get('/admin/confirm', fn (Request $request): Response => $dashboard->showConfirm($request), Access::ADMINS_BEFORE_CONFIRMATION);
        $router->post('/admin/confirm', fn (Request $request): Response => $dashboard->confirm($request), Access::ADMINS_BEFORE_CONFIRMATION);
        $router->get('/admin', fn (Request $request): Response => $dashboard->dashboard($request), Access::ADMINS);
        $router->post('/admin/updates', fn (Request $request): Response => $dashboard->runUpdates($request), Access::ADMINS);

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

        // --- Admin: content, community, newsletter, notices (ADM-05, ADM-07, A44), archive (A42) ---
        $communityAdmin = new CommunityAdminController($services, $adminPages);
        $archive = new ArchiveController($services, $adminPages);
        $router->get('/admin/content', fn (Request $request): Response => $communityAdmin->content($request), Access::ADMINS);
        $router->post('/admin/content', fn (Request $request): Response => $communityAdmin->createContent($request), Access::ADMINS);
        $router->post('/admin/content/{id}', fn (Request $request, array $parts): Response => $communityAdmin->updateContent($request, $parts), Access::ADMINS);
        $router->post('/admin/content/{id}/delete', fn (Request $request, array $parts): Response => $communityAdmin->deleteContent($request, $parts), Access::ADMINS);
        $router->get('/admin/community', fn (Request $request): Response => $communityAdmin->settings($request), Access::ADMINS);
        $router->post('/admin/community', fn (Request $request): Response => $communityAdmin->saveSettings($request), Access::ADMINS);
        $router->get('/admin/newsletter', fn (Request $request): Response => $communityAdmin->newsletter($request), Access::ADMINS);
        $router->post('/admin/newsletter', fn (Request $request): Response => $communityAdmin->sendNewsletter($request), Access::ADMINS);
        $router->get('/admin/notices', fn (Request $request): Response => $communityAdmin->notices($request), Access::ADMINS);
        $router->get('/admin/archive', fn (Request $request): Response => $archive->show($request), Access::ADMINS);
        $router->post('/admin/archive/check', fn (Request $request): Response => $archive->check($request), Access::ADMINS);
        $router->post('/admin/archive/write', fn (Request $request): Response => $archive->write($request), Access::ADMINS);
        $router->get('/admin/archive/download', fn (Request $request): Response => $archive->download($request), Access::ADMINS);
        $router->post('/admin/archive/remove', fn (Request $request): Response => $archive->remove($request), Access::ADMINS);
        $router->post('/admin/archive/restore', fn (Request $request): Response => $archive->restore($request), Access::ADMINS);

        // --- API v1: status (API-01) ---
        $apiStatus = new StatusController();
        $router->get('/api/v1/status', fn (Request $request): Response => $apiStatus->status($request));

        // --- API v1: games (API-02) ---
        $apiGames = new ApiGamesController($services);
        $router->get('/api/v1/games', fn (Request $request): Response => $apiGames->list($request), Access::MEMBERS);
        $router->get('/api/v1/games/{id}', fn (Request $request, array $parts): Response => $apiGames->show($request, $parts), Access::MEMBERS);
        $router->post('/api/v1/games/{id}/move', fn (Request $request, array $parts): Response => $apiGames->move($request, $parts), Access::MEMBERS);

        // --- API v1: the member, challenges, messages, players (API-01) ---
        $api = new ApiMembersController($services);
        $messagesSwitch = FeatureSwitches::DIRECT_MESSAGES;
        $router->get('/api/v1/me', fn (Request $request): Response => $api->me($request), Access::MEMBERS);
        $router->get('/api/v1/challenges', fn (Request $request): Response => $api->challenges($request), Access::MEMBERS);
        $router->post('/api/v1/challenges/{id}/accept', fn (Request $request, array $parts): Response => $api->acceptChallenge($request, $parts), Access::MEMBERS);
        $router->get('/api/v1/messages', fn (Request $request): Response => $api->conversations($request), Access::MEMBERS, $messagesSwitch);
        $router->get('/api/v1/messages/{name}', fn (Request $request, array $parts): Response => $api->conversation($request, $parts), Access::MEMBERS, $messagesSwitch);
        $router->post('/api/v1/messages/{name}', fn (Request $request, array $parts): Response => $api->send($request, $parts), Access::MEMBERS, $messagesSwitch);
        $router->get('/api/v1/buddies', fn (Request $request): Response => $api->buddies($request), Access::MEMBERS);
        $router->get('/api/v1/players/online', fn (Request $request): Response => $api->online($request), Access::MEMBERS, $playerList);
    }

    /** Community, content, own data, devices, notifications, the app and school mode (C2-2). */
    private static function addCommunityAndSchool(Router $router, Services $services): void
    {
        $memberPages = new MemberPages($services);
        $content = new ContentController($services);
        $app = new AppController($services);
        $players = new PlayersController($services, $memberPages);
        $messages = new MessagesController($services, $memberPages);
        $ownData = new OwnDataController($services, $memberPages);
        $devices = new DevicesController($services, $memberPages);
        $notifications = new NotificationsController($services, $memberPages);
        $school = new SchoolController($services, $memberPages);
        $playerList = FeatureSwitches::PLAYER_LIST_VISIBILITY;
        $messagesSwitch = FeatureSwitches::DIRECT_MESSAGES;
        $uploads = FeatureSwitches::FILE_UPLOADS;
        $push = FeatureSwitches::PUSH_NOTIFICATIONS;
        $schoolMode = FeatureSwitches::SCHOOL_MODE;

        // --- Content for everyone (C-06, C-09) and the installable app (UI-05, UI-08) ---
        $router->get('/faq', fn (Request $request): Response => $content->faq($request));
        $router->get('/news', fn (Request $request): Response => $content->news($request));
        $router->get('/pages/{slug}', fn (Request $request, array $parts): Response => $content->page($request, $parts));
        $router->get('/manifest.webmanifest', fn (Request $request): Response => $app->manifest($request));
        $router->get('/sw.js', fn (Request $request): Response => $app->serviceWorker($request));
        $router->get('/offline', fn (Request $request): Response => $app->offline($request));

        // --- Players, profiles, buddies, avatars (C-01 to C-03, C-10) ---
        $router->get('/players', fn (Request $request): Response => $players->list($request), Access::MEMBERS);
        $router->get('/players/{name}', fn (Request $request, array $parts): Response => $players->profile($request, $parts));
        $router->post('/players/{name}/buddy', fn (Request $request, array $parts): Response => $players->addBuddy($request, $parts), Access::MEMBERS);
        $router->post('/players/{name}/unbuddy', fn (Request $request, array $parts): Response => $players->removeBuddy($request, $parts), Access::MEMBERS);
        $router->get('/account/profile', fn (Request $request): Response => $players->editProfile($request), Access::MEMBERS);
        $router->post('/account/profile', fn (Request $request): Response => $players->saveProfile($request), Access::MEMBERS);
        $router->post('/account/avatar', fn (Request $request): Response => $players->chooseAvatar($request), Access::MEMBERS);
        $router->post('/account/avatar/upload', fn (Request $request): Response => $players->uploadAvatar($request), Access::MEMBERS, $uploads);
        $router->get('/avatars/{id}', fn (Request $request, array $parts): Response => $players->avatarImage($request, $parts));

        // --- Direct messages (C-04) ---
        $router->get('/messages', fn (Request $request): Response => $messages->inbox($request), Access::MEMBERS, $messagesSwitch);
        $router->get('/messages/{name}', fn (Request $request, array $parts): Response => $messages->conversation($request, $parts), Access::MEMBERS, $messagesSwitch);
        $router->get('/messages/{name}/new', fn (Request $request, array $parts): Response => $messages->newMessages($request, $parts), Access::MEMBERS, $messagesSwitch);
        $router->post('/messages/{name}', fn (Request $request, array $parts): Response => $messages->send($request, $parts), Access::MEMBERS, $messagesSwitch);

        // --- My data, devices and notifications (ID-10, ID-11, N-03, N-04) ---
        $router->get('/account/data', fn (Request $request): Response => $ownData->show($request), Access::MEMBERS);
        $router->get('/account/data/export', fn (Request $request): Response => $ownData->export($request), Access::MEMBERS);
        $router->post('/account/delete', fn (Request $request): Response => $ownData->delete($request), Access::MEMBERS);
        $router->get('/account/devices', fn (Request $request): Response => $devices->list($request), Access::MEMBERS);
        $router->post('/account/devices', fn (Request $request): Response => $devices->create($request), Access::MEMBERS);
        $router->post('/account/devices/{id}/revoke', fn (Request $request, array $parts): Response => $devices->revoke($request, $parts), Access::MEMBERS);
        $router->get('/account/notifications', fn (Request $request): Response => $notifications->show($request), Access::MEMBERS);
        $router->post('/account/notifications', fn (Request $request): Response => $notifications->save($request), Access::MEMBERS);
        $router->post('/account/push/subscribe', fn (Request $request): Response => $notifications->subscribe($request), Access::MEMBERS, $push);
        $router->post('/account/push/{id}/remove', fn (Request $request, array $parts): Response => $notifications->remove($request, $parts), Access::MEMBERS, $push);

        // --- School mode (SCH-02 to SCH-06); the login cards are for everyone ---
        $router->get('/school', fn (Request $request): Response => $school->classes($request), Access::TEACHERS, $schoolMode);
        $router->post('/school/classes', fn (Request $request): Response => $school->createClass($request), Access::TEACHERS, $schoolMode);
        $router->get('/school/classes/{id}', fn (Request $request, array $parts): Response => $school->showClass($request, $parts), Access::TEACHERS, $schoolMode);
        $router->post('/school/classes/{id}/students', fn (Request $request, array $parts): Response => $school->addStudents($request, $parts), Access::TEACHERS, $schoolMode);
        $router->post('/school/classes/{id}/members', fn (Request $request, array $parts): Response => $school->addMember($request, $parts), Access::TEACHERS, $schoolMode);
        $router->post('/school/classes/{id}/members/{user}/remove', fn (Request $request, array $parts): Response => $school->removeMember($request, $parts), Access::TEACHERS, $schoolMode);
        $router->post('/school/classes/{id}/members/{user}/revoke-card', fn (Request $request, array $parts): Response => $school->revokeCard($request, $parts), Access::TEACHERS, $schoolMode);
        $router->post('/school/classes/{id}/members/{user}/password', fn (Request $request, array $parts): Response => $school->setPassword($request, $parts), Access::TEACHERS, $schoolMode);
        $router->post('/school/classes/{id}/limit', fn (Request $request, array $parts): Response => $school->setLimit($request, $parts), Access::TEACHERS, $schoolMode);
        $router->post('/school/classes/{id}/cards', fn (Request $request, array $parts): Response => $school->printCards($request, $parts), Access::TEACHERS, $schoolMode);
        $router->get('/school/students/{user}/games', fn (Request $request, array $parts): Response => $school->studentGames($request, $parts), Access::TEACHERS, $schoolMode);
        $router->get('/c/{token}', fn (Request $request, array $parts): Response => $school->showCardLogin($request, $parts), Access::EVERYONE, $schoolMode);
        $router->post('/c/{token}', fn (Request $request, array $parts): Response => $school->cardLogin($request, $parts), Access::EVERYONE, $schoolMode);
    }
}
