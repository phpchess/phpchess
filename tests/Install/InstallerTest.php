<?php

declare(strict_types=1);

namespace PhpChess\Tests\Install;

use Closure;
use PhpChess\Infra\Database;
use PhpChess\Kernel\Config;
use PhpChess\Kernel\Kernel;
use PhpChess\Tests\Support\TestBrowser;
use PhpChess\Tests\Support\TestDatabase;
use PhpChess\Tests\Support\TestInstallFolder;
use PHPUnit\Framework\TestCase;

/**
 * Tests the installer from start to finish (INS-02 to INS-06), through the
 * kernel like a browser would, and its refusal when protection fails (INS-04).
 */
final class InstallerTest extends TestCase
{
    private TestInstallFolder $folder;
    private Config $databaseConfig;
    private Database $database;

    protected function setUp(): void
    {
        $this->databaseConfig = TestDatabase::config($this);
        $this->database = TestDatabase::connectEmpty($this);
        TestDatabase::createForeignTable($this->database);
        $this->folder = new TestInstallFolder();
    }

    protected function tearDown(): void
    {
        if (isset($this->folder)) {
            $this->folder->remove();
        }
        if (isset($this->database)) {
            TestDatabase::dropTestTables($this->database);
            $this->database->run('DROP TABLE IF EXISTS ' . TestDatabase::FOREIGN_TABLE);
        }
    }

    // --- Start to finish ---

    public function testInstallsFromStartToFinish(): void
    {
        $browser = $this->browserWithServer(fn (string $url): string => '<h1>Forbidden</h1>');

        // Step 1: pre-flight.
        self::assertStringContainsString('Step 1', $browser->get('/install')->body);
        self::assertSame(303, $browser->post('/install/preflight')->status);

        // Step 2: protection self-test.
        self::assertStringContainsString('Step 2', $browser->get('/install')->body);
        self::assertSame(303, $browser->post('/install/protection')->status);

        // Step 3: database; writes the config, not yet "installed".
        self::assertStringContainsString('Step 3', $browser->get('/install')->body);
        self::assertSame(303, $browser->post('/install/database', $this->databaseFields())->status);
        $config = Config::load($this->folder->path);
        self::assertNotNull($config);
        self::assertFalse($config->installed);

        // Step 4: admin account.
        self::assertStringContainsString('Step 4', $browser->get('/install')->body);
        self::assertSame(303, $browser->post('/install/admin', [
            'username' => 'admin', 'email' => 'admin@example.test',
            'password' => 'a long password', 'password_again' => 'a long password',
        ])->status);

        // Step 5: migrations.
        self::assertStringContainsString('Step 5', $browser->get('/install')->body);
        self::assertSame(303, $browser->post('/install/migrations')->status);

        // Step 6: lock.
        self::assertStringContainsString('Step 6', $browser->get('/install')->body);
        $finished = $browser->post('/install/finish');
        self::assertStringContainsString('phpChess is installed', $finished->body);

        // Installed: the installer is locked and the site answers.
        $config = Config::load($this->folder->path);
        self::assertNotNull($config);
        self::assertTrue($config->installed);
        self::assertSame('', $config->installerOwnerHash);
        self::assertStringContainsString('removed itself', $finished->body);
        self::assertDirectoryDoesNotExist($this->folder->path . '/templates/install', 'the installer removed itself (A23)');
        self::assertSame(404, $browser->get('/install')->status, 'the installer no longer exists');
        self::assertSame(200, $browser->get('/')->status);
        self::assertSame(['server' => 'phpChess', 'version' => \PhpChess\Version::CURRENT, 'api' => 1], json_decode($browser->get('/api/v1/status')->body, true));

        // The admin exists, with a hashed password; another program's table is untouched.
        $admin = $this->database->fetchRow('SELECT username, role, password_hash FROM pt_users');
        self::assertNotNull($admin);
        self::assertSame('admin', $admin['username']);
        self::assertTrue(password_verify('a long password', (string) (is_string($admin['password_hash']) ? $admin['password_hash'] : '')));
        self::assertSame('not ours', $this->database->fetchValue('SELECT name FROM ' . TestDatabase::FOREIGN_TABLE));
    }

    // --- Refusals ---

    public function testStopsWhenThePrivateFoldersAreExposed(): void
    {
        $appRoot = $this->folder->path;
        $leakyServer = fn (string $url): string => (string) @file_get_contents($appRoot . substr($url, strlen('http://example.test/chess')));
        $browser = $this->browserWithServer($leakyServer);

        $browser->get('/install');
        $browser->post('/install/preflight');
        $response = $browser->post('/install/protection');

        self::assertSame(409, $response->status);
        self::assertStringContainsString('Stop: private folders can be opened from the web', $response->body);
        self::assertStringContainsString('root ' . $appRoot . '/public;', $response->body, 'the nginx snippet is shown');
        self::assertStringContainsString('Step 2', $browser->get('/install')->body, 'the installer stays at step 2');
        self::assertFileDoesNotExist(Config::filePath($appRoot));
    }

    public function testAnotherBrowserCannotTakeOverAStartedInstall(): void
    {
        $browser = $this->browserWithServer(fn (string $url): string => 'Forbidden');
        $browser->get('/install');
        $browser->post('/install/preflight');
        $browser->post('/install/protection');
        $browser->post('/install/database', $this->databaseFields());

        $stranger = $this->browserWithServer(fn (string $url): string => 'Forbidden');
        $stranger->get('/install');
        $stranger->post('/install/preflight');
        $stranger->post('/install/protection');

        self::assertSame(403, $stranger->get('/install')->status);
        self::assertSame(403, $stranger->post('/install/database', $this->databaseFields())->status);
    }

    public function testClickingConnectTwiceDoesNotLockTheOwnerOut(): void
    {
        $browser = $this->browserWithServer(fn (string $url): string => 'Forbidden');
        $browser->get('/install');
        $browser->post('/install/preflight');
        $browser->post('/install/protection');

        $firstClick = $browser->post('/install/database', $this->databaseFields());
        $secondClick = $browser->post('/install/database', $this->databaseFields());

        self::assertSame([303, 303], [$firstClick->status, $secondClick->status]);
        self::assertStringContainsString('Step 4', $browser->get('/install')->body);
    }

    public function testWrongDatabaseDetailsAreShownAndNothingIsWritten(): void
    {
        $browser = $this->browserWithServer(fn (string $url): string => 'Forbidden');
        $browser->get('/install');
        $browser->post('/install/preflight');
        $browser->post('/install/protection');

        $response = $browser->post('/install/database', ['database_password' => 'wrong'] + $this->databaseFields());

        self::assertSame(422, $response->status);
        self::assertStringContainsString('refused the user name or password', $response->body);
        self::assertFileDoesNotExist(Config::filePath($this->folder->path));
    }

    // --- Helpers ---

    /**
     * @param Closure(string): ?string $server
     */
    private function browserWithServer(Closure $server): TestBrowser
    {
        return new TestBrowser(new Kernel($this->folder->path, fetchUrl: $server));
    }

    /**
     * @return array<string, string>
     */
    private function databaseFields(): array
    {
        return [
            'database_host' => $this->databaseConfig->databaseHost,
            'database_port' => (string) $this->databaseConfig->databasePort,
            'database_name' => $this->databaseConfig->databaseName,
            'database_user' => $this->databaseConfig->databaseUser,
            'database_password' => $this->databaseConfig->databasePassword,
            'table_prefix' => TestDatabase::PREFIX,
        ];
    }
}
