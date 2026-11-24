<?php

declare(strict_types=1);

namespace PhpChess\Install;

use Closure;
use PhpChess\Http\Request;
use PhpChess\Http\Response;
use PhpChess\Http\Template;
use PhpChess\Infra\Database;
use PhpChess\Infra\Identity\UserRepository;
use PhpChess\Infra\Migration\MigrationRunner;
use PhpChess\Infra\System\SettingsRepository;
use PhpChess\Kernel\Config;
use PhpChess\Kernel\Csrf;
use PhpChess\Kernel\Session;
use PDOException;
use Throwable;

/**
 * The web installer: open the site's address and follow the steps (INS-02 to INS-06).
 *
 * The steps (ARCHITECTURE.md §10):
 *   1. pre-flight checks             PreflightCheck
 *   2. protection self-test          ProtectionSelfTest (stops when exposed)
 *   3. database details              writes config/config.php, "installed" = false
 *   4. admin account                 kept in the session, password already hashed
 *   5. migrations                    MigrationRunner, a few steps per request
 *   6. lock                          "installed" = true; the installer is closed
 *
 * The current step is worked out from the facts every time (showStep), so a
 * reload or a lost connection never breaks the install: it simply carries on.
 *
 * Who may continue: the browser that wrote the config holds a secret in its
 * session; the config stores only its hash. Another visitor reaching the
 * half-finished installer can't take it over (installer_owner_hash).
 *
 * Every POST is checked for CSRF by the Kernel before it gets here (K7), and
 * every method returns a Response (K13).
 */
final class InstallerController
{
    /** Migration steps run per request, so a host's time limit is never reached. */
    private const int MIGRATION_STEPS_PER_REQUEST = 10;

    private const string OWNER_SECRET = 'install.owner_secret';
    private const string PREFLIGHT_PASSED = 'install.preflight_passed';
    private const string PROTECTION_PASSED = 'install.protection_passed';
    private const string ADMIN_DETAILS = 'install.admin';
    private const string DATABASE_DETAILS = 'install.database_details';

    /**
     * @param Closure(Config): Database $connectDatabase  opens a connection (tests pass a test database)
     * @param (Closure(string): ?string)|null $fetchUrl   HTTP fetch for the self-test; tests fake it, null = real HTTP
     */
    public function __construct(
        private readonly string $appRoot,
        private readonly Template $template,
        private readonly Session $session,
        private readonly Csrf $csrf,
        private readonly Closure $connectDatabase,
        private readonly ?Closure $fetchUrl,
        private readonly int $now,
    ) {
    }

    // --- Showing the current step (GET /install) ---

    /** Shows whichever step comes next. */
    public function showStep(Request $request): Response
    {
        $config = Config::load($this->appRoot);

        // After the install the installer stays closed for good; updates run
        // from the admin pages ("Run updates now", A54).
        if ($config !== null && $config->installed) {
            return $this->page($request, 'install/locked', 'The installer is locked', 0, [], 403);
        }

        if ($this->session->get(self::PREFLIGHT_PASSED) !== true) {
            $checks = (new PreflightCheck($this->appRoot))->checkServer($request->isHttps);

            return $this->page($request, 'install/preflight', 'Step 1: Can this server run phpChess?', 1, [
                'checks' => $checks,
                'canContinue' => CheckResult::allRequiredPassed($checks),
            ]);
        }

        if ($this->session->get(self::PROTECTION_PASSED) !== true) {
            return $this->page($request, 'install/protection', 'Step 2: Are the private folders protected?', 2, [
                'testResult' => null,
                'nginxSnippet' => ProtectionSelfTest::nginxSnippet($this->appRoot),
            ]);
        }

        if ($config === null) {
            return $this->databaseForm($request, [], '');
        }
        if (!$this->isOwner($config)) {
            return $this->page($request, 'install/not_owner', 'The installation was started elsewhere', 0, [], 403);
        }

        $database = $this->tryConnect($config);
        if ($database === null) {
            return $this->databaseForm($request, [], 'The saved database details no longer work. Please enter them again.');
        }

        $runner = new MigrationRunner($database, MigrationRunner::loadFolder($this->appRoot . '/migrations'));
        $pendingSteps = $runner->pendingStepCount();
        $adminExists = $this->adminExists($database);

        if (!$adminExists && !is_array($this->session->get(self::ADMIN_DETAILS))) {
            return $this->page($request, 'install/admin', 'Step 4: Your admin account', 4, ['error' => '', 'username' => '', 'email' => '']);
        }

        if ($pendingSteps > 0 || !$adminExists) {
            return $this->page($request, 'install/migrations', 'Step 5: Setting up the database', 5, ['pendingSteps' => $pendingSteps, 'stepsRun' => []]);
        }

        return $this->page($request, 'install/finish', 'Step 6: Finish and lock the installer', 6, []);
    }

