<?php

declare(strict_types=1);

namespace PhpChess\Install;

use PDOException;
use PhpChess\Infra\DatabaseServer;

/**
 * Finds or creates the database for phpChess, so the installer needs no database skills (INS-02, A23).
 *
 * The visitor gives what every host shows on its database page: the server,
 * a user name and a password. The database name is optional. Then, in order:
 *
 * 1. **Connect.** When "localhost" fails, "127.0.0.1" is tried too, and the
 *    other way round (hosts differ in which one works).
 * 2. **A name was given**: use it; when it doesn't exist yet, create it.
 * 3. **No name given**:
 *    a. a database called "phpchess" (or "something_phpchess") exists: use it;
 *    b. try to create "phpchess", then "<account>_phpchess" for hosts that put
 *       the account name in front (cPanel style: "jane_phpchess");
 *    c. the account sees exactly one database: use it (phpChess's own table
 *       prefix keeps it apart from anything else in there);
 *    d. it sees several: the visitor picks one from a list;
 *    e. it sees none: plain-language help on creating one in the control panel.
 *
 * The database server may be on another machine than PHP; the host field
 * then holds that machine's name, as the hosting panel shows it.
 */
final class DatabaseFinder
{
    public const string FOUND = 'found';
    public const string CHOOSE = 'choose';
    public const string CANNOT_CONNECT = 'cannot_connect';
    public const string NO_DATABASE = 'no_database';

    /** The name phpChess uses when it creates its own database (A22). */
    public const string DEFAULT_NAME = 'phpchess';

    /**
     * Works out the database to use.
     *
     * @return array{status: string, host: string, name: string, choices: list<string>, message: string}
     */
    public static function find(string $host, int $port, string $user, string $password, string $wantedName): array
    {
        // --- 1. Connect, trying the other spelling of "this machine" too ---
        $server = null;
        $usedHost = $host;
        $lastError = '';
        foreach (self::hostsToTry($host) as $candidateHost) {
            try {
                $server = DatabaseServer::connect($candidateHost, $port, $user, $password);
                $usedHost = $candidateHost;
                break;
            } catch (PDOException $error) {
                $lastError = $error->getMessage();
            }
        }
        if ($server === null) {
            return self::result(self::CANNOT_CONNECT, $host, '', [], self::explainConnectionError($lastError));
        }

        $visible = $server->visibleDatabases();

        // --- 2. A name was given ---
        if ($wantedName !== '') {
            if (!DatabaseServer::isSafeDatabaseName($wantedName)) {
                return self::result(self::NO_DATABASE, $usedHost, '', $visible, 'A database name may only hold letters, digits and "_".');
            }
            if (in_array($wantedName, $visible, true) || $server->tryCreateDatabase($wantedName)) {
                return self::result(self::FOUND, $usedHost, $wantedName, [], '');
            }

            return self::result(self::NO_DATABASE, $usedHost, '', $visible, "There is no database called \"{$wantedName}\", and this account may not create it.");
        }

        // --- 3a. An existing phpChess database ---
        foreach ($visible as $name) {
            if ($name === self::DEFAULT_NAME || str_ends_with($name, '_' . self::DEFAULT_NAME)) {
                return self::result(self::FOUND, $usedHost, $name, [], '');
            }
        }

        // --- 3b. Create one ---
        foreach (self::namesToCreate($user) as $name) {
            if ($server->tryCreateDatabase($name)) {
                return self::result(self::FOUND, $usedHost, $name, [], '');
            }
        }

        // --- 3c to 3e. Use what the account already has ---
        if (count($visible) === 1) {
            return self::result(self::FOUND, $usedHost, $visible[0], [], '');
        }
        if (count($visible) > 1) {
            return self::result(self::CHOOSE, $usedHost, '', $visible, 'This account can use several databases. Please pick the one for phpChess.');
        }

        return self::result(
            self::NO_DATABASE,
            $usedHost,
            '',
            [],
            'This account has no database yet and may not create one. In your hosting control panel, open "Databases" '
            . '(or "MySQL Databases"), create a database, allow your database user to use it, then press the button again.',
        );
    }

    // --- Helpers ---

    /**
     * @return list<string>
     */
    private static function hostsToTry(string $host): array
    {
        $host = trim($host) === '' ? 'localhost' : trim($host);

        return match ($host) {
            'localhost' => ['localhost', '127.0.0.1'],
            '127.0.0.1' => ['127.0.0.1', 'localhost'],
            default => [$host],
        };
    }

    /**
     * "phpchess", and "<account>_phpchess" when the user name looks like "account_something".
     *
     * @return list<string>
     */
    private static function namesToCreate(string $user): array
    {
        $names = [self::DEFAULT_NAME];
        $underscore = strpos($user, '_');
        if ($underscore !== false && $underscore > 0) {
            $names[] = substr($user, 0, $underscore) . '_' . self::DEFAULT_NAME;
        }

        return array_values(array_filter($names, DatabaseServer::isSafeDatabaseName(...)));
    }

    /** Turns the server's error into advice a non-expert can follow. */
    private static function explainConnectionError(string $error): string
    {
        if (str_contains($error, 'Access denied')) {
            return 'The database server refused the user name or password. Copy both again from your hosting control panel.';
        }
        if (str_contains($error, 'getaddrinfo') || str_contains($error, 'Unknown') || str_contains($error, 'refused')
            || str_contains($error, 'timed out') || str_contains($error, "Can't connect")) {
            return 'The database server could not be reached. Check the "database server" name in your hosting control panel '
                . '(often "localhost", sometimes a name like "mysql.example.com").';
        }

        return 'Could not connect to the database server: ' . $error;
    }

    /**
     * @param list<string> $choices
     * @return array{status: string, host: string, name: string, choices: list<string>, message: string}
     */
    private static function result(string $status, string $host, string $name, array $choices, string $message): array
    {
        return ['status' => $status, 'host' => $host, 'name' => $name, 'choices' => $choices, 'message' => $message];
    }
}
