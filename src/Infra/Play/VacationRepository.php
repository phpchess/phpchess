<?php

declare(strict_types=1);

namespace PhpChess\Infra\Play;

use PhpChess\Infra\Repository;

/**
 * The vacation_days table (G-08, A29).
 *
 * Owns the table "vacation_days": one row per whole day a player is on vacation.
 */
final class VacationRepository extends Repository
{
    /**
     * A player's vacation days from a date on, "YYYY-MM-DD", oldest first.
     *
     * @return list<string>
     */
    public function daysFrom(int $userId, string $fromDay): array
    {
        $rows = $this->database->fetchAll(
            'SELECT day FROM ' . $this->table('vacation_days') . ' WHERE user_id = ? AND day >= ? ORDER BY day',
            [$userId, $fromDay],
        );

        return array_map(fn (array $row): string => self::textOf($row['day']), $rows);
    }

    /** How many vacation days the player has in a calendar year. */
    public function countInYear(int $userId, int $year): int
    {
        return self::intOf($this->database->fetchValue(
            'SELECT COUNT(*) FROM ' . $this->table('vacation_days') . ' WHERE user_id = ? AND day BETWEEN ? AND ?',
            [$userId, sprintf('%04d-01-01', $year), sprintf('%04d-12-31', $year)],
        ));
    }

    /** Adds one day; a day already taken is kept once. */
    public function addDay(int $userId, string $day): void
    {
        $this->database->run('INSERT IGNORE INTO ' . $this->table('vacation_days') . ' (user_id, day) VALUES (?, ?)', [$userId, $day]);
    }

    /** Removes the player's vacation days from a date on (cancelling a planned vacation). */
    public function removeFrom(int $userId, string $fromDay): void
    {
        $this->database->run('DELETE FROM ' . $this->table('vacation_days') . ' WHERE user_id = ? AND day >= ?', [$userId, $fromDay]);
    }
}
