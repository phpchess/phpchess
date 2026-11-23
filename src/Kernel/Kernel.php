<?php

declare(strict_types=1);

namespace PhpChess\Kernel;

use Closure;
use PhpChess\Domain\Identity\User;
use PhpChess\Http\ApiError;
use PhpChess\Http\Request;
use PhpChess\Http\Response;
use PhpChess\Http\Template;
use PhpChess\Infra\Database;
use PhpChess\Infra\Identity\LoginAttemptRepository;
use PhpChess\Infra\Identity\TokenRepository;
use PhpChess\Domain\Notify\MoveEmails;
use PhpChess\Domain\System\FeatureSwitches;
use PhpChess\Infra\Identity\PreferenceRepository;
use PhpChess\Infra\Identity\UserRepository;
use PhpChess\Infra\Jobs\CleanupJob;
use PhpChess\Infra\Jobs\GameClockJob;
use PhpChess\Infra\Jobs\MoveEmailJob;
use PhpChess\Infra\Jobs\JobRunner;
use PhpChess\Infra\Mail\Mailer;
use PhpChess\Infra\System\JobRepository;
use PhpChess\Infra\System\RateLimiter;
use PhpChess\Infra\System\SettingsRepository;
use PhpChess\Domain\Language\Translator;
use PhpChess\Install\InstallerController;
use RuntimeException;
use Throwable;

/**
 * The kernel: turns one request into one response (ARCHITECTURE.md §5).
 *
 * Called by public/index.php. In the order it runs:
 *   1. load the config; without a finished install, only the installer answers;
 *   2. find the route (404 / 405 otherwise, K7);
 *   3. a switched-off feature answers 404 (K8);
 *   4. check who may open the route (Access, K14): login page, 403, or the
 *      admin's password confirmation (ID-08);
 *   5. on POST, check the CSRF token (419 otherwise, K7);
 *   6. on the API, count the request against the rate limit (429, API-04);
 *   7. run the controller, which returns a Response (K13);
 *   8. add the cookies, the security headers (K9) and write one log line.
 * Any error on the way is logged and answered with a plain 500 page.
 *
 * After the response has been sent, afterResponse() runs the pseudo-cron (§8).
 */
final class Kernel
{
    /** API requests allowed per client address per minute (API-04). */
    private const int API_REQUESTS_PER_MINUTE = 300;

    private readonly Closure $connectDatabase;
    private readonly ?Closure $fetchUrl;
    private readonly Template $template;
    private readonly ErrorHandler $errorHandler;
    private ?Config $config = null;
    private ?Database $database = null;

    /**
     * @param string                           $appRoot          the install folder (above public/)
     * @param (Closure(Config): Database)|null $connectDatabase  tests pass their own; default Database::connect
     * @param (Closure(string): ?string)|null  $fetchUrl         for the installer self-test; tests fake it; null = real HTTP
     * @param int|null                         $now              the time to use; tests fix it
     * @param Mailer|null                      $mailer           tests pass one that keeps the emails; null = the settings' choice
     */
    public function __construct(
        private readonly string $appRoot,
        ?Closure $connectDatabase = null,
        ?Closure $fetchUrl = null,
        private readonly ?int $now = null,
        private readonly ?Mailer $mailer = null,
    ) {
        $this->connectDatabase = $connectDatabase ?? Database::connect(...);
        $this->fetchUrl = $fetchUrl;
        $this->template = new Template($appRoot . '/templates');
        $this->errorHandler = new ErrorHandler($appRoot . '/storage/logs');
    }

    // --- Handling a request ---

    /** Answers one request. Never throws: errors become a 500 response. */
    public function handle(Request $request, Session $session): Response
    {
        $startedAt = microtime(true);

        try {
            $response = $this->route($request, $session);
        } catch (Throwable $error) {
            $this->errorHandler->log($error);
            $response = $this->errorResponse($request, 500, 'server_error', 'Something went wrong. The error has been logged.');
        }

        $response = SecurityHeaders::addTo($response, $request->isHttps);
        $this->writeRequestLog($request, $response, microtime(true) - $startedAt);

        return $response;
    }

