<?php

declare(strict_types=1);

namespace PhpChess\Infra;

/**
 * The base class of every repository (contract K5).
 *
 * A repository is the one place where a module's tables are read and written.
 * Services call repository methods with plain values ("find the setting
 * called X"); the SQL stays here, always as prepared statements with
 * parameters. Each repository names the tables it owns (ARCHITECTURE.md §3).
 */
abstract class Repository
{
    public function __construct(protected readonly Database $database)
    {
    }

    /** The full name of one of this module's tables, with the install's prefix. */
    protected function table(string $name): string
    {
        return $this->database->table($name);
    }

    /** The current time in UTC with milliseconds, as the database stores it (K12). */
    protected function now(): string
    {
        return gmdate('Y-m-d H:i:s') . sprintf('.%03d', (int) (microtime(true) * 1000) % 1000);
    }
}