    // --- Step 1: pre-flight ---

    /** POST /install/preflight: runs the checks again and goes on when they pass. */
    public function confirmPreflight(Request $request): Response
    {
        $checks = (new PreflightCheck($this->appRoot))->checkServer($request->isHttps);
        if (CheckResult::allRequiredPassed($checks)) {
            // A fresh session id at the start of the install, before anything
            // is written, so an id planted by someone else is worthless.
            $this->session->renewId();
            $this->session->set(self::PREFLIGHT_PASSED, true);
        }

        return Response::redirect($request->basePath . '/install');
    }

    // --- Step 2: protection self-test ---

    /**
     * POST /install/protection: runs the self-test. Exposed folders stop the
     * install (INS-04). When the server can't reach itself, the visitor
     * checks the two links by hand and confirms.
     */
    public function runProtectionTest(Request $request): Response
    {
        $selfTest = new ProtectionSelfTest($this->appRoot, $this->fetchUrl ?? ProtectionSelfTest::fetchWithHttp(...));
        $testResult = $selfTest->run(self::installRootUrl($request));

        $manuallyConfirmed = $request->formValue('manually_checked') === 'yes';
        $passed = $testResult['result'] === ProtectionSelfTest::RESULT_PROTECTED
            || ($testResult['result'] === ProtectionSelfTest::RESULT_UNKNOWN && $manuallyConfirmed);

        if ($passed) {
            $this->session->set(self::PROTECTION_PASSED, true);
            $this->session->set('install.protection_result', $testResult['result']);

            return Response::redirect($request->basePath . '/install');
        }

        $status = $testResult['result'] === ProtectionSelfTest::RESULT_EXPOSED ? 409 : 200;

        return $this->page($request, 'install/protection', 'Step 2: Are the private folders protected?', 2, [
            'testResult' => $testResult,
            'nginxSnippet' => ProtectionSelfTest::nginxSnippet($this->appRoot),
        ], $status);
    }

    // --- Step 3: database ---

    /** POST /install/database: tests the details, checks the server, writes the config. */
    public function saveDatabase(Request $request): Response
    {
        $existingConfig = Config::load($this->appRoot);
        if ($existingConfig !== null && !$this->isOwner($existingConfig)) {
            return $this->page($request, 'install/not_owner', 'The installation was started elsewhere', 0, [], 403);
        }

        $fields = [
            'database_host' => trim($request->formValue('database_host', 'localhost')),
            'database_port' => trim($request->formValue('database_port', '3306')),
            'database_name' => trim($request->formValue('database_name')),
            'database_user' => trim($request->formValue('database_user')),
            'database_password' => $request->formValue('database_password'),
            'table_prefix' => trim($request->formValue('table_prefix', 'phpc_')),
        ];

        // Picking from the list of databases: the other details were kept in the session.
        $keptFields = $this->session->get(self::DATABASE_DETAILS);
        if ($request->formValue('picked_from_list') === 'yes' && is_array($keptFields)) {
            foreach ($keptFields as $name => $value) {
                if (is_string($name) && is_string($value) && $name !== 'database_name') {
                    $fields[$name] = $value;
                }
            }
        }

        // Find or create the database (A23), so the visitor needs no database skills.
        $found = DatabaseFinder::find(
            $fields['database_host'],
            (int) $fields['database_port'],
            $fields['database_user'],
            $fields['database_password'],
            $fields['database_name'],
        );
        if ($found['status'] === DatabaseFinder::CHOOSE) {
            $this->session->set(self::DATABASE_DETAILS, $fields);

            return $this->page($request, 'install/database_choice', 'Step 3: Which database?', 3, [
                'choices' => $found['choices'],
                'message' => $found['message'],
            ]);
        }
        if ($found['status'] !== DatabaseFinder::FOUND) {
            return $this->databaseForm($request, $fields, $found['message']);
        }
        $fields['database_host'] = $found['host'];
        $fields['database_name'] = $found['name'];
        $this->session->remove(self::DATABASE_DETAILS);

        try {
            $config = Config::fromArray($fields);
        } catch (Throwable $error) {
            return $this->databaseForm($request, $fields, $error->getMessage());
        }

        try {
            $database = ($this->connectDatabase)($config);
        } catch (PDOException $error) {
            return $this->databaseForm($request, $fields, 'Could not connect: ' . $error->getMessage());
        }

        $checks = (new PreflightCheck($this->appRoot))->checkDatabase($database);
        if (!CheckResult::allRequiredPassed($checks)) {
            return $this->databaseForm($request, $fields, '', $checks);
        }

        // The secret that makes this browser the owner of the install. A second
        // click on the button (the connection can take seconds) keeps the same
        // secret, and the session id is not changed here, so a request still
        // on its way with the current cookie is not locked out.
        $ownerSecret = $this->session->getString(self::OWNER_SECRET);
        if ($ownerSecret === '') {
            $ownerSecret = bin2hex(random_bytes(32));
            $this->session->set(self::OWNER_SECRET, $ownerSecret);
        }
        $config = $config->withInstallState(false, hash('sha256', $ownerSecret));

        if (!$this->writeConfig($config)) {
            return $this->page($request, 'install/config_upload', 'Step 3: Upload the config file', 3, [
                'fileContents' => $config->toFileContents(),
            ]);
        }

        return Response::redirect($request->basePath . '/install');
    }