    private function route(Request $request, Session $session): Response
    {
        $this->config = Config::load($this->appRoot);
        $isInstalled = $this->config !== null && $this->config->installed;
        $csrf = new Csrf($session);
        $services = null;

        // 1. Build the list of addresses for this state of the install.
        $router = new Router();
        // The installer deletes itself at the end (A23); its addresses exist only while its files do.
        if ($this->installerIsPresent()) {
            $installer = new InstallerController(
                $this->appRoot,
                $this->template,
                $session,
                $csrf,
                $this->connectDatabase,
                $this->fetchUrl,
                $this->currentTime(),
            );
            Routes::addInstaller($router, $installer);
        }
        if ($this->config !== null && $this->config->installed) {
            $services = new Services(
                $this->appRoot,
                $this->config,
                $request,
                $session,
                $csrf,
                $this->currentTime(),
                $this->connectDatabase,
                $this->mailer,
            );
            Routes::addApplication($router, $services);
        }

        // 2. Find the route.
        $match = $router->match($request);
        if ($match->result === RouteMatch::NOT_FOUND) {
            if (!$isInstalled && !$request->isApi()) {
                if (!$this->installerIsPresent()) {
                    return $this->errorResponse($request, 503, 'not_installed', 'phpChess is not installed, and the installer files are missing. Upload the release files again.');
                }

                return Response::redirect($request->basePath . '/install');
            }

            return $this->errorResponse($request, 404, 'not_found', 'There is no page at this address.');
        }
        if ($match->result === RouteMatch::METHOD_NOT_ALLOWED || $match->route === null) {
            return $this->errorResponse($request, 405, 'method_not_allowed', 'This address does not accept this kind of request.')
                ->withHeader('Allow', implode(', ', $match->allowedMethods));
        }
        $route = $match->route;

        // 3. A switched-off feature does not exist (K8).
        if ($route->featureSwitch !== null && $services !== null && !$services->switches()->isOn($route->featureSwitch)) {
            return $this->errorResponse($request, 404, 'not_found', 'There is no page at this address.');
        }

        // 4. Who may open it (K14, ID-08).
        if ($services !== null) {
            $refusal = $this->refuseWithoutAccess($route, $request, $services);
            if ($refusal !== null) {
                return $this->withAuthenticationCookies($refusal, $services);
            }
        }

        // 5. Every change needs this session's CSRF token (K7).
        if ($route->method === 'POST' && !$csrf->isValidRequest($request)) {
            return $this->errorResponse($request, 419, 'csrf_failed', 'This form has expired. Please go back, reload the page and try again.');
        }

        // 6. API rate limit (API-04).
        if ($services !== null && $request->isApi()) {
            $now = $this->currentTime();
            if (!$services->rateLimiter()->hit('api:ip:' . $request->clientIp, self::API_REQUESTS_PER_MINUTE, 60, $now)) {
                return ApiError::response(429, 'rate_limited', 'Too many requests. Please wait a moment.')
                    ->withHeader('Retry-After', (string) RateLimiter::secondsUntilNextWindow(60, $now));
            }
        }

        // 7. Presence (ID-12) and the site address for emails sent by jobs.
        if ($services !== null) {
            $this->notePresenceAndAddress($request, $services);
        }

        // 8. The controller answers (K13: its Response is the end of the request).
        $response = ($route->handler)($request, $match->pathParts);

        // 9. Cookies the login set or rotated (remember-me, ID-05).
        return $services === null ? $response : $this->withAuthenticationCookies($response, $services);
    }

    /**
     * Checks the route's Access rule. Returns the refusal, or null when the
     * visitor may go on:
     * - not logged in, on a members or admin page: to the login page (401 on the API);
     * - logged in without the admin role, on an admin page: 403;
     * - an admin whose password confirmation is older than 30 minutes: to the confirmation page.
     */
    private function refuseWithoutAccess(Route $route, Request $request, Services $services): ?Response
    {
        if ($route->access === Access::EVERYONE) {
            return null;
        }

        $user = $services->authentication()->currentUser();
        if ($user === null) {
            if ($request->isApi()) {
                return ApiError::response(401, 'login_required', 'Please log in first.');
            }

            return Response::redirect($request->basePath . '/login?next=' . rawurlencode($request->path));
        }

        if ($route->access === Access::MEMBERS) {
            return null;
        }

        if ($user->role !== User::ROLE_ADMIN) {
            return $this->errorResponse($request, 403, 'forbidden', 'This page is for admins only.');
        }

        if ($route->access === Access::ADMINS && !$services->authentication()->isAdminConfirmed($this->currentTime())) {
            $next = $route->method === 'GET' ? $request->path : '/admin';

            return Response::redirect($request->basePath . '/admin/confirm?next=' . rawurlencode($next));
        }

        return null;
    }

