<?php

declare(strict_types=1);

namespace PhpChess\Domain\Tournament;

/**
 * Tie-breaks and standings (T-06, T-07, A101; FIDE Handbook C.07, 2023).
 *
 * The tie-breaks, by their key:
 * - buchholz_cut1:      Buchholz without the lowest opponent score;
 * - buchholz:           the sum of the final scores of every opponent;
 * - sonneborn_berger:   the sum of the scores of the opponents beaten, plus
 *                       half the scores of the opponents drawn;
 * - direct_encounter:   among players with the same points who have all met
 *                       each other: the points scored in those games;
 * - wins:               the number of games won on the board;
 * - koya:               the points scored against the players who finished
 *                       with at least half the possible points (Round Robin).
 *
 * Unplayed games (byes and forfeits) count, as in FIDE's 2023 rules, as a
 * game against a virtual opponent who has the player's own final score.
 *
 * The standings sort by points, then by the tie-breaks in the organiser's
 * order. Players still level share a rank. Pure code, no database.
 */
final class TieBreaks
{
    public const string BUCHHOLZ_CUT1 = 'buchholz_cut1';
    public const string BUCHHOLZ = 'buchholz';
    public const string SONNEBORN_BERGER = 'sonneborn_berger';
    public const string DIRECT_ENCOUNTER = 'direct_encounter';
    public const string WINS = 'wins';
    public const string KOYA = 'koya';
    public const array ALL = [self::BUCHHOLZ_CUT1, self::BUCHHOLZ, self::SONNEBORN_BERGER, self::DIRECT_ENCOUNTER, self::WINS, self::KOYA];

    /**
     * The default order per format (A101). Knockout has none.
     *
     * @return list<string>
     */
    public static function defaultsFor(string $format): array
    {
        return match ($format) {
            Tournament::FORMAT_ROUND_ROBIN => [self::DIRECT_ENCOUNTER, self::SONNEBORN_BERGER, self::WINS, self::KOYA],
            Tournament::FORMAT_SWISS => [self::BUCHHOLZ_CUT1, self::BUCHHOLZ, self::SONNEBORN_BERGER, self::DIRECT_ENCOUNTER, self::WINS],
            Tournament::FORMAT_LEAGUE => [self::DIRECT_ENCOUNTER, self::SONNEBORN_BERGER],
            default => [],
        };
    }

    /**
     * The standings of a group of players (a whole tournament, or one
     * League division).
     *
     * @param list<TournamentPlayer> $players
     * @param list<Pairing> $pairings  all pairings of these players; only those that count are used
     * @param list<string> $order      tie-break keys, most important first
     * @return list<StandingRow>       best first, with rank
     */
    public static function standings(array $players, array $pairings, array $order): array
    {
        $counting = array_values(array_filter($pairings, fn (Pairing $pairing): bool => $pairing->counts()));

        // --- Points first ---
        $points = [];
        foreach ($players as $player) {
            $points[$player->userId] = 0;
        }
        foreach ($counting as $pairing) {
            if (isset($points[$pairing->whiteId])) {
                $points[$pairing->whiteId] += $pairing->whiteHalves;
            }
            if ($pairing->blackId !== null && isset($points[$pairing->blackId])) {
                $points[$pairing->blackId] += $pairing->blackHalves;
            }
        }

        // --- Then every tie-break ---
        $values = [];
        foreach ($players as $player) {
            $values[$player->userId] = [];
            foreach ($order as $key) {
                $values[$player->userId][$key] = $key === self::DIRECT_ENCOUNTER
                    ? 0.0
                    : self::value($key, $player->userId, $counting, $points);
            }
        }
        if (in_array(self::DIRECT_ENCOUNTER, $order, true)) {
            foreach (self::directEncounters($points, $counting) as $userId => $value) {
                $values[$userId][self::DIRECT_ENCOUNTER] = $value;
            }
        }

        // --- Sort and give ranks ---
        $rows = [];
        foreach ($players as $player) {
            $rows[] = new StandingRow($player, $points[$player->userId], $values[$player->userId], self::gamesOf($player->userId, $counting));
        }
        usort($rows, function (StandingRow $a, StandingRow $b) use ($order): int {
            $comparison = self::compare($a, $b, $order);

            return $comparison !== 0 ? $comparison : $a->player->seed <=> $b->player->seed;
        });

        $ranked = [];
        foreach ($rows as $index => $row) {
            $sharesRank = $index > 0 && self::compare($rows[$index - 1], $row, $order) === 0;
            $rank = $sharesRank ? $ranked[$index - 1]->rank : $index + 1;
            $ranked[] = $row->withRank($rank);
        }

        return $ranked;
    }

