<?php

declare(strict_types=1);

namespace PhpChess\Http\Web\Tournament;

use PhpChess\Domain\Tournament\KnockoutBracket;
use PhpChess\Domain\Tournament\Pairing;
use PhpChess\Domain\Tournament\TieBreaks;
use PhpChess\Domain\Tournament\Tournament;
use PhpChess\Domain\Tournament\TournamentPlayer;
use PhpChess\Domain\Tournament\TournamentRounds;
use PhpChess\Kernel\Services;

/**
 * Prepares what the tournament page shows (T-07): standings with
 * tie-breaks, the rounds with their boards, the crosstable, and the
 * knockout bracket. Plain arrays for the templates; no HTML here.
 *
 * Used by TournamentsController::show() and the manage page.
 */
final class TournamentView
{
    public function __construct(private readonly Services $services)
    {
    }

    /**
     * Everything the page needs about one tournament.
     *
     * @return array<string, mixed>
     */
    public function of(Tournament $tournament, int $viewerId): array
    {
        $repository = $this->services->tournaments()->repository();
        $rounds = $this->services->tournaments()->rounds();
        $players = $repository->players($tournament->id);
        $pairings = $repository->pairings($tournament->id);

        $log = $repository->logOf($tournament->id, 100);

        // --- Names: players, anyone who left a board behind (a replaced player), and the organisers in the log ---
        $names = [];
        foreach ($players as $player) {
            $names[$player->userId] = $player->username;
        }
        $missing = [];
        foreach ($pairings as $pairing) {
            foreach ([$pairing->whiteId, $pairing->blackId] as $userId) {
                if ($userId !== null && !isset($names[$userId])) {
                    $missing[] = $userId;
                }
            }
        }
        foreach ($log as $line) {
            if ($line['userId'] !== null && !isset($names[$line['userId']])) {
                $missing[] = $line['userId'];
            }
        }
        $names += $this->services->users()->usernamesOf($missing);

        // --- Standings per division (one group for formats without divisions) ---
        $standings = [];
        $crosstables = [];
        foreach ($rounds->divisionsOf($players) as $division => $divisionPlayers) {
            if ($tournament->format === Tournament::FORMAT_LEAGUE && $division === 0) {
                continue;
            }
            $divisionPairings = array_values(array_filter($pairings, fn (Pairing $pairing): bool => $pairing->division === $division));
            $standings[$division] = TieBreaks::standings($divisionPlayers, $divisionPairings, $tournament->tieBreaks);
            if ($tournament->hasFixedTables()) {
                $crosstables[$division] = self::crosstable($divisionPlayers, $divisionPairings);
            }
        }

        // --- The rounds and their boards ---
        $boardsByRound = [];
        foreach ($pairings as $pairing) {
            $boardsByRound[$pairing->round][] = $pairing;
        }
        $roundRows = $repository->rounds($tournament->id);

        // --- My board in the current round ---
        $myBoards = array_values(array_filter($pairings, fn (Pairing $pairing): bool => $pairing->involves($viewerId)
            && in_array($pairing->state, [Pairing::STATE_PLAYING, Pairing::STATE_WAITING, Pairing::STATE_POSTPONED], true)));

        return [
            'players' => $players,
            'names' => $names,
            'standings' => $standings,
            'crosstables' => $crosstables,
            'swissTable' => $tournament->format === Tournament::FORMAT_SWISS ? self::roundByRound($standings[0] ?? [], $pairings) : [],
            'knockout' => $tournament->format === Tournament::FORMAT_KNOCKOUT ? $this->knockout($tournament, $roundRows, $players) : [],
            'boardsByRound' => $boardsByRound,
            'rounds' => $roundRows,
            'myBoards' => $myBoards,
            'absences' => $repository->absences($tournament->id),
            'log' => $log,
            'organiserIds' => $repository->organiserIds($tournament->id),
            'isPlayer' => in_array($viewerId, array_map(fn (TournamentPlayer $player): int => $player->userId, $players), true),
            'myState' => self::stateOf($players, $viewerId),
            'firstOpenRound' => $this->services->tournaments()->desk()->firstOpenRound($tournament),
        ];
    }

