<?php

declare(strict_types=1);

namespace PhpChess\Install;

use PhpChess\Domain\System\ServerSupport;
use PhpChess\Infra\Database;

/**
 * Pre-flight checks: can this server run phpChess? (INS-03, installer step 1)
 *
 * Checks PHP's version and extensions, the writable folders and HTTPS before
 * anything is written. The database version and sql_mode are checked once the
 * database details are known (checkDatabase, installer step 3). Whether the
 * private folders are protected is the job of ProtectionSelfTest (step 2).
 */
final class PreflightCheck
{
    public const string MINIMUM_PHP = '8.3.0';
    public const string MINIMUM_MARIADB = '10.11.0';
    public const string MINIMUM_MYSQL = '8.0.0';

    /** PHP extensions phpChess cannot run without. */
    private const array REQUIRED_EXTENSIONS = ['pdo_mysql', 'mbstring', 'json', 'ctype'];

    public function __construct(private readonly string $appRoot)
    {
    }

    // --- Server checks (step 1) ---

    /**
     * @return list<CheckResult>
     */
    public function checkServer(bool $isHttps): array
    {
        $results = [];

        $results[] = new CheckResult(
            'PHP version',
            version_compare(PHP_VERSION, self::MINIMUM_PHP, '>='),
            true,
            'This server runs PHP ' . PHP_VERSION . '; phpChess needs ' . self::MINIMUM_PHP . ' or newer.',
            'Choose PHP 8.3 or newer in your hosting control panel.',
        );

        foreach (self::REQUIRED_EXTENSIONS as $extension) {
            $results[] = new CheckResult(
                "PHP extension {$extension}",
                extension_loaded($extension),
                true,
                extension_loaded($extension) ? 'Available.' : 'Missing.',
                "Switch on the \"{$extension}\" extension in your hosting control panel, or ask your host.",
            );
        }

        foreach (['storage', 'config'] as $folder) {
            $path = $this->appRoot . '/' . $folder;
            $isWritable = is_dir($path) && is_writable($path);
            $results[] = new CheckResult(
                "Folder {$folder}/ is writable",
                $isWritable,
                $folder === 'storage',
                $isWritable ? 'Writable.' : 'phpChess cannot write to this folder.',
                $folder === 'storage'
                    ? 'Give the folder "storage" write permission (755 or 775) with your FTP program.'
                    : 'Optional: give "config" write permission, or upload the config file yourself in step 3.',
            );
        }

        // Optional extras (ServerSupport): without them the feature simply stays off.
        $hasImages = ServerSupport::has(ServerSupport::IMAGES);
        $results[] = new CheckResult(
            'Picture uploads (PHP extension gd)',
            $hasImages,
            false,
            $hasImages ? 'Available.' : 'Missing: members can choose the bundled avatars, but not upload their own pictures.',
            'Optional. Ask your host to switch on the "gd" extension; phpChess notices it by itself.',
        );
        $hasPush = ServerSupport::has(ServerSupport::PUSH);
        $results[] = new CheckResult(
            'Push notifications (openssl, curl, mbstring)',
            $hasPush,
            false,
            $hasPush ? 'Available.' : 'Missing: members get email instead of push notifications.',
            'Optional. Ask your host for the openssl, curl and mbstring extensions; phpChess notices them by itself.',
        );

        $results[] = new CheckResult(
            'HTTPS',
            $isHttps,
            false,
            $isHttps ? 'The site is opened over HTTPS.' : 'The site is opened over plain HTTP.',
            'Switch on HTTPS (a free certificate) in your hosting control panel; passwords are otherwise sent unprotected.',
        );

        return $results;
    }

    // --- Database checks (step 3) ---

    /**
     * Checks the database server's version and that strict SQL mode works.
     *
     * @return list<CheckResult>
     */
    public function checkDatabase(Database $database): array
    {
        $versionText = $database->serverVersion();
        $results = [self::checkDatabaseVersion($versionText)];

        $sqlMode = $database->sessionSqlMode();
        $isStrict = str_contains($sqlMode, 'STRICT_ALL_TABLES');
        $results[] = new CheckResult(
            'Strict SQL mode',
            $isStrict,
            true,
            $isStrict ? 'phpChess can switch on strict mode for its connection.' : 'The server refused strict mode.',
            'Ask your host to allow "SET SESSION sql_mode".',
        );

        return $results;
    }

    /** Compares a server version text such as "10.11.8-MariaDB" with the minimum. */
    public static function checkDatabaseVersion(string $versionText): CheckResult
    {
        // MariaDB sometimes reports "5.5.5-10.11.8-MariaDB" for old clients.
        $cleanVersion = (string) preg_replace('/^5\.5\.5-/', '', $versionText);
        $isMariaDb = stripos($cleanVersion, 'mariadb') !== false;
        preg_match('/^\d+\.\d+\.\d+/', $cleanVersion, $numbers);
        $number = $numbers[0] ?? '0.0.0';

        $minimum = $isMariaDb ? self::MINIMUM_MARIADB : self::MINIMUM_MYSQL;
        $serverName = $isMariaDb ? 'MariaDB' : 'MySQL';

        return new CheckResult(
            'Database version',
            version_compare($number, $minimum, '>='),
            true,
            "{$serverName} {$number}; phpChess needs {$minimum} or newer.",
            'Choose a newer database version in your hosting control panel, or ask your host.',
        );
    }
}
