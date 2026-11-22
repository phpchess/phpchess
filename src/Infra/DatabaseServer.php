<?php

declare(strict_types=1);

namespace PhpChess\Infra;

use PDO;
use PDOException;

/**
 * A connection to the database *server*, before a database is chosen (INS-02, A23).
 *
 * Used only by the installer's database step, so that nobody needs database
 * skills: it finds which databases the account can see, and creates one when
 * the hosting account is allowed to.
 *
 * Contract K5: database names are never taken from input as they are. A name
 * is accepted only when it passes isSafeDatabaseName() (letters, digits and
 * "_" only), and is then written in backticks. This is the one place in
 * phpChess where an identifier can come from the visitor, and only during
 * installation.
 */
final class DatabaseServer
{
    /** Databases every server has; never offered for phpChess. */
    private const array SYSTEM_DATABASES = ['information_schema', 'mysql', 'performance_schema', 'sys', 'test'];

    private function __construct(private readonly PDO $pdo)
    {
    }

    // --- Connecting ---

    /** Connects to the server without choosing a database. Throws PDOException on failure. */
    public static function connect(string $host, int $port, string $user, string $password): self
    {
        $pdo = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => 5,
        ]);

        return new self($pdo);
    }

    // --- Finding and creating databases ---

    /**
     * The databases this account can see, without the server's own ones.
     *
     * @return list<string>
     */
    public function visibleDatabases(): array
    {
        $statement = $this->pdo->prepare('SHOW DATABASES');
        $statement->execute();

        $names = [];
        foreach ($statement->fetchAll(PDO::FETCH_COLUMN) as $name) {
            if (is_string($name) && !in_array(strtolower($name), self::SYSTEM_DATABASES, true)) {
                $names[] = $name;
            }
        }

        return $names;
    }

    /**
     * Tries to create a database. Returns true when it exists afterwards,
     * false when the account isn't allowed to create databases.
     */
    public function tryCreateDatabase(string $name): bool
    {
        if (!self::isSafeDatabaseName($name)) {
            return false;
        }

        try {
            $statement = $this->pdo->prepare(
                'CREATE DATABASE IF NOT EXISTS `' . $name . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
            );
            $statement->execute();
        } catch (PDOException) {
            return false;
        }

        return in_array($name, $this->visibleDatabases(), true);
    }

    /** True for names made only of letters, digits and "_", 1 to 64 characters. */
    public static function isSafeDatabaseName(string $name): bool
    {
        return preg_match('/^[A-Za-z0-9_]{1,64}$/', $name) === 1;
    }
}