    // --- Step 4: admin account ---

    /** POST /install/admin: checks the account details and keeps them for step 5. */
    public function saveAdmin(Request $request): Response
    {
        $config = Config::load($this->appRoot);
        if ($config === null || !$this->isOwner($config)) {
            return Response::redirect($request->basePath . '/install');
        }

        $username = trim($request->formValue('username'));
        $email = trim($request->formValue('email'));
        $password = $request->formValue('password');

        $error = self::checkAdminDetails($username, $email, $password, $request->formValue('password_again'));
        if ($error !== '') {
            return $this->page($request, 'install/admin', 'Step 4: Your admin account', 4, [
                'error' => $error, 'username' => $username, 'email' => $email,
            ], 422);
        }

        $this->session->set(self::ADMIN_DETAILS, [
            'username' => $username,
            'email' => $email,
            'password_hash' => password_hash($password, defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT),
        ]);

        return Response::redirect($request->basePath . '/install');
    }

    /** Returns what is wrong with the admin details, or "" when they are fine. */
    public static function checkAdminDetails(string $username, string $email, string $password, string $passwordAgain): string
    {
        if (preg_match('/^[A-Za-z0-9_.-]{3,40}$/', $username) !== 1) {
            return 'The user name needs 3 to 40 letters, digits, ".", "-" or "_".';
        }
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return 'Please enter a valid email address.';
        }
        if (mb_strlen($password) < 10) {
            return 'The password needs at least 10 characters.';
        }
        if ($password !== $passwordAgain) {
            return 'The two passwords are not the same.';
        }

