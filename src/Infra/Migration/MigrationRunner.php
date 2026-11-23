<?php

declare(strict_types=1);

namespace PhpChess\Infra\Migration;

use PDOException;
use PhpChess\Infra\Database;
use RuntimeException;

/**
 * Runs the migrations: numbered, resumable, prefixed (INS-05, contract K11).
 *
 * Used by the installer (step 5) and, in later versions, by the upgrade page.
 * The runner keeps a list of finished steps in the table "schema_migrations".
 * Each call runs the next steps that aren't in that list, up to a limit, so a
 * shared host's time limit can't leave the schema half done: the installer
 * simply calls again until nothing is pending.
 *
 * K11 is checked here, before any statement runs:
 * - every table a statement names must be written "{name}", so it gets the
 *   install's prefix and can never be someone else's table;
 * - DROP TABLE is allowed only for a table that a phpChess migration creates;
 * - DROP DATABASE is never allowed.
 */
final class MigrationRunner
{
    private const string LOCK_NAME = 'migrations';

    /**
     * Statement parts that name a table; the table must follow in braces.
     * "ON UPDATE" (a column default) and "KEY UPDATE" are not table names.
     * The "?+" makes an optional part (such as IF NOT EXISTS) stick once found,
     * so "CREATE TABLE IF NOT EXISTS {name}" is not misread as a bare "CREATE TABLE".
     */
    private const string TABLE_KEYWORDS =
        '/\b(CREATE\s+TABLE(\s+IF\s+NOT\s+EXISTS)?+|ALTER\s+TABLE|DROP\s+TABLE(\s+IF\s+EXISTS)?+|'
        . 'INSERT\s+(IGNORE\s+)?+INTO|(?<!ON )(?<!KEY )UPDATE|DELETE\s+FROM|TRUNCATE(\s+TABLE)?+|REFERENCES|RENAME\s+TABLE)\s+(?!\{)/i';

    /**
     * @param list<Migration> $migrations  all known migrations, any order
     */
    public function __construct(
        private readonly Database $database,
        private readonly array $migrations,
    ) {
    }

    // --- Loading the migration files ---

    /**
     * Reads every migrations/NNNN_name.php file. Each returns one Migration.
     *
     * @return list<Migration>
     */
    public static function loadFolder(string $folder): array
    {
        $files = glob($folder . '/[0-9][0-9][0-9][0-9]_*.php');
        if ($files === false) {
            return [];
        }
        sort($files);

        $migrations = [];
        foreach ($files as $file) {
            $migration = require $file;
            if (!$migration instanceof Migration) {
                throw new RuntimeException("The file {$file} must return a Migration.");
            }
            $migrations[] = $migration;
        }

        return $migrations;
    }

    // --- What is still to do ---

    /** How many steps have not run yet. */
    public function pendingStepCount(): int
    {
        $this->createTrackingTable();
        $finished = $this->finishedSteps();

        $pending = 0;
        foreach ($this->sortedMigrations() as $migration) {
            foreach (array_keys($migration->steps) as $stepIndex) {
                if (!isset($finished[$this->stepKey($migration->number, $stepIndex + 1)])) {
                    $pending++;
                }
            }
        }

        return $pending;
    }

    // --- Running ---

    /**
     * Runs pending steps in order, at most $maximumSteps of them.
     * Returns the names of the steps run. A failing step throws; the steps
     * before it stay recorded, so the next call resumes at the failed step.
     *
     * @return list<string>
     */
    public function runPending(int $maximumSteps = PHP_INT_MAX): array
    {
        $this->createTrackingTable();

        if (!$this->database->acquireLock(self::LOCK_NAME, 10)) {
            throw new RuntimeException('Another request is running the migrations. Please wait a moment and try again.');
        }

        try {
            return $this->runPendingWhileLocked($maximumSteps);
        } finally {
            $this->database->releaseLock(self::LOCK_NAME);
        }
    }

    /**
     * @return list<string>
     */
    private function runPendingWhileLocked(int $maximumSteps): array
    {
        $finished = $this->finishedSteps();
        $stepsRun = [];

        foreach ($this->sortedMigrations() as $migration) {
            foreach ($migration->steps as $stepIndex => $step) {
                $stepNumber = $stepIndex + 1;
                if (isset($finished[$this->stepKey($migration->number, $stepNumber)])) {
                    continue;
                }
                if (count($stepsRun) >= $maximumSteps) {
                    return $stepsRun;
                }

                $this->runStep($step);
                $this->recordStep($migration->number, $stepNumber, $step->name);
                $stepsRun[] = sprintf('%04d.%d %s', $migration->number, $stepNumber, $step->name);
            }
        }

        return $stepsRun;
    }

