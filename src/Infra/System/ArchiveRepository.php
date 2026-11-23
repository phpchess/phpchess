<?php

declare(strict_types=1);

namespace PhpChess\Infra\System;

use PhpChess\Infra\Repository;
use PDOException;

/**
 * The SQL behind the archive (A42, A43): which old rows go into an archive
 * file, removing them afterwards, reading them back in, and the timed
 * test reads that tell when the database has grown slow.
 *
 * What is archived, all older than a date the admin picks:
 *   games          finished games (running games never), with their
 *                  game_moves, rating_history and game_chat;
 *   messages, notices, audit_log, login_attempts.
 * Only the tables in PARTS can be written or read back, and only columns
 * the table really has (checked against the table), so a changed file
 * can't reach anything else.
 */
final class ArchiveRepository extends Repository
{
    /** Archived tables and the column that dates their rows. Games come first, so restoring puts them back before their moves. */
    public const array PARTS = [
        'games' => 'ended_at',
        'game_moves' => null,
        'rating_history' => null,
        'game_chat' => null,
        'messages' => 'created_at',
        'notices' => 'created_at',
        'audit_log' => 'created_at',
        'login_attempts' => 'attempted_at',
    ];

    /** Tables that belong to a game; they follow their game. */
    private const array GAME_CHILDREN = ['game_moves', 'rating_history', 'game_chat'];

    private const int BATCH = 200;

    // --- Measuring (A43) ---

    /**
     * Runs the typical reads and returns how long each took, in milliseconds.
     *
     * @return array<string, int>
     */
    public function timeTypicalReads(): array
    {
        $reads = [
            'finished_games' => 'SELECT id, white_id, black_id, result FROM ' . $this->table('games')
                . " WHERE state = 'finished' ORDER BY ended_at DESC LIMIT 50",
            'games_count' => 'SELECT COUNT(*) FROM ' . $this->table('games'),
            'messages' => 'SELECT id, body FROM ' . $this->table('messages') . ' ORDER BY created_at DESC LIMIT 50',
            'notices' => 'SELECT COUNT(*) FROM ' . $this->table('notices') . ' WHERE read_at IS NULL',
            'audit_log' => 'SELECT id, action FROM ' . $this->table('audit_log') . ' ORDER BY id DESC LIMIT 200',
        ];
        $times = [];
        foreach ($reads as $name => $sql) {
            $startedAt = microtime(true);
            $this->database->fetchAll($sql);
            $times[$name] = (int) round((microtime(true) - $startedAt) * 1000);
        }

        return $times;
    }

    /**
     * How many rows of each part are older than the date.
     *
     * @return array<string, int>
     */
    public function countOlderThan(string $beforeDate): array
    {
        $counts = [];
        foreach (self::PARTS as $table => $dateColumn) {
            if ($dateColumn === null) {
                continue;
            }
            [$where, $parameters] = self::selection($table, $dateColumn, $beforeDate);
            $counts[$table] = self::intOf($this->database->fetchValue('SELECT COUNT(*) FROM ' . $this->table($table) . ' WHERE ' . $where, $parameters));
        }

        return $counts;
    }

    // --- Writing the rows out ---

    /**
     * Calls $write for every archived row, part by part, in batches: the
     * table name and the row. Returns how many rows were written.
     *
     * @param callable(string, array<string, mixed>): void $write
     */
    public function eachOldRow(string $beforeDate, callable $write): int
    {
        $count = 0;
        foreach (self::PARTS as $table => $dateColumn) {
            if ($dateColumn === null) {
                continue;
            }
            [$where, $parameters] = self::selection($table, $dateColumn, $beforeDate);
            $lastId = 0;
            while (true) {
                $rows = $this->database->fetchAll(
                    'SELECT * FROM ' . $this->table($table) . ' WHERE ' . $where . ' AND id > ? ORDER BY id LIMIT ' . self::BATCH,
                    [...$parameters, $lastId],
                );
                if ($rows === []) {
                    break;
                }
                foreach ($rows as $row) {
                    $write($table, $row);
                    $count++;
                }
                $lastId = self::intOf($rows[count($rows) - 1]['id']);
                if ($table === 'games') {
                    $count += $this->writeGameChildren(array_map(fn (array $row): int => self::intOf($row['id']), $rows), $write);
                }
            }
        }

        return $count;
    }

