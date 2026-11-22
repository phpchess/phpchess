<?php

declare(strict_types=1);

namespace PhpChess\Infra;

use PDO;
use PDOStatement;
use PhpChess\Kernel\Config;
use RuntimeException;
use Throwable;

/**
 * The database connection, on PDO (ARCHITECTURE.md §6, contract K5).
 *
 * Only code in src/Infra/ (the repositories) and the migrations use this class.
 * Every statement is prepared and every value is passed as a parameter, never
 * written into the SQL text. Table names come from code only, through table(),
 * which adds the install's prefix (for example "phpc_settings").
 *
 * The connection sets its own strict sql_mode and UTC time zone, so phpChess
 * behaves the same whatever the server's defaults are.
 */
final class Database
{
    public const string STRICT_SQL_MODE =
        'STRICT_ALL_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

    public function __construct(
        private readonly PDO $pdo,
        public readonly string $tablePrefix,
    ) {
    }

    // --- Connecting ---

    /** Opens a connection with the details from the config. Throws PDOException on failure. */
    public static function connect(Config $config): self
    {
        $dataSourceName = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $config->databaseHost,
            $config->databasePort,
            $config->databaseName,
        );

        $pdo = new PDO($dataSourceName, $config->databaseUser, $config->databasePassword, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // Real prepared statements on the server, not emulated by PDO.
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
        ]);
        $pdo->exec("SET SESSION sql_mode = '" . self::STRICT_SQL_MODE . "', time_zone = '+00:00'");

        return new self($pdo, $config->tablePrefix);
    }

    // --- Table names ---

    /**
     * The full name of one of phpChess's tables, with the prefix.
     * The name must come from code, never from input.
     */
    public function table(string $name): string
    {
        if (preg_match('/^[a-z][a-z0-9_]*$/', $name) !== 1) {
            throw new RuntimeException("Not a table name: {$name}");
        }

        return $this->tablePrefix . $name;
    }

    // --- Running statements ---

    /**
     * Prepares and runs one statement with its parameters.
     *
     * @param array<string|int, string|int|float|bool|null> $parameters
     */
    public function run(string $sql, array $parameters = []): PDOStatement
    {
        $statement = $this->pdo->prepare($sql);
        foreach ($parameters as $name => $value) {
            $placeholder = is_int($name) ? $name + 1 : ':' . ltrim($name, ':');
            $statement->bindValue($placeholder, $value, self::parameterType($value));
        }
        $statement->execute();

        return $statement;
    }

    /**
     * All rows of a SELECT.
     *
     * @param array<string|int, string|int|float|bool|null> $parameters
     * @return list<array<string, mixed>>
     */
    public function fetchAll(string $sql, array $parameters = []): array
    {
        /** @var list<array<string, mixed>> $rows */
        $rows = $this->run($sql, $parameters)->fetchAll(PDO::FETCH_ASSOC);

        return $rows;
    }

    /**
     * The first row of a SELECT, or null when there is none.
     *
     * @param array<string|int, string|int|float|bool|null> $parameters
     * @return array<string, mixed>|null
     */
    public function fetchRow(string $sql, array $parameters = []): ?array
    {
        $row = $this->run($sql, $parameters)->fetch(PDO::FETCH_ASSOC);
        if (!is_array($row)) {
            return null;
        }

        /** @var array<string, mixed> $row */
        return $row;
    }

    /**
     * The first column of the first row, or null when there is no row.
     *
     * @param array<string|int, string|int|float|bool|null> $parameters
     */
    public function fetchValue(string $sql, array $parameters = []): mixed
    {
        $value = $this->run($sql, $parameters)->fetchColumn();

        return $value === false ? null : $value;
    }

    /** The id given to the last inserted row. */
    public function lastInsertId(): int
    {
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Runs the work in one transaction: all of it is saved, or none of it.
     *
     * @template T
     * @param callable(): T $work
     * @return T
     */
    public function transaction(callable $work): mixed
    {
        $this->pdo->beginTransaction();
        try {
            $result = $work();
            $this->pdo->commit();

            return $result;
        } catch (Throwable $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $error;
        }
    }

    // --- Named locks (used by the job runner and the migration runner) ---

    /**
     * Takes a named lock for this connection (MariaDB/MySQL GET_LOCK).
     * Waits at most $waitSeconds; returns false when another connection holds it.
     * The lock is released by releaseLock() or when the connection closes.
     */
    public function acquireLock(string $name, int $waitSeconds = 0): bool
    {
        $result = $this->fetchValue('SELECT GET_LOCK(?, ?)', [$this->tablePrefix . $name, $waitSeconds]);

        return is_numeric($result) && (int) $result === 1;
    }

    /** Releases a lock taken with acquireLock(). */
    public function releaseLock(string $name): void
    {
        $this->run('SELECT RELEASE_LOCK(?)', [$this->tablePrefix . $name]);
    }

    // --- About the server ---

    /** The server's version text, for example "10.11.8-MariaDB". */
    public function serverVersion(): string
    {
        $version = $this->pdo->getAttribute(PDO::ATTR_SERVER_VERSION);

        return is_string($version) ? $version : '';
    }

    /** The sql_mode of this connection, for the installer's strict-mode check (INS-03). */
    public function sessionSqlMode(): string
    {
        $mode = $this->fetchValue('SELECT @@SESSION.sql_mode');

        return is_string($mode) ? $mode : '';
    }

    /** True when a table with this full name (prefix included) exists. */
    public function tableExists(string $fullName): bool
    {
        $count = $this->fetchValue(
            'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?',
            [$fullName],
        );

        return is_numeric($count) && (int) $count > 0;
    }

    private static function parameterType(string|int|float|bool|null $value): int
    {
        if ($value === null) {
            return PDO::PARAM_NULL;
        }
        if (is_int($value)) {
            return PDO::PARAM_INT;
        }
        if (is_bool($value)) {
            return PDO::PARAM_BOOL;
        }

        return PDO::PARAM_STR;
    }
}