    /**
     * Runs one step. When the request died after an ALTER TABLE but before the
     * step was recorded, repeating it fails with "duplicate column" (1060) or
     * "duplicate key" (1061). That only means the step is already done, so it
     * counts as done. (MySQL 8 has no "ADD COLUMN IF NOT EXISTS".)
     */
    private function runStep(MigrationStep $step): void
    {
        try {
            $this->database->run($this->prepareSql($step));
        } catch (PDOException $error) {
            $errorInfo = is_array($error->errorInfo) ? $error->errorInfo : [];
            $driverCode = $errorInfo[1] ?? null;
            if ($driverCode === 1060 || $driverCode === 1061) {
                return;
            }
            throw $error;
        }
    }

    // --- Checking a step (K11) and filling in table names ---

    /** Checks the step against K11 and returns its SQL with prefixed table names. */
    public function prepareSql(MigrationStep $step): string
    {
        $sql = $step->sql;

        if (preg_match('/\bDROP\s+(DATABASE|SCHEMA)\b/i', $sql) === 1) {
            throw new RuntimeException("Migration step \"{$step->name}\" drops a database. That is never allowed.");
        }

        if (preg_match(self::TABLE_KEYWORDS, $sql, $match) === 1) {
            throw new RuntimeException(
                "Migration step \"{$step->name}\" names a table without braces after \"{$match[1]}\". "
                . 'Write phpChess tables as {name}.',
            );
        }

        $createdTables = $this->tablesCreatedByMigrations();
        if (preg_match_all('/\bDROP\s+TABLE\s+(IF\s+EXISTS\s+)?\{([a-z0-9_]+)\}/i', $sql, $drops) > 0) {
            foreach ($drops[2] as $droppedTable) {
                if (!in_array($droppedTable, $createdTables, true)) {
                    throw new RuntimeException(
                        "Migration step \"{$step->name}\" drops {$droppedTable}, which no phpChess migration created.",
                    );
                }
            }
        }

        return (string) preg_replace_callback(
            '/\{([a-z][a-z0-9_]*)\}/',
            fn (array $found): string => '`' . $this->database->table($found[1]) . '`',
            $sql,
        );
    }

    /**
     * The names (without prefix) of every table a known migration creates.
     *
     * @return list<string>
     */
    private function tablesCreatedByMigrations(): array
    {
        $tables = [];
        foreach ($this->migrations as $migration) {
            foreach ($migration->steps as $step) {
                if (preg_match_all('/\bCREATE\s+TABLE\s+(IF\s+NOT\s+EXISTS\s+)?\{([a-z0-9_]+)\}/i', $step->sql, $found) > 0) {
                    foreach ($found[2] as $table) {
                        $tables[] = $table;
                    }
                }
            }
        }

        return $tables;
    }

    // --- The record of finished steps ---

    private function createTrackingTable(): void
    {
        $this->database->run(
            'CREATE TABLE IF NOT EXISTS `' . $this->database->table('schema_migrations') . '` ('
            . ' id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,'
            . ' migration_number INT UNSIGNED NOT NULL,'
            . ' step_number INT UNSIGNED NOT NULL,'
            . ' step_name VARCHAR(190) NOT NULL,'
            . ' applied_at DATETIME(3) NOT NULL,'
            . ' UNIQUE KEY migration_step (migration_number, step_number)'
            . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
        );
    }

    /**
     * @return array<string, true> "number.step" => true for every finished step
     */
    private function finishedSteps(): array
    {
        $rows = $this->database->fetchAll(
            'SELECT migration_number, step_number FROM `' . $this->database->table('schema_migrations') . '`',
        );

        $finished = [];
        foreach ($rows as $row) {
            $migrationNumber = is_numeric($row['migration_number']) ? (int) $row['migration_number'] : 0;
            $stepNumber = is_numeric($row['step_number']) ? (int) $row['step_number'] : 0;
            $finished[$this->stepKey($migrationNumber, $stepNumber)] = true;
        }

        return $finished;
    }

    private function recordStep(int $migrationNumber, int $stepNumber, string $stepName): void
    {
        $this->database->run(
            'INSERT INTO `' . $this->database->table('schema_migrations') . '`'
            . ' (migration_number, step_number, step_name, applied_at) VALUES (?, ?, ?, UTC_TIMESTAMP(3))',
            [$migrationNumber, $stepNumber, $stepName],
        );
    }

    private function stepKey(int $migrationNumber, int $stepNumber): string
    {
        return $migrationNumber . '.' . $stepNumber;
    }

    /**
     * @return list<Migration>
     */
    private function sortedMigrations(): array
    {
        $migrations = $this->migrations;
        usort($migrations, static fn (Migration $first, Migration $second): int => $first->number <=> $second->number);

        return $migrations;
    }
}