    /**
     * Notes that the logged-in user is here (presence, ID-12; written at most
     * once a minute), and remembers the site's address (setting
     * site.base_url) so the background jobs can put links in emails.
     */
    private function notePresenceAndAddress(Request $request, Services $services): void
    {
        $user = $services->authentication()->currentUser();
        if ($user !== null) {
            $services->users()->markSeen($user->id, $this->currentTime());
        }
        if (!$request->isApi() && $request->host !== '' && $services->settings()->getString('site.base_url') !== $request->baseUrl()) {
            $services->settings()->set('site.base_url', $request->baseUrl());
        }
    }

    private function withAuthenticationCookies(Response $response, Services $services): Response
    {
        foreach ($services->authentication()->cookiesToSet() as $cookie) {
            $response = $response->withCookie($cookie);
        }

        return $response;
    }

    // --- After the response (pseudo-cron, ARCHITECTURE.md §8) ---

    /**
     * Runs the due background jobs when pseudo-cron is on. Called by
     * public/index.php after the response was sent, so the visitor never waits.
     */
    public function afterResponse(): void
    {
        if ($this->config === null || !$this->config->installed) {
            return;
        }

        try {
            $this->jobRunner()->tickAfterRequest($this->currentTime());
        } catch (Throwable $error) {
            $this->errorHandler->log($error);
        }
    }

    /** The job runner with every job, for pseudo-cron and for public/cron.php. */
    public function jobRunner(): JobRunner
    {
        $database = $this->database();
        $settings = new SettingsRepository($database);
        $play = new PlayParts($database, $settings);
        $mailer = $this->mailer ?? Services::mailerFromSettings($settings, $this->appRoot, 'localhost');
        $translator = new Translator($this->appRoot . '/lang', $settings->getString('site.language', Translator::FALLBACK_LANGUAGE));
        $moveEmails = new MoveEmails(
            $play->noticeRepository(),
            new UserRepository($database),
            new PreferenceRepository($database),
            $settings,
            $mailer,
            $translator,
        );

        return new JobRunner(
            $database,
            $settings,
            new JobRepository($database),
            [
                new CleanupJob(
                    $database,
                    $this->appRoot . '/storage/logs',
                    new TokenRepository($database),
                    new LoginAttemptRepository($database),
                ),
                new GameClockJob($play->games(), $play->gameRepository(), $play->challengeRepository(), $this->now),
                new MoveEmailJob($moveEmails, new FeatureSwitches($settings), $settings, $this->now),
            ],
        );
    }

    /** Switches error display off and logs instead (called by the entry points). */
    public function registerErrorHandler(): void
    {
        $this->errorHandler->register();
    }

    // --- Helpers ---

    /**
     * True while the installer's files are still there. It deletes them when it
     * finishes (A23), and from then on /install does not exist at all.
     * class_exists() is safe here: the release's autoloader checks that a
     * class file exists before loading it (built without a class map).
     */
    private function installerIsPresent(): bool
    {
        return is_file($this->appRoot . '/templates/install/layout.php')
            && class_exists(InstallerController::class);
    }

    private function database(): Database
    {
        if ($this->database === null) {
            $this->config ??= Config::load($this->appRoot);
            if ($this->config === null) {
                throw new RuntimeException('phpChess is not installed yet.');
            }
            $this->database = ($this->connectDatabase)($this->config);
        }

        return $this->database;
    }

    private function currentTime(): int
    {
        return $this->now ?? time();
    }

    /** An error answer: the API error format for the API (API-04), a plain page otherwise. */
    private function errorResponse(Request $request, int $status, string $code, string $message): Response
    {
        if ($request->isApi()) {
            return ApiError::response($status, $code, $message);
        }

        $html = $this->template->render('error', [
            'status' => $status,
            'message' => $message,
            'basePath' => $request->basePath,
        ]);

        return Response::html($html, $status);
    }

    /** One line per request: time, method, path, status, milliseconds (§5). */
    private function writeRequestLog(Request $request, Response $response, float $seconds): void
    {
        $logFolder = $this->appRoot . '/storage/logs';
        if (!is_dir($logFolder) || !is_writable($logFolder)) {
            return;
        }

        $line = sprintf(
            "%s %s %s %d %dms\n",
            gmdate('Y-m-d\TH:i:s\Z'),
            $request->method,
            $request->path,
            $response->status,
            (int) round($seconds * 1000),
        );
        @file_put_contents($logFolder . '/requests-' . gmdate('Y-m-d') . '.log', $line, FILE_APPEND | LOCK_EX);
    }
}