        return '';
    }

    // --- Step 5: migrations ---

    /** POST /install/migrations: runs the next batch of migration steps, then creates the admin. */
    public function runMigrations(Request $request): Response
    {
        $config = Config::load($this->appRoot);
        if ($config === null || !$this->isOwner($config)) {
            return Response::redirect($request->basePath . '/install');
        }

        $database = ($this->connectDatabase)($config);
        $runner = new MigrationRunner($database, MigrationRunner::loadFolder($this->appRoot . '/migrations'));
        $stepsRun = $runner->runPending(self::MIGRATION_STEPS_PER_REQUEST);
        $pendingSteps = $runner->pendingStepCount();

        if ($pendingSteps === 0) {
            $this->createAdminFromSession($database);
        }

        if ($pendingSteps > 0) {
            return $this->page($request, 'install/migrations', 'Step 5: Setting up the database', 5, [
                'pendingSteps' => $pendingSteps, 'stepsRun' => $stepsRun,
            ]);
        }

        return Response::redirect($request->basePath . '/install');
    }

    private function createAdminFromSession(Database $database): void
    {
        $details = $this->session->get(self::ADMIN_DETAILS);
        if (!is_array($details) || $this->adminExists($database)) {
            return;
        }

        $username = is_string($details['username'] ?? null) ? $details['username'] : '';
        $email = is_string($details['email'] ?? null) ? $details['email'] : '';
        $passwordHash = is_string($details['password_hash'] ?? null) ? $details['password_hash'] : '';
        if ($username === '' || $passwordHash === '') {
            return;
        }

        (new UserRepository($database))->createAdmin($username, $email, $passwordHash);
        $this->session->remove(self::ADMIN_DETAILS);
    }

    // --- Step 6: lock ---

    /** POST /install/finish: marks the install as done, which locks the installer (INS-06). */
    public function finish(Request $request): Response
    {
        $config = Config::load($this->appRoot);
        if ($config === null || !$this->isOwner($config)) {
            return Response::redirect($request->basePath . '/install');
        }

        $database = ($this->connectDatabase)($config);
        $runner = new MigrationRunner($database, MigrationRunner::loadFolder($this->appRoot . '/migrations'));
        if ($runner->pendingStepCount() > 0 || !$this->adminExists($database)) {
            return Response::redirect($request->basePath . '/install');
        }

        $settings = new SettingsRepository($database);
        $protectionResult = $this->session->getString('install.protection_result');
        $settings->set('install.protection_result', $protectionResult === '' ? 'protected' : $protectionResult);
        $settings->set('install.finished_at', $this->now);

        if (!$config->installed && !$this->writeConfig($config->withInstallState(true, ''))) {
            return $this->page($request, 'install/config_upload', 'Step 6: Upload the final config file', 6, [
                'fileContents' => $config->withInstallState(true, '')->toFileContents(),
            ]);
        }

        $this->session->remove(self::OWNER_SECRET);
        $this->session->remove(self::PREFLIGHT_PASSED);
        $this->session->remove(self::PROTECTION_PASSED);

        // The installer removes itself (A23): first its code, then, once the
        // last page is rendered, its templates. If the host refuses, the lock
        // still keeps it closed, and the admin banner asks to delete it by FTP.
        $codeRemoved = self::deleteFolder($this->appRoot . '/src/Install');
        $response = $this->page($request, 'install/done', 'phpChess is installed', 6, [
            'installerRemoved' => $codeRemoved,
        ]);
        self::deleteFolder($this->appRoot . '/templates/install');

        return $response;
    }

    /**
     * Deletes a folder and everything in it. Returns true when it is gone
     * afterwards (also when it was never there).
     */
    private static function deleteFolder(string $folder): bool
    {
        if (!is_dir($folder)) {
            return true;
        }

        foreach (scandir($folder) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $folder . '/' . $entry;
            if (is_dir($path)) {
                self::deleteFolder($path);
                continue;
            }
            @unlink($path);
        }
        @rmdir($folder);

        return !is_dir($folder);
    }

    // --- Helpers ---

    /**
     * The address of the install folder: the base path without a trailing
     * "/public", which appears when public/ is opened directly.
     */
    public static function installRootUrl(Request $request): string
    {
        $baseUrl = $request->baseUrl();

        return str_ends_with($baseUrl, '/public') ? substr($baseUrl, 0, -strlen('/public')) : $baseUrl;
    }

    private function isOwner(Config $config): bool
    {
        if ($config->installed) {
            return false;
        }
        $secret = $this->session->getString(self::OWNER_SECRET);

        return $secret !== '' && hash_equals($config->installerOwnerHash, hash('sha256', $secret));
    }

    private function tryConnect(Config $config): ?Database
    {
        try {
            return ($this->connectDatabase)($config);
        } catch (PDOException) {
            return null;
        }
    }

    private function adminExists(Database $database): bool
    {
        if (!$database->tableExists($database->table('users'))) {
            return false;
        }

        return (new UserRepository($database))->countAdmins() > 0;
    }

    /** Writes config/config.php; false when the folder isn't writable. */
    private function writeConfig(Config $config): bool
    {
        $path = Config::filePath($this->appRoot);
        if (@file_put_contents($path, $config->toFileContents(), LOCK_EX) === false) {
            return false;
        }
        // PHP's opcode cache may still hold the old file; tell it to read the new one.
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($path, true);
        }

        return true;
    }

    /**
     * @param array<string, string> $fields
     * @param list<CheckResult>     $checks
     */
    private function databaseForm(Request $request, array $fields, string $error, array $checks = []): Response
    {
        return $this->page($request, 'install/database', 'Step 3: Your database', 3, [
            'fields' => $fields + [
                'database_host' => 'localhost', 'database_port' => '3306', 'database_name' => '',
                'database_user' => '', 'table_prefix' => 'phpc_',
            ],
            'error' => $error,
            'checks' => $checks,
        ], $error === '' && $checks === [] ? 200 : 422);
    }

    /**
     * Renders an installer page inside the installer layout.
     *
     * @param array<string, mixed> $variables
     */
    private function page(Request $request, string $templateName, string $title, int $stepNumber, array $variables, int $status = 200): Response
    {
        $common = [
            'title' => $title,
            'stepNumber' => $stepNumber,
            'basePath' => $request->basePath,
            'csrfToken' => $this->csrf->token(),
        ];
        $contentHtml = $this->template->render($templateName, $variables + $common);
        $html = $this->template->render('install/layout', $common + ['contentHtml' => $contentHtml]);

        return Response::html($html, $status);
    }
}
