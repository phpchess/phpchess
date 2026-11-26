<?php

declare(strict_types=1);

namespace PhpChess\Tests\Support;

use PhpChess\Domain\Identity\PasswordRules;
use PhpChess\Domain\Identity\User;
use PhpChess\Infra\Database;
use PhpChess\Infra\Identity\UserRepository;
use PhpChess\Infra\Migration\MigrationRunner;
use PhpChess\Infra\System\SettingsRepository;
use PhpChess\Kernel\Kernel;
use PHPUnit\Framework\TestCase;

/**
 * A finished phpChess install for tests of the site itself (accounts, admin, switches).
 *
 * Makes a test install folder with an "installed" config, runs every
 * migration on the test database, and creates an admin ("admin" /
 * "admin password 1"). The mailer keeps the emails (RecordingMailer), the
 * push sender keeps the pushes (RecordingPushSender). Lichess is never
 * asked: lichessReplies answers instead (path => reply body; anything else 503).
 * browser() gives a fresh visitor; loggedInBrowser() one that is logged in.
 */
final class InstalledSite
{
    public const string ADMIN_NAME = 'admin';
    public const string PASSWORD = 'a good password 1';

    public readonly TestInstallFolder $folder;
    public readonly Database $database;
    public readonly RecordingMailer $mailer;
    public readonly RecordingPushSender $pushSender;
    public readonly Kernel $kernel;

    /** @var array<string, string> what the fake Lichess answers: a path start => the reply body */
    public array $lichessReplies = [];

    /** @var list<string> the addresses the fake Lichess was asked, in order */
    public array $lichessAsked = [];

    public function __construct(TestCase $test)
    {
        $this->database = TestDatabase::connectEmpty($test);
        $this->folder = new TestInstallFolder();
        $this->mailer = new RecordingMailer();
        $this->pushSender = new RecordingPushSender();

        $config = TestDatabase::config($test)->withInstallState(true, '');
        file_put_contents($this->folder->path . '/config/config.php', $config->toFileContents());

        (new MigrationRunner($this->database, MigrationRunner::loadFolder($this->folder->path . '/migrations')))->runPending();
        (new UserRepository($this->database))->createAdmin(self::ADMIN_NAME, 'admin@example.test', PasswordRules::hash(self::PASSWORD));

        // References to the two lists, not to $this: a closure holding $this
        // would keep the whole site, and its database connection, alive.
        $replies = &$this->lichessReplies;
        $asked = &$this->lichessAsked;
        $this->kernel = new Kernel(
            $this->folder->path,
            mailer: $this->mailer,
            pushSender: $this->pushSender,
            lichessTransport: static function (string $url, string $accept) use (&$replies, &$asked): array {
                $asked[] = $url;
                foreach ($replies as $pathStart => $body) {
                    if (str_starts_with($url, 'https://lichess.org' . $pathStart)) {
                        return ['status' => 200, 'body' => $body];
                    }
                }

                return ['status' => 503, 'body' => ''];
            },
        );
    }

    /** Removes the folder and the test tables. */
    public function remove(): void
    {
        $this->folder->remove();
        TestDatabase::dropTestTables($this->database);
    }

    public function settings(): SettingsRepository
    {
        return new SettingsRepository($this->database);
    }

    public function users(): UserRepository
    {
        return new UserRepository($this->database);
    }

    /** Creates an active account with the given role, password PASSWORD. */
    public function createUser(string $username, string $role = User::ROLE_PLAYER, string $state = User::STATE_ACTIVE): int
    {
        return $this->users()->create($username, $username . '@example.test', PasswordRules::hash(self::PASSWORD), $role, $state);
    }

    /** A new visitor, not logged in. */
    public function browser(): TestBrowser
    {
        return new TestBrowser($this->kernel);
    }

    /** A visitor who logged in with the name and PASSWORD (and, for admins, confirmed the password). */
    public function loggedInBrowser(string $username, bool $confirmAdmin = false): TestBrowser
    {
        $browser = $this->browser();
        $browser->submit('/login', '/login', ['name_or_email' => $username, 'password' => self::PASSWORD]);
        if ($confirmAdmin) {
            $browser->submit('/admin/confirm', '/admin/confirm', ['password' => self::PASSWORD, 'next' => '/admin']);
        }

        return $browser;
    }
}
