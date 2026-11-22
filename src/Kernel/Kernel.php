<?php

declare(strict_types=1);

namespace PhpChess\Kernel;

use Closure;
use PhpChess\Http\Api\V1\StatusController;
use PhpChess\Http\ApiError;
use PhpChess\Http\Request;
use PhpChess\Http\Response;
use PhpChess\Http\Template;
use PhpChess\Http\Web\HomeController;
use PhpChess\Infra\Database;
use PhpChess\Infra\Jobs\CleanupJob;
use PhpChess\Infra\Jobs\JobRunner;
use PhpChess\Infra\System\JobRepository;
use PhpChess\Infra\System\RateLimiter;
use PhpChess\Infra\System\SettingsRepository;
use PhpChess\Install\InstallerController;
use Throwable;

/**
 * The kernel: turns one request into one response (ARCHITECTURE.md §5).
 *
 * Called by public/index.php. In the order it runs:
 *   1. load the config; without a finished install, only the installer answers;
 *   2. find the route (404 / 405 otherwise, K7);
 *   3. on POST, check the CSRF token (419 otherwise, K7);
 *   4. on the API, count the request against the rate limit (429, API-04);
 *   5. run the controller, which returns a Response (K13);
 *   6. add the security headers (K9) and write one log line.
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
     * @param string                        $appRoot          the install folder (above public/)
     * @param (Closure(Config): Database)|null $connectDatabase  tests pass their own; default Database::connect
     * @param (Closure(string): ?string)|null  $fetchUrl         for the installer self-test; tests fake it
     * @param int|null                      $now              the time to use; tests fix it
     */
    public function __construct(
        private readonly string $appRoot,
        ?Closure $connectDatabase = null,
        ?Closure $fetchUrl = null,
        private readonly ?int $now = null,
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
        if ($isInstalled) {
            Routes::addApplication($router, new HomeController($this->template), new StatusController());
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

        // 3. Every change needs this session's CSRF token (K7).
        if ($match->route->method === 'POST' && !$csrf->isValidRequest($request)) {
            return $this->errorResponse($request, 419, 'csrf_failed', 'This form has expired. Please go back, reload the page and try again.');
        }

        // 4. API rate limit (API-04).
        if ($isInstalled && $request->isApi()) {
            $limiter = new RateLimiter($this->database());
            $now = $this->currentTime();
            if (!$limiter->hit('api:ip:' . $request->clientIp, self::API_REQUESTS_PER_MINUTE, 60, $now)) {
                return ApiError::response(429, 'rate_limited', 'Too many requests. Please wait a moment.')
                    ->withHeader('Retry-After', (string) RateLimiter::secondsUntilNextWindow(60, $now));
            }
        }

        // 5. The controller answers (K13: its Response is the end of the request).
        return ($match->route->handler)($request, $match->pathParts);
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

        return new JobRunner(
            $database,
            new SettingsRepository($database),
            new JobRepository($database),
            [new CleanupJob($database, $this->appRoot . '/storage/logs')],
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
                throw new \RuntimeException('phpChess is not installed yet.');
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