    /**
     * Compares two rows: more points first, then each tie-break in order.
     *
     * @param list<string> $order
     */
    private static function compare(StandingRow $a, StandingRow $b, array $order): int
    {
        if ($a->halves !== $b->halves) {
            return $b->halves <=> $a->halves;
        }
        foreach ($order as $key) {
            $difference = ($b->tieBreaks[$key] ?? 0.0) <=> ($a->tieBreaks[$key] ?? 0.0);
            if ($difference !== 0) {
                return $difference;
            }
        }

        return 0;
    }

    // --- The tie-breaks one by one ---

    /**
     * One tie-break value (all but direct encounter), in points (not halves).
     *
     * @param list<Pairing> $counting
     * @param array<int, int> $points  user id => halves
     */
    public static function value(string $key, int $userId, array $counting, array $points): float
    {
        $ownPoints = ($points[$userId] ?? 0) / 2;
        $opponentScores = [];
        $sonnebornBerger = 0.0;
        $wins = 0;
        $koya = 0.0;
        $roundsPlayed = self::roundsOf($counting);
        $koyaLine = $roundsPlayed;   // half of the possible points (in halves: rounds * 2 / 2)

        foreach ($counting as $pairing) {
            if (!$pairing->involves($userId)) {
                continue;
            }
            $scored = $pairing->halvesOf($userId) / 2;
            $opponentId = $pairing->opponentOf($userId);
            // Unplayed: a virtual opponent with the player's own score (FIDE 2023).
            $opponentScore = $pairing->wasPlayed() && $opponentId !== null ? ($points[$opponentId] ?? 0) / 2 : $ownPoints;
            $opponentScores[] = $opponentScore;
            $sonnebornBerger += $opponentScore * $scored;
            if ($pairing->wasPlayed() && $pairing->halvesOf($userId) === 2) {
                $wins++;
            }
            if ($pairing->wasPlayed() && $opponentId !== null && ($points[$opponentId] ?? 0) >= $koyaLine) {
                $koya += $scored;
            }
        }

        return match ($key) {
            self::BUCHHOLZ => array_sum($opponentScores),
            self::BUCHHOLZ_CUT1 => $opponentScores === [] ? 0.0 : array_sum($opponentScores) - min($opponentScores),
            self::SONNEBORN_BERGER => $sonnebornBerger,
            self::WINS => (float) $wins,
            self::KOYA => $koya,
            default => 0.0,
        };
    }

    /**
     * Direct encounter: for every group of players with the same points
     * who have all played each other, the points each scored in those games.
     * A group that has not all met gets 0 for everyone (no advantage).
     *
     * @param array<int, int> $points
     * @param list<Pairing> $counting
     * @return array<int, float>
     */
    private static function directEncounters(array $points, array $counting): array
    {
        $groups = [];
        foreach ($points as $userId => $halves) {
            $groups[$halves][] = $userId;
        }
        $values = [];
        foreach ($groups as $members) {
            if (count($members) < 2) {
                continue;
            }
            $scores = array_fill_keys($members, 0.0);
            $met = [];
            foreach ($counting as $pairing) {
                if ($pairing->blackId === null || !in_array($pairing->whiteId, $members, true) || !in_array($pairing->blackId, $members, true)) {
                    continue;
                }
                $scores[$pairing->whiteId] += $pairing->whiteHalves / 2;
                $scores[$pairing->blackId] += $pairing->blackHalves / 2;
                $met[min($pairing->whiteId, $pairing->blackId) . '-' . max($pairing->whiteId, $pairing->blackId)] = true;
            }
            $pairsNeeded = count($members) * (count($members) - 1) / 2;
            if (count($met) < $pairsNeeded) {
                continue;
            }
            foreach ($scores as $userId => $score) {
                $values[$userId] = $score;
            }
        }

        return $values;
    }

    /**
     * @param list<Pairing> $counting
     */
    private static function gamesOf(int $userId, array $counting): int
    {
        $count = 0;
        foreach ($counting as $pairing) {
            if ($pairing->involves($userId) && !$pairing->isBye()) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * The number of rounds with at least one counted board.
     *
     * @param list<Pairing> $counting
     */
    private static function roundsOf(array $counting): int
    {
        $rounds = [];
        foreach ($counting as $pairing) {
            $rounds[$pairing->round] = true;
        }

        return count($rounds);
    }
}
