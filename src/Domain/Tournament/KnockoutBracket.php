<?php

declare(strict_types=1);

namespace PhpChess\Domain\Tournament;

/**
 * The knockout bracket (T-04, A100): seeded by rating, filled up with byes
 * to a power of 2, so the strongest players get the byes and only meet late.
 *
 * Example for 6 players: the bracket has 8 places, so seeds 1 and 2 have a
 * bye in round 1:
 *
 *   match 1: 1 - (bye)     match 2: 4 - 5
 *   match 3: 2 - (bye)     match 4: 3 - 6
 *
 * Winners of matches 1 and 2 meet in round 2, then 3 and 4, and so on.
 * Who wins a match (games, armageddon) is decided by TournamentRounds;
 * this class only knows the shape of the bracket. Pure code, no database.
 */
final class KnockoutBracket
{
    /** The bracket size for a number of players: the next power of 2. */
    public static function size(int $playerCount): int
    {
        $size = 2;
        while ($size < $playerCount) {
            $size *= 2;
        }

        return $size;
    }

    /** The number of rounds: 8 players -> 3 rounds (quarter-final, semi-final, final). */
    public static function roundCount(int $playerCount): int
    {
        return (int) log(self::size($playerCount), 2);
    }

    /**
     * The first-round matches as seed pairs, in bracket order. A missing
     * seed (beyond the number of players) is a bye: null.
     *
     * @return list<array{0: int, 1: ?int}>
     */
    public static function firstRound(int $playerCount): array
    {
        $size = self::size($playerCount);
        $order = self::seedOrder($size);
        $matches = [];
        for ($i = 0; $i < $size; $i += 2) {
            $top = $order[$i];
            $bottom = $order[$i + 1];
            $matches[] = [$top, $bottom > $playerCount ? null : $bottom];
        }

        return $matches;
    }

    /**
     * The order of seeds down the bracket, so that 1 and 2 can only meet in
     * the final: size 4 -> 1, 4, 2, 3; size 8 -> 1, 8, 4, 5, 2, 7, 3, 6.
     *
     * Built by doubling: each seed s of the smaller bracket is followed by
     * its opponent (new size + 1 - s).
     *
     * @return list<int>
     */
    public static function seedOrder(int $size): array
    {
        $order = [1, 2];
        $current = 2;
        while ($current < $size) {
            $current *= 2;
            $next = [];
            foreach ($order as $seed) {
                $next[] = $seed;
                $next[] = $current + 1 - $seed;
            }
            $order = $next;
        }

        return $order;
    }

    /** The round names shown on the page: "final", "semi_final", "quarter_final" or "round". */
    public static function roundName(int $round, int $playerCount): string
    {
        $fromTheEnd = self::roundCount($playerCount) - $round;

        return match ($fromTheEnd) {
            0 => 'final',
            1 => 'semi_final',
            2 => 'quarter_final',
            default => 'round',
        };
    }
}