    /**
     * The Round Robin crosstable of one group: for each pair of players the
     * scores from the row player's point of view ("1", "½", "0", "+", "-").
     *
     * @param list<TournamentPlayer> $players
     * @param list<Pairing> $pairings
     * @return array<int, array<int, list<string>>>  row user id => column user id => scores
     */
    public static function crosstable(array $players, array $pairings): array
    {
        $table = [];
        foreach ($players as $row) {
            foreach ($players as $column) {
                $table[$row->userId][$column->userId] = [];
            }
        }
        foreach ($pairings as $pairing) {
            if (!$pairing->counts() || $pairing->blackId === null || !isset($table[$pairing->whiteId][$pairing->blackId])) {
                continue;
            }
            $table[$pairing->whiteId][$pairing->blackId][] = self::scoreFor($pairing, $pairing->whiteId);
            $table[$pairing->blackId][$pairing->whiteId][] = self::scoreFor($pairing, $pairing->blackId);
        }

        return $table;
    }

    /** One score from a player's point of view: "1", "½", "0", or "+" / "-" for a forfeit. */
    public static function scoreFor(Pairing $pairing, int $userId): string
    {
        $halves = $pairing->halvesOf($userId);
        if (!$pairing->wasPlayed() && in_array($pairing->outcome, [Pairing::OUTCOME_WHITE_FORFEIT_WIN, Pairing::OUTCOME_BLACK_FORFEIT_WIN, Pairing::OUTCOME_DOUBLE_FORFEIT], true)) {
            return $halves === 2 ? '+' : '-';
        }

        return Pairing::halvesText($halves);
    }

    /**
     * The Swiss table: for each player (in standings order) and each round,
     * the opponent's place, the colour and the score, like "5w1".
     *
     * @param list<\PhpChess\Domain\Tournament\StandingRow> $standings
     * @param list<Pairing> $pairings
     * @return array<int, array<int, string>>  user id => round => cell text
     */
    public static function roundByRound(array $standings, array $pairings): array
    {
        $placeOf = [];
        foreach ($standings as $index => $row) {
            $placeOf[$row->player->userId] = $index + 1;
        }
        $table = [];
        foreach ($standings as $row) {
            $table[$row->player->userId] = [];
        }
        foreach ($pairings as $pairing) {
            foreach ([$pairing->whiteId, $pairing->blackId] as $userId) {
                if ($userId === null || !isset($table[$userId]) || !$pairing->counts()) {
                    continue;
                }
                $opponentId = $pairing->opponentOf($userId);
                $cell = $opponentId === null
                    ? ($pairing->outcome === Pairing::OUTCOME_ABSENCE_BYE ? '–' : 'bye') . ' ' . Pairing::halvesText($pairing->halvesOf($userId))
                    : ($placeOf[$opponentId] ?? '?') . $pairing->colourOf($userId) . self::scoreFor($pairing, $userId);
                $table[$userId][$pairing->round] = $cell;
            }
        }

        return $table;
    }

    /**
     * The knockout bracket: each round with its matches, winners marked.
     *
     * @param list<array{number: int, state: string, startedAt: ?int, deadlineAt: ?int, closedAt: ?int}> $roundRows
     * @param list<TournamentPlayer> $players
     * @return list<array{round: int, name: string, matches: list<array{match: int, decided: bool, winner: ?int, loser: ?int, needs: string}>}>
     */
    private function knockout(Tournament $tournament, array $roundRows, array $players): array
    {
        $seedOf = [];
        foreach ($players as $player) {
            $seedOf[$player->userId] = $player->seed;
        }
        $bracket = [];
        foreach ($roundRows as $round) {
            $bracket[] = [
                'round' => $round['number'],
                'name' => KnockoutBracket::roundName($round['number'], count($players)),
                'matches' => $this->services->tournaments()->rounds()->knockoutResults($tournament, $round['number'], $seedOf),
            ];
        }

        return $bracket;
    }

    /**
     * @param list<TournamentPlayer> $players
     */
    private static function stateOf(array $players, int $userId): string
    {
        foreach ($players as $player) {
            if ($player->userId === $userId) {
                return $player->state;
            }
        }

        return '';
    }

    /** The forfeit rule as a language key, for the visible rule box (A102). */
    public static function forfeitRuleKey(string $rule): string
    {
        return in_array($rule, TournamentRounds::FORFEIT_RULES, true) ? 'tournament.forfeit.' . $rule : 'tournament.forfeit.loss_on_time';
    }
}
