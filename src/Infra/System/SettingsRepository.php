<?php

declare(strict_types=1);

namespace PhpChess\Infra\System;

use PhpChess\Infra\Repository;

/**
 * The settings table: typed key/value settings of the System module (ARCHITECTURE.md §6).
 *
 * Owns the table "settings". Feature switches are settings too (C1-2).
 * Values are read once per request and kept in memory, so asking for the same
 * setting twice costs one query in total.
 *
 * Setting names used so far:
 * - jobs.pseudo_cron        bool, on by default: page requests run due jobs (INS-08)
 * - jobs.last_tick_at       int, Unix time of the last job run, from cron or a page
 * - jobs.last_cron_at       int, Unix time of the last run by a real cron
 */
final class SettingsRepository extends Repository
{
    /** @var array<string, string>|null all settings, loaded on first use */
    private ?array $cache = null;

    // --- Reading ---

    /** A text setting, or the default when it isn't set. */
    public function getString(string $name, string $default = ''): string
    {
        return $this->all()[$name] ?? $default;
    }

    /** A whole-number setting, or the default when it isn't set. */
    public function getInt(string $name, int $default = 0): int
    {
        $value = $this->all()[$name] ?? null;

        return $value === null ? $default : (int) $value;
    }

    /** A yes/no setting, or the default when it isn't set. */
    public function getBool(string $name, bool $default = false): bool
    {
        $value = $this->all()[$name] ?? null;

        return $value === null ? $default : $value === '1';
    }

    // --- Writing ---

    /** Stores a setting (creating it when new). */
    public function set(string $name, string|int|bool $value): void
    {
        $type = match (true) {
            is_bool($value) => 'bool',
            is_int($value) => 'int',
            default => 'string',
        };
        $text = is_bool($value) ? ($value ? '1' : '0') : (string) $value;

        $this->database->run(
            'INSERT INTO ' . $this->table('settings') . ' (name, value, value_type, updated_at)'
            . ' VALUES (?, ?, ?, UTC_TIMESTAMP(3))'
            . ' ON DUPLICATE KEY UPDATE value = VALUES(value), value_type = VALUES(value_type), updated_at = VALUES(updated_at)',
            [$name, $text, $type],
        );

        if ($this->cache !== null) {
            $this->cache[$name] = $text;
        }
    }

    // --- Loading ---

    /**
     * @return array<string, string>
     */
    private function all(): array
    {
        if ($this->cache === null) {
            $this->cache = [];
            foreach ($this->database->fetchAll('SELECT name, value FROM ' . $this->table('settings')) as $row) {
                $this->cache[(string) $this->text($row['name'])] = $this->text($row['value']);
            }
        }

        return $this->cache;
    }

    private function text(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }
}
