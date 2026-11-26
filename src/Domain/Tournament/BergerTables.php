<?php

declare(strict_types=1);

namespace PhpChess\Domain\Tournament;

/**
 * The Berger tables for Round Robin (T-02, A100): who plays whom, with which
 * colour, in every round. The League uses them for each division (T-05).
 *
 * Players are given as seed numbers 1..n. With an odd number of players a
 * dummy player n+1 is added; whoever meets the dummy has a bye that round
 * (0 points, not a game, A100). The result is the same as FIDE's published
 * Berger tables (FIDE Handbook C.05, Annex 1), for example for 6 players:
 *
 *   Round 1:  1-6  2-5  3-4
 *   Round 2:  6-4  5-3  1-2
 *   Round 3:  2-6  3-1  4-5
 *
 * How it is built: player n stays in place; the others turn around a circle.
 * In round k (counting from 0) the circle starts at player 1 + k*(n/2),
 * counted around the n-1 others. The first of the circle meets player n
 * (colours alternate by round); the others meet from the outside in.
 *
 * Pure code, no database: tests call it directly.
 */
final class BergerTables
{
    /**
     * All rounds for a number of players.
     *
     * @return list<list<array{0: int, 1: ?int}>> round => boards => [white seed, black seed or null for a bye]
     */
    public static function rounds(int $playerCount, bool $isDoubleRound = false): array
    {
        if ($playerCount < 2) {
            return [];
        }
        $hasDummy = $playerCount % 2 === 1;
        $count = $hasDummy ? $playerCount + 1 : $playerCount;
        $others = $count - 1;
        $half = intdiv($count, 2);

        $rounds = [];
        for ($k = 0; $k < $others; $k++) {
            // --- The circle for this round ---
            $start = ($k * $half) % $others;
            $circle = [];
            for ($i = 0; $i < $others; $i++) {
                $circle[] = (($start + $i) % $others) + 1;
            }

            // --- Board 1: the first of the circle against the fixed player ---
            $boards = [];
            $boards[] = $k % 2 === 0 ? [$circle[0], $count] : [$count, $circle[0]];

            // --- The other boards, from the outside in ---
            for ($j = 1; $j < $half; $j++) {
                $boards[] = [$circle[$j], $circle[$others - $j]];
            }
            $rounds[] = self::withByes($boards, $hasDummy ? $count : 0);
        }

        // --- A double round: the same again with the colours swapped ---
        if ($isDoubleRound) {
            $firstCycle = $rounds;
            foreach ($firstCycle as $boards) {
                $swapped = [];
                foreach ($boards as [$white, $black]) {
                    $swapped[] = $black === null ? [$white, null] : [$black, $white];
                }
                $rounds[] = $swapped;
            }
        }

        return $rounds;
    }

    /**
     * Turns a board against the dummy into a bye for the real player, and
     * puts the bye last so the real boards are numbered 1, 2, 3 ...
     *
     * @param list<array{0: int, 1: int}> $boards
     * @return list<array{0: int, 1: ?int}>
     */
    private static function withByes(array $boards, int $dummy): array
    {
        $real = [];
        $bye = null;
        foreach ($boards as [$white, $black]) {
            if ($dummy !== 0 && ($white === $dummy || $black === $dummy)) {
                $bye = [$white === $dummy ? $black : $white, null];
                continue;
            }
            $real[] = [$white, $black];
        }
        if ($bye !== null) {
            $real[] = $bye;
        }

        return $real;
    }
}
