<?php

declare(strict_types=1);

namespace PhpChess\Domain\Play;

/**
 * Days-per-move clocks and vacation for correspondence games (G-07, G-08,
 * A28, A29, K12).
 *
 * A correspondence game has one deadline at a time: the moment by which the
 * player to move must have moved. It is the time of the last move (or the
 * start) plus the days per move. Vacation days of the player to move do not
 * count (A29): each vacation day that falls inside the time to move pushes
 * the deadline one day later.
 *
 * These are plain calculations without the database, so they are easy to test.
 */
final class Clocks
{
    public const int SECONDS_PER_DAY = 86400;

    /** The highest days-per-move an admin may allow (A28). */
    public const int HIGHEST_ALLOWED_DAYS = 31;

    /** The default for the admin's maximum days per move (A28). */
    public const int DEFAULT_MAX_DAYS = 30;

    /**
     * The deadline for the player to move.
     *
     * @param int $turnStartedAt  the last move, or the start of the game
     * @param list<string> $vacationDays  the mover's vacation days, "YYYY-MM-DD"
     */
    public static function deadline(int $turnStartedAt, int $daysPerMove, array $vacationDays): int
    {
        $deadline = $turnStartedAt + $daysPerMove * self::SECONDS_PER_DAY;
        $vacation = array_flip($vacationDays);

        // Walk the days from the start of the turn to the deadline; each
        // vacation day on the way moves the deadline (and so the end of the
        // walk) one day further.
        $day = gmdate('Y-m-d', $turnStartedAt);
        while (strtotime($day . ' 00:00:00 UTC') <= $deadline) {
            if (isset($vacation[$day])) {
                $deadline += self::SECONDS_PER_DAY;
            }
            $day = gmdate('Y-m-d', (int) strtotime($day . ' 00:00:00 UTC') + self::SECONDS_PER_DAY);
        }

        return $deadline;
    }

    /** Days per move limited to 1 .. the admin's maximum (A28). */
    public static function limitDays(int $daysPerMove, int $maximumDays): int
    {
        return max(1, min($daysPerMove, max(1, min($maximumDays, self::HIGHEST_ALLOWED_DAYS))));
    }
}