    /**
     * @param list<int> $gameIds
     * @param callable(string, array<string, mixed>): void $write
     */
    private function writeGameChildren(array $gameIds, callable $write): int
    {
        $count = 0;
        $placeholders = implode(', ', array_fill(0, count($gameIds), '?'));
        foreach (self::GAME_CHILDREN as $table) {
            $rows = $this->database->fetchAll('SELECT * FROM ' . $this->table($table) . ' WHERE game_id IN (' . $placeholders . ')', $gameIds);
            foreach ($rows as $row) {
                $write($table, $row);
                $count++;
            }
        }

        return $count;
    }

    // --- Removing them (only after the admin kept the file) ---

    /** Deletes the archived rows; returns how many top-level rows went. */
    public function deleteOlderThan(string $beforeDate): int
    {
        $count = 0;
        foreach (self::PARTS as $table => $dateColumn) {
            if ($dateColumn === null) {
                continue;
            }
            [$where, $parameters] = self::selection($table, $dateColumn, $beforeDate);
            if ($table === 'games') {
                // Game chat has no link to the games table, so it goes first, by hand.
                $this->database->run(
                    'DELETE c FROM ' . $this->table('game_chat') . ' c JOIN ' . $this->table('games') . ' g ON g.id = c.game_id'
                    . " WHERE g.state = 'finished' AND g.ended_at < ?",
                    [$beforeDate],
                );
            }
            // Moves and rating history follow their game (ON DELETE CASCADE).
            $count += $this->database->run('DELETE FROM ' . $this->table($table) . ' WHERE ' . $where, $parameters)->rowCount();
        }

        return $count;
    }

    // --- Reading them back in ---

    /**
     * Puts one archived row back. Rows already there are left alone.
     * Returns false when the row doesn't fit (unknown table or column, or a
     * player who no longer exists).
     *
     * @param array<string, mixed> $row
     */
    public function restoreRow(string $table, array $row): bool
    {
        if (!array_key_exists($table, self::PARTS) || $row === []) {
            return false;
        }
        $knownColumns = $this->columnsOf($table);
        foreach (array_keys($row) as $column) {
            if (!in_array($column, $knownColumns, true)) {
                return false;
            }
        }
        $columns = array_keys($row);
        $values = array_map(fn (mixed $value): string|int|float|null => is_scalar($value) ? (is_bool($value) ? (int) $value : $value) : null, array_values($row));
        try {
            $this->database->run(
                'INSERT IGNORE INTO ' . $this->table($table) . ' (`' . implode('`, `', $columns) . '`) VALUES ('
                . implode(', ', array_fill(0, count($columns), '?')) . ')',
                $values,
            );
        } catch (PDOException) {
            return false;
        }

        return true;
    }

    /** @var array<string, list<string>> */
    private array $columnCache = [];

    /**
     * The real column names of a table.
     *
     * @return list<string>
     */
    private function columnsOf(string $table): array
    {
        if (!isset($this->columnCache[$table])) {
            $rows = $this->database->fetchAll('SHOW COLUMNS FROM ' . $this->table($table));
            $this->columnCache[$table] = array_map(fn (array $row): string => self::textOf($row['Field']), $rows);
        }

        return $this->columnCache[$table];
    }

    /**
     * The WHERE part that picks a table's archived rows.
     *
     * @return array{0: string, 1: list<string>}
     */
    private static function selection(string $table, string $dateColumn, string $beforeDate): array
    {
        if ($table === 'games') {
            return ["state = 'finished' AND ended_at < ?", [$beforeDate]];
        }

        return [$dateColumn . ' < ?', [$beforeDate]];
    }
}
