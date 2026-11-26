<?php

declare(strict_types=1);

namespace PhpChess\Domain\Tournament;

use PhpChess\Domain\ActionRefused;
use PhpChess\Domain\Chess\Position;
use PhpChess\Domain\Identity\User;
use PhpChess\Domain\Play\Game;
use PhpChess\Domain\Play\Games;
use PhpChess\Domain\Play\PlayRefused;
use PhpChess\Domain\Play\TimeControl;
use PhpChess\Domain\Rating\Ratings;
use PhpChess\Infra\Database;
use PhpChess\Infra\Play\GameRepository;
use PhpChess\Infra\Tournament\TournamentRepository;

/**
 * Running a tournament round by round (T-02 to T-05, T-08, A99 to A104).
 *
 * The life of a round:
 *
 *   paired  -> running -> closed
 *
 * 1. **Pairing**: the boards of the next round are made for the format:
 *    Berger tables (Round Robin, each League division), the Dutch system
 *    (Swiss) or the bracket (knockout). Byes are decided at once.
 * 2. **Start**: every board gets a real game (Games::startForTournament).
 *    When the organiser wants to check the pairings first (confirm
 *    pairings), the round waits as "paired" and may be paired again.
 * 3. **Deadline** (correspondence): a board still running at its deadline
 *    is decided by the tournament's forfeit rule, shown on its page (A102),
 *    through Games::end (K3) and unrated. Timed games end by their clocks.
 * 4. **Close**: when every board is decided the round closes and the next
 *    one is paired; after the last round the tournament is finished.
 *
 * tick() does whatever is due. The job runner calls it every minute, and
 * every tournament page calls it too, so nothing waits for the job runner
 * (A104). A named database lock per tournament makes sure two requests
 * never pair the same round twice.
 *
 * Absent players (A108): in Swiss they get an absence bye; in Round Robin
 * and League their board is postponed and starts when they are back.
 */
final class TournamentRounds
{
    // --- The forfeit rules for a board still running at the deadline (A102) ---
    /** The player to move loses (the default). */
    public const string FORFEIT_LOSS_ON_TIME = 'loss_on_time';
    /** The game is scored as a draw. */
    public const string FORFEIT_DRAW = 'draw';
    /** The organiser decides each board; the round stays open until then. */
    public const string FORFEIT_ORGANISER = 'organiser';
    public const array FORFEIT_RULES = [self::FORFEIT_LOSS_ON_TIME, self::FORFEIT_DRAW, self::FORFEIT_ORGANISER];

    /** How long tick() waits for another request working on the same tournament. */
    private const int LOCK_WAIT_SECONDS = 5;

    /** Steps tick() takes at most at once (close, pair, start ...), so a page stays quick. */
    private const int MOST_STEPS_PER_TICK = 4;

    public function __construct(
        private readonly Database $database,
        private readonly TournamentRepository $tournaments,
        private readonly Games $games,
        private readonly GameRepository $gameRecords,
        private readonly Ratings $ratings,
        private readonly TournamentAccess $access,
    ) {
    }

    // --- Starting the tournament ---

    /**
     * Starts the tournament: seeds the players by rating (League: as placed
     * in the editor), checks there are enough of them, and pairs round 1.
     */
    public function start(Tournament $tournament, User $manager, int $now): void
    {
        if (!$this->access->mayManage($tournament, $manager)) {
            throw new ActionRefused('tournament.not_allowed');
        }
        if (!$tournament->isOpenForRegistration()) {
            throw new ActionRefused('tournament.already_started');
        }
        $players = array_values(array_filter($this->tournaments->players($tournament->id), fn (TournamentPlayer $player): bool => $player->isActive()));
        if (count($players) < 2) {
            throw new ActionRefused('tournament.too_few_players');
        }
        $this->checkClassRules($players);
        $this->seedPlayers($tournament, $players);

        if ($tournament->format === Tournament::FORMAT_LEAGUE) {
            foreach ($this->divisionsOf($this->tournaments->players($tournament->id)) as $divisionPlayers) {
                if (count($divisionPlayers) < 2) {
                    throw new ActionRefused('tournament.division_too_small');
                }
            }
        }
        if ($tournament->format === Tournament::FORMAT_SWISS && $tournament->roundsPlanned === 0) {
            $rounds = min(count($players) - 1, (int) ceil(log(count($players), 2)) + 1);
            $this->tournaments->updateSettings($tournament->id, ['rounds_planned' => max(1, $rounds)]);
        }

        $this->tournaments->setStatus($tournament->id, Tournament::STATUS_RUNNING, $now);
        $this->tournaments->log($tournament->id, $manager->id, 'started', count($players) . ' players', $now);
        $this->tick($tournament->id, $now);
    }

    /**
     * Seeds by rating, strongest first (A100). In a League the organiser's
     * places stay; players without a division are put in, strongest at the top.
     *
     * @param list<TournamentPlayer> $players
     */
    private function seedPlayers(Tournament $tournament, array $players): void
    {
        $speed = Game::SPEED_CORRESPONDENCE;
        if ($tournament->isTimed()) {
            $control = TimeControl::fromText($tournament->timeControl);
            $speed = $control === null ? $speed : $control->speed();
        }
        $ratings = [];
        foreach ($players as $player) {
            $ratings[$player->userId] = $this->ratings->shownRating($player->userId, $speed)['rating'];
            $this->tournaments->setRating($tournament->id, $player->userId, $ratings[$player->userId]);
        }

        if ($tournament->format !== Tournament::FORMAT_LEAGUE) {
            usort($players, fn (TournamentPlayer $a, TournamentPlayer $b): int => [$ratings[$b->userId], $a->seed] <=> [$ratings[$a->userId], $b->seed]);
            foreach ($players as $index => $player) {
                $this->tournaments->setSeedAndDivision($tournament->id, $player->userId, $index + 1, 0);
            }

            return;
        }

        // --- League: keep the editor's places, fill in the rest by rating ---
        $placed = array_values(array_filter($players, fn (TournamentPlayer $player): bool => $player->division > 0));
        $unplaced = array_values(array_filter($players, fn (TournamentPlayer $player): bool => $player->division === 0));
        usort($unplaced, fn (TournamentPlayer $a, TournamentPlayer $b): int => $ratings[$b->userId] <=> $ratings[$a->userId]);
        $divisionCount = max(1, $tournament->divisions);
        $perDivision = (int) ceil(count($players) / $divisionCount);
        $sizes = array_fill(1, $divisionCount, 0);
        foreach ($placed as $player) {
            $sizes[min($divisionCount, $player->division)]++;
        }
        $newDivision = [];
        foreach ($unplaced as $player) {
            $division = 1;
            while ($division < $divisionCount && $sizes[$division] >= $perDivision) {
                $division++;
            }
            $sizes[$division]++;
            $newDivision[$player->userId] = $division;
        }
        foreach ($this->divisionsOf($players, $newDivision) as $division => $divisionPlayers) {
            foreach ($divisionPlayers as $index => $player) {
                $this->tournaments->setSeedAndDivision($tournament->id, $player->userId, $index + 1, $division);
            }
        }
    }

    /**
     * Refuses to start when two players may not play each other under the
     * school's class rule (SCH-05), naming them, so no round breaks later.
     *
     * @param list<TournamentPlayer> $players
     */
    private function checkClassRules(array $players): void
    {
        foreach ($players as $index => $player) {
            foreach (array_slice($players, $index + 1) as $other) {
                try {
                    $this->games->checkClassRule($player->userId, $other->userId);
                } catch (PlayRefused) {
                    throw new ActionRefused('tournament.class_rule_conflict', ['first' => $player->username, 'second' => $other->username]);
                }
            }
        }
    }

    // --- The heartbeat: whatever is due ---

    /**
     * Does what is due for one tournament: starts a paired round, decides
     * boards past their deadline, starts postponed boards, closes a finished
     * round and pairs the next, or finishes the tournament. Returns a short
     * text of what happened (for the job's log).
     */
    public function tick(int $tournamentId, int $now): string
    {
        $lockName = 'tournament_' . $tournamentId;
        if (!$this->database->acquireLock($lockName, self::LOCK_WAIT_SECONDS)) {
            return 'busy';
        }
        try {
            $done = [];
            for ($step = 0; $step < self::MOST_STEPS_PER_TICK; $step++) {
                $tournament = $this->tournaments->find($tournamentId);
                if ($tournament === null || !$tournament->isRunning() || $tournament->isPaused) {
                    break;
                }
                $action = $this->nextStep($tournament, $now);
                if ($action === '') {
                    break;
                }
                $done[] = $action;
            }

            return implode(', ', $done);
        } finally {
            $this->database->releaseLock($lockName);
        }
    }

    /** One step of tick(); returns what it did, or '' when nothing is due. */
    private function nextStep(Tournament $tournament, int $now): string
    {
        $round = $this->roundRow($tournament->id, $tournament->currentRound);

        // --- No round yet, or the last one is closed: pair the next ---
        if ($round === null || $round['state'] === 'closed') {
            if ($this->pairNextRound($tournament, $now)) {
                return 'round ' . ($tournament->currentRound + 1) . ' paired';
            }
            if ($this->hasOpenBoards($tournament->id)) {
                return $this->startPostponedBoards($tournament, $now) > 0 ? 'postponed boards started' : '';
            }
            $this->finish($tournament, $now);

            return 'finished';
        }

        // --- A paired round starts, unless the organiser wants to check it first ---
        if ($round['state'] === 'paired') {
            if ($tournament->confirmsPairings) {
                return '';
            }
            $this->startRound($tournament, $tournament->currentRound, $now);

            return 'round ' . $tournament->currentRound . ' started';
        }

        // --- A running round ---
        $decided = $this->applyDeadlines($tournament, $now);
        $started = $this->startPostponedBoards($tournament, $now);
        if ($tournament->format === Tournament::FORMAT_KNOCKOUT) {
            $started += $this->addKnockoutGames($tournament, $now);
        }
        if ($this->isRoundComplete($tournament, $tournament->currentRound)) {
            $this->tournaments->closeRound($tournament->id, $tournament->currentRound, $now);
            $this->tournaments->log($tournament->id, null, 'round_closed', 'Round ' . $tournament->currentRound, $now);

            return 'round ' . $tournament->currentRound . ' closed';
        }

        return $decided + $started > 0 ? "{$decided} boards decided, {$started} games started" : '';
    }

    // --- Pairing a round ---

    /** Makes the boards of the next round; false when the tournament has no more rounds. */
    private function pairNextRound(Tournament $tournament, int $now): bool
    {
        $number = $tournament->currentRound + 1;
        $boards = match ($tournament->format) {
            Tournament::FORMAT_SWISS => $this->swissBoards($tournament, $number),
            Tournament::FORMAT_KNOCKOUT => $this->knockoutBoards($tournament, $number),
            default => $this->fixedBoards($tournament, $number),
        };
        if ($boards === null) {
            return false;
        }
        $this->tournaments->createRound($tournament->id, $number, 'paired');
        foreach ($boards as $board) {
            $pairingId = $this->tournaments->addPairing($tournament->id, $number, $board['division'], $board['board'], $board['white'], $board['black'], $board['match']);
            if ($board['bye'] !== '') {
                $this->tournaments->writeOutcome($pairingId, $board['bye'], $this->byeHalves($tournament, $board['bye']), 0);
            }
        }
        $this->tournaments->setCurrentRound($tournament->id, $number);
        $this->tournaments->log($tournament->id, null, 'round_paired', 'Round ' . $number . ': ' . count($boards) . ' boards', $now);

        return true;
    }

    /**
     * What a bye is worth: in Round Robin and League nothing, it is not a
     * game (A100); in a knockout the player goes through; in Swiss the
     * organiser's setting (a full point by default; an absence half a point).
     */
    private function byeHalves(Tournament $tournament, string $byeKind): int
    {
        if ($tournament->format === Tournament::FORMAT_KNOCKOUT) {
            return 2;
        }
        if ($tournament->hasFixedTables()) {
            return 0;
        }

        return $byeKind === Pairing::OUTCOME_ABSENCE_BYE ? $tournament->absenceByeHalves : $tournament->byeHalves;
    }

    /**
     * Round Robin and League: the Berger tables, per division (T-02, T-05).
     *
     * @return list<array{division: int, board: int, white: int, black: ?int, match: int, bye: string}>|null
     */
    private function fixedBoards(Tournament $tournament, int $number): ?array
    {
        $players = $this->tournaments->players($tournament->id);
        $boards = [];
        $anyRound = false;
        foreach ($this->divisionsOf($players) as $division => $divisionPlayers) {
            $rounds = BergerTables::rounds(count($divisionPlayers), $tournament->isDoubleRound);
            if (!isset($rounds[$number - 1])) {
                continue;
            }
            $anyRound = true;
            $boardNumber = 1;
            foreach ($rounds[$number - 1] as [$whiteSeed, $blackSeed]) {
                $white = $divisionPlayers[$whiteSeed - 1];
                $black = $blackSeed === null ? null : $divisionPlayers[$blackSeed - 1];
                // A player removed from the tournament (A108) is as if they never played.
                if ($white->state === TournamentPlayer::STATE_REMOVED || ($black !== null && $black->state === TournamentPlayer::STATE_REMOVED)) {
                    continue;
                }
                $boards[] = [
                    'division' => $division,
                    'board' => $boardNumber++,
                    'white' => $white->userId,
                    'black' => $black?->userId,
                    'bye' => $black === null ? Pairing::OUTCOME_BYE : '',
                    'match' => 0,
                ];
            }
        }

        return $anyRound ? $boards : null;
    }

    /**
     * Swiss: the Dutch system for the players still in, absence byes for
     * those away this round (T-03, A99, A108).
     *
     * @return list<array{division: int, board: int, white: int, black: ?int, match: int, bye: string}>|null
     */
    private function swissBoards(Tournament $tournament, int $number): ?array
    {
        if ($number > max(1, $tournament->roundsPlanned)) {
            return null;
        }
        $absences = $this->tournaments->absences($tournament->id);
        $pairings = $this->tournaments->pairings($tournament->id);
        $boards = [];
        $present = [];
        foreach ($this->tournaments->players($tournament->id) as $player) {
            if (!$player->isActive() || $player->joinedRound > $number) {
                continue;
            }
            if (in_array($number, $absences[$player->userId] ?? [], true)) {
                $boards[] = ['division' => 0, 'board' => 0, 'white' => $player->userId, 'black' => null, 'match' => 0, 'bye' => Pairing::OUTCOME_ABSENCE_BYE];
                continue;
            }
            $present[] = self::swissPlayer($player, $pairings, $number);
        }
        $roundCount = max(1, $tournament->roundsPlanned);
        $result = (new DutchPairing())->pair($present, $roundCount, $number === $roundCount);
        if ($result === null) {
            return null;
        }
        $boardNumber = 1;
        $paired = [];
        foreach ($result['boards'] as [$whiteId, $blackId]) {
            $paired[] = ['division' => 0, 'board' => $boardNumber++, 'white' => $whiteId, 'black' => $blackId, 'match' => 0, 'bye' => ''];
        }
        if ($result['byeId'] !== null) {
            $paired[] = ['division' => 0, 'board' => $boardNumber++, 'white' => $result['byeId'], 'black' => null, 'match' => 0, 'bye' => Pairing::OUTCOME_BYE];
        }
        foreach ($boards as $absence) {
            $absence['board'] = $boardNumber++;
            $paired[] = $absence;
        }

        return $paired;
    }

    /**
     * What the Dutch pairing needs to know about a player, from their boards.
     *
     * @param list<Pairing> $pairings
     */
    public static function swissPlayer(TournamentPlayer $player, array $pairings, int $roundToPair = 0): SwissPlayer
    {
        $halves = 0;
        $colours = [];
        $opponents = [];
        $hadBye = false;
        $unplayed = 0;
        foreach ($pairings as $pairing) {
            if (!$pairing->involves($player->userId)) {
                continue;
            }
            if ($pairing->counts() && !$pairing->wasPlayed()) {
                $unplayed++;
            }
            if ($pairing->counts()) {
                $halves += $pairing->halvesOf($player->userId);
            }
            // Only games really played count as met: after a forfeit the two may meet again (FIDE C.04.1).
            $opponentId = $pairing->opponentOf($player->userId);
            if ($pairing->wasPlayed() && $opponentId !== null) {
                $colours[] = $pairing->colourOf($player->userId);
                $opponents[] = $opponentId;
            }
            // C2: after a bye, or a win's worth of points without playing, no second bye.
            if ($pairing->counts() && !$pairing->wasPlayed() && $pairing->halvesOf($player->userId) === 2) {
                $hadBye = true;
            }
        }

        return new SwissPlayer(
            $player->userId,
            $player->seed,
            $halves,
            $colours,
            $opponents,
            $hadBye,
            $unplayed,
            self::floatIn($player->userId, $roundToPair - 1, $pairings),
            self::floatIn($player->userId, $roundToPair - 2, $pairings),
        );
    }

    /**
     * A player's float in one round (FIDE 1.4): "down" after playing a
     * lower-scored opponent or scoring without playing, "up" after playing
     * a higher-scored one, "" otherwise. Scores are those before that round.
     *
     * @param list<Pairing> $pairings
     */
    private static function floatIn(int $userId, int $round, array $pairings): string
    {
        if ($round < 1) {
            return '';
        }
        $board = null;
        $scoreBefore = [];
        foreach ($pairings as $pairing) {
            if ($pairing->round < $round && $pairing->counts()) {
                $scoreBefore[$pairing->whiteId] = ($scoreBefore[$pairing->whiteId] ?? 0) + $pairing->whiteHalves;
                if ($pairing->blackId !== null) {
                    $scoreBefore[$pairing->blackId] = ($scoreBefore[$pairing->blackId] ?? 0) + $pairing->blackHalves;
                }
            }
            if ($pairing->round === $round && $pairing->involves($userId) && $pairing->counts()) {
                $board = $pairing;
            }
        }
        if ($board === null) {
            return '';
        }
        if (!$board->wasPlayed()) {
            return $board->halvesOf($userId) > 0 ? SwissPlayer::FLOAT_DOWN : '';
        }
        $own = $scoreBefore[$userId] ?? 0;
        $opponent = $scoreBefore[(int) $board->opponentOf($userId)] ?? 0;
        if ($own === $opponent) {
            return '';
        }

        return $own > $opponent ? SwissPlayer::FLOAT_DOWN : SwissPlayer::FLOAT_UP;
    }

    /**
     * Knockout: round 1 from the bracket, later rounds from the winners
     * (T-04, A100). The final round also gets the match for third place
     * when the organiser wants one.
     *
     * @return list<array{division: int, board: int, white: int, black: ?int, match: int, bye: string}>|null
     */
    private function knockoutBoards(Tournament $tournament, int $number): ?array
    {
        $players = $this->tournaments->players($tournament->id);
        usort($players, fn (TournamentPlayer $a, TournamentPlayer $b): int => $a->seed <=> $b->seed);
        // After the final (and the match for third place, played beside it) there is nothing more.
        if ($number > KnockoutBracket::roundCount(count($players))) {
            return null;
        }
        $seedOf = [];
        foreach ($players as $player) {
            $seedOf[$player->userId] = $player->seed;
        }

        // --- Who is still in: in bracket order, null where nobody came through ---
        if ($number === 1) {
            $slots = [];
            foreach (KnockoutBracket::firstRound(count($players)) as [$topSeed, $bottomSeed]) {
                $slots[] = $players[$topSeed - 1]->userId;
                $slots[] = $bottomSeed === null ? null : $players[$bottomSeed - 1]->userId;
            }
            $losers = [];
        } else {
            $results = $this->knockoutResults($tournament, $number - 1, $seedOf);
            $slots = array_map(fn (array $match): ?int => $match['winner'], $results);
            $losers = array_map(fn (array $match): ?int => $match['loser'], $results);
            $stillIn = array_filter($slots, fn (?int $userId): bool => $userId !== null);
            if (count($slots) < 2 || count($stillIn) < 2) {
                return null;
            }
        }

        $boards = [];
        $matchNumber = 1;
        for ($i = 0; $i + 1 < count($slots); $i += 2) {
            $boards = [...$boards, ...self::knockoutMatch($slots[$i], $slots[$i + 1], $matchNumber, $seedOf)];
            $matchNumber++;
        }

        // --- The match for third place, beside the final (A100) ---
        $isFinal = count($slots) === 2;
        if ($isFinal && $tournament->hasThirdPlaceMatch && count($losers) === 2 && $losers[0] !== null && $losers[1] !== null) {
            $boards = [...$boards, ...self::knockoutMatch($losers[0], $losers[1], $matchNumber, $seedOf)];
        }

        return $boards;
    }

    /**
     * The first board of one knockout match: the higher seed has White in
     * game 1; a lone player has a bye and goes through.
     *
     * @param array<int, int> $seedOf
     * @return list<array{division: int, board: int, white: int, black: ?int, match: int, bye: string}>
     */
    private static function knockoutMatch(?int $first, ?int $second, int $matchNumber, array $seedOf): array
    {
        if ($first === null && $second === null) {
            return [];
        }
        if ($first === null || $second === null) {
            return [['division' => 0, 'board' => $matchNumber, 'white' => (int) ($first ?? $second), 'black' => null, 'match' => $matchNumber, 'bye' => Pairing::OUTCOME_BYE]];
        }
        [$higher, $lower] = ($seedOf[$first] ?? 0) <= ($seedOf[$second] ?? 0) ? [$first, $second] : [$second, $first];

        return [['division' => 0, 'board' => $matchNumber, 'white' => $higher, 'black' => $lower, 'match' => $matchNumber, 'bye' => '']];
    }

    // --- Starting a round and its boards ---

    /** Starts every board of a paired round: a real game each, or postponed for an absent player. */
    public function startRound(Tournament $tournament, int $number, int $now): void
    {
        $deadline = $tournament->isTimed() ? null : $now + $tournament->roundDays * 86400;
        $this->tournaments->startRound($tournament->id, $number, $now, $deadline);
        $absences = $this->tournaments->absences($tournament->id);
        $players = self::playersById($this->tournaments->players($tournament->id));
        foreach ($this->tournaments->pairings($tournament->id) as $pairing) {
            if ($pairing->round === $number && $pairing->state === Pairing::STATE_WAITING) {
                $this->startBoard($tournament, $pairing, $players, $absences, $number, $now);
            }
        }
        $this->tournaments->log($tournament->id, null, 'round_started', 'Round ' . $number, $now);
    }

    /**
     * Starts one board: a forfeit when a player has withdrawn, postponed when
     * one is away (Round Robin, League), otherwise a real game.
     *
     * @param array<int, TournamentPlayer> $players
     * @param array<int, list<int>> $absences
     */
    private function startBoard(Tournament $tournament, Pairing $pairing, array $players, array $absences, int $round, int $now): void
    {
        if ($pairing->blackId === null) {
            return;
        }
        $white = $players[$pairing->whiteId] ?? null;
        $black = $players[$pairing->blackId] ?? null;
        $whiteGone = $white === null || !$white->isActive();
        $blackGone = $black === null || !$black->isActive();

        // --- Withdrawn players lose by forfeit (A108) ---
        if ($whiteGone || $blackGone) {
            if ($whiteGone && $blackGone) {
                $this->tournaments->writeOutcome($pairing->id, Pairing::OUTCOME_DOUBLE_FORFEIT, 0, 0);
            } elseif ($whiteGone) {
                $this->tournaments->writeOutcome($pairing->id, Pairing::OUTCOME_BLACK_FORFEIT_WIN, 0, 2);
            } else {
                $this->tournaments->writeOutcome($pairing->id, Pairing::OUTCOME_WHITE_FORFEIT_WIN, 2, 0);
            }

            return;
        }

        // --- Someone is away: the board waits for them (A108) ---
        $isAway = in_array($round, $absences[$pairing->whiteId] ?? [], true) || in_array($round, $absences[$pairing->blackId] ?? [], true);
        if ($isAway && $tournament->hasFixedTables()) {
            $this->tournaments->setPairingState($pairing->id, Pairing::STATE_POSTPONED);

            return;
        }

        $this->startGameFor($tournament, $pairing, $white, $black, $now);
    }

    /** Starts the game of a board; a computer's games are never rated (A105). */
    public function startGameFor(Tournament $tournament, Pairing $pairing, TournamentPlayer $white, TournamentPlayer $black, int $now): void
    {
        $isRated = $tournament->isRated && !$white->isComputer && !$black->isComputer;
        try {
            $gameId = $this->games->startForTournament(
                $pairing->whiteId,
                (int) $pairing->blackId,
                $tournament->daysPerMove,
                $isRated,
                $now,
                $tournament->isTimed() ? $tournament->timeControl : null,
            );
        } catch (PlayRefused $refusal) {
            // Left waiting; the organiser sees it and can decide the board.
            $this->tournaments->log($tournament->id, null, 'game_not_started', 'Board ' . $pairing->board . ': ' . $refusal->languageKey, $now);

            return;
        }
        $deadline = $tournament->isTimed() ? null : $now + $tournament->roundDays * 86400;
        $this->tournaments->setPairingGame($pairing->id, $gameId, $deadline);
    }

    /**
     * Starts postponed boards whose players are both back (A108): any board
     * of an earlier round, as soon as neither player is away this round.
     */
    private function startPostponedBoards(Tournament $tournament, int $now): int
    {
        $absences = $this->tournaments->absences($tournament->id);
        $players = self::playersById($this->tournaments->players($tournament->id));
        $round = $tournament->currentRound;
        $started = 0;
        foreach ($this->tournaments->pairings($tournament->id) as $pairing) {
            if ($pairing->state !== Pairing::STATE_POSTPONED || $pairing->blackId === null) {
                continue;
            }
            $stillAway = in_array($round, $absences[$pairing->whiteId] ?? [], true) || in_array($round, $absences[$pairing->blackId] ?? [], true);
            if ($stillAway) {
                continue;
            }
            $this->tournaments->setPairingState($pairing->id, Pairing::STATE_WAITING);
            $this->startBoard($tournament, $this->tournaments->findPairing($pairing->id) ?? $pairing, $players, $absences, $round, $now);
            $started++;
        }

        return $started;
    }

    // --- Deadlines and the forfeit rule (A102) ---

    /** Decides the boards past their deadline by the tournament's forfeit rule; returns how many. */
    private function applyDeadlines(Tournament $tournament, int $now): int
    {
        if ($tournament->forfeitRule === self::FORFEIT_ORGANISER) {
            return 0;
        }
        $decided = 0;
        foreach ($this->tournaments->pairings($tournament->id) as $pairing) {
            if ($pairing->deadlineAt === null || $pairing->deadlineAt > $now) {
                continue;
            }
            if ($pairing->state === Pairing::STATE_PLAYING && $pairing->gameId !== null) {
                $decided += $this->forfeitByRule($tournament->forfeitRule, $pairing->gameId, $now) ? 1 : 0;
            } elseif ($pairing->needsOrganiser()) {
                // An aborted game nobody replayed: nobody played, nobody scores.
                $this->tournaments->writeOutcome($pairing->id, Pairing::OUTCOME_DOUBLE_FORFEIT, 0, 0);
                $decided++;
            }
        }

        return $decided;
    }

    /**
     * Ends one running game by the rule, unrated, through Games::end (K3):
     * nobody moved -> double forfeit; otherwise the player to move loses
     * (loss on time) or it is a draw.
     */
    public function forfeitByRule(string $rule, int $gameId, int $now): bool
    {
        $game = $this->gameRecords->find($gameId);
        if ($game === null || !$game->isRunning()) {
            return false;
        }
        if ($game->ply === 0) {
            return $this->games->end($gameId, Game::RESULT_ABORTED, TournamentResults::REASON_NO_MOVES, $now, false);
        }
        if ($rule === self::FORFEIT_DRAW) {
            return $this->games->end($gameId, Game::RESULT_DRAW, TournamentResults::REASON_ROUND_DEADLINE, $now, false);
        }
        $result = $game->sideToMove() === Position::WHITE ? Game::RESULT_BLACK_WON : Game::RESULT_WHITE_WON;

        return $this->games->end($gameId, $result, TournamentResults::REASON_ROUND_DEADLINE, $now, false);
    }

    // --- Closing a round and finishing ---

    /**
     * A round is complete when no board of it is waiting, playing or waiting
     * for the organiser. Postponed boards do not hold the round up; they
     * are played later (A108). A knockout round also needs every match decided.
     */
    private function isRoundComplete(Tournament $tournament, int $number): bool
    {
        foreach ($this->tournaments->pairings($tournament->id) as $pairing) {
            if ($pairing->round !== $number) {
                continue;
            }
            if ($pairing->state === Pairing::STATE_WAITING || $pairing->state === Pairing::STATE_PLAYING || $pairing->needsOrganiser()) {
                return false;
            }
        }
        if ($tournament->format === Tournament::FORMAT_KNOCKOUT) {
            foreach ($this->knockoutResults($tournament, $number, $this->seeds($tournament->id)) as $match) {
                if (!$match['decided']) {
                    return false;
                }
            }
        }

        return true;
    }

    /** True while any board of the tournament still has to be played or decided. */
    private function hasOpenBoards(int $tournamentId): bool
    {
        foreach ($this->tournaments->pairings($tournamentId) as $pairing) {
            if (in_array($pairing->state, [Pairing::STATE_WAITING, Pairing::STATE_PLAYING, Pairing::STATE_POSTPONED], true) || $pairing->needsOrganiser()) {
                return true;
            }
        }

        return false;
    }

    private function finish(Tournament $tournament, int $now): void
    {
        $this->tournaments->setStatus($tournament->id, Tournament::STATUS_FINISHED, $now);
        $this->tournaments->log($tournament->id, null, 'finished', '', $now);
    }

    // --- Knockout matches (A100) ---

    /**
     * Each match of a knockout round: decided or not, its winner and loser,
     * and what it still needs ("game" for the second game, "armageddon").
     *
     * @param array<int, int> $seedOf
     * @return list<array{match: int, decided: bool, winner: ?int, loser: ?int, needs: string}>
     */
    public function knockoutResults(Tournament $tournament, int $round, array $seedOf): array
    {
        $byMatch = [];
        foreach ($this->tournaments->pairings($tournament->id) as $pairing) {
            if ($pairing->round === $round) {
                $byMatch[$pairing->matchNumber][] = $pairing;
            }
        }
        ksort($byMatch);
        $results = [];
        foreach ($byMatch as $matchNumber => $boards) {
            $results[] = ['match' => $matchNumber] + self::matchResult($boards, $tournament->knockoutGames, $seedOf);
        }

        return $results;
    }

    /**
     * The result of one match from its boards.
     *
     * @param list<Pairing> $boards
     * @param array<int, int> $seedOf
     * @return array{decided: bool, winner: ?int, loser: ?int, needs: string}
     */
    public static function matchResult(array $boards, int $gamesPerMatch, array $seedOf): array
    {
        $first = $boards[0];
        if ($first->isBye()) {
            return ['decided' => $first->isDone(), 'winner' => $first->whiteId, 'loser' => null, 'needs' => ''];
        }
        $playerA = $first->whiteId;
        $playerB = (int) $first->blackId;
        $regular = array_values(array_filter($boards, fn (Pairing $board): bool => !$board->isArmageddon));
        $armageddon = array_values(array_filter($boards, fn (Pairing $board): bool => $board->isArmageddon));

        foreach ($boards as $board) {
            if (!$board->counts()) {
                return ['decided' => false, 'winner' => null, 'loser' => null, 'needs' => ''];
            }
        }
        // Nobody turned up to any game: both are out.
        $allDoubleForfeits = array_filter($regular, fn (Pairing $board): bool => $board->outcome !== Pairing::OUTCOME_DOUBLE_FORFEIT) === [];
        if ($allDoubleForfeits) {
            return ['decided' => true, 'winner' => null, 'loser' => null, 'needs' => ''];
        }
        if (count($regular) < $gamesPerMatch) {
            return ['decided' => false, 'winner' => null, 'loser' => null, 'needs' => 'game'];
        }
        $scoreA = 0;
        $scoreB = 0;
        foreach ($regular as $board) {
            $scoreA += $board->halvesOf($playerA);
            $scoreB += $board->halvesOf($playerB);
        }
        if ($scoreA !== $scoreB) {
            return $scoreA > $scoreB
                ? ['decided' => true, 'winner' => $playerA, 'loser' => $playerB, 'needs' => '']
                : ['decided' => true, 'winner' => $playerB, 'loser' => $playerA, 'needs' => ''];
        }
        if ($armageddon === []) {
            return ['decided' => false, 'winner' => null, 'loser' => null, 'needs' => 'armageddon'];
        }
        // Armageddon: a draw counts as a win for Black (A100).
        $decider = $armageddon[count($armageddon) - 1];
        $winner = $decider->whiteHalves === 2 ? $decider->whiteId : (int) $decider->blackId;

        return ['decided' => true, 'winner' => $winner, 'loser' => $winner === $playerA ? $playerB : $playerA, 'needs' => ''];
    }

    /**
     * Adds the second game, or the armageddon game, to matches that need
     * one, and starts it at once. Game 2 swaps the colours; in armageddon
     * the higher seed takes Black, with draw odds (A100).
     */
    private function addKnockoutGames(Tournament $tournament, int $now): int
    {
        $seedOf = $this->seeds($tournament->id);
        $players = self::playersById($this->tournaments->players($tournament->id));
        $round = $tournament->currentRound;
        $added = 0;
        foreach ($this->knockoutResults($tournament, $round, $seedOf) as $match) {
            if ($match['needs'] === '') {
                continue;
            }
            $boards = array_values(array_filter($this->tournaments->pairings($tournament->id), fn (Pairing $pairing): bool => $pairing->round === $round && $pairing->matchNumber === $match['match']));
            $first = $boards[0];
            $higher = ($seedOf[$first->whiteId] ?? 0) <= ($seedOf[(int) $first->blackId] ?? 0) ? $first->whiteId : (int) $first->blackId;
            $lower = $higher === $first->whiteId ? (int) $first->blackId : $first->whiteId;
            $isArmageddon = $match['needs'] === 'armageddon';
            [$whiteId, $blackId] = $isArmageddon ? [$lower, $higher] : [$first->blackId ?? $lower, $first->whiteId];
            $pairingId = $this->tournaments->addPairing($tournament->id, $round, 0, $first->board, (int) $whiteId, $blackId, $match['match'], count($boards) + 1, $isArmageddon);
            $pairing = $this->tournaments->findPairing($pairingId);
            if ($pairing !== null && isset($players[(int) $whiteId], $players[$blackId])) {
                $this->startGameFor($tournament, $pairing, $players[(int) $whiteId], $players[$blackId], $now);
                $added++;
            }
        }

        return $added;
    }

    // --- Organiser actions on rounds ---

    /** The organiser has checked the pairings and starts the round (confirm pairings). */
    public function startPairedRound(Tournament $tournament, User $manager, int $now): void
    {
        $this->checkManager($tournament, $manager);
        $round = $this->roundRow($tournament->id, $tournament->currentRound);
        if ($round === null || $round['state'] !== 'paired') {
            throw new ActionRefused('tournament.no_paired_round');
        }
        $this->startRound($tournament, $tournament->currentRound, $now);
        $this->tournaments->log($tournament->id, $manager->id, 'round_confirmed', 'Round ' . $tournament->currentRound, $now);
    }

    /** Pairs a round again that has not started yet (A108: re-pair an unstarted round). */
    public function repairRound(Tournament $tournament, User $manager, int $now): void
    {
        $this->checkManager($tournament, $manager);
        $round = $this->roundRow($tournament->id, $tournament->currentRound);
        if ($round === null || $round['state'] !== 'paired') {
            throw new ActionRefused('tournament.no_paired_round');
        }
        $this->tournaments->deleteRound($tournament->id, $tournament->currentRound);
        $this->tournaments->setCurrentRound($tournament->id, $tournament->currentRound - 1);
        $this->tournaments->log($tournament->id, $manager->id, 'round_repaired', 'Round ' . $tournament->currentRound, $now);
        $fresh = $this->tournaments->find($tournament->id);
        if ($fresh !== null) {
            $this->pairNextRound($fresh, $now);
        }
    }

    /**
     * The organiser closes the round now: every board still running is
     * decided by the forfeit rule, as at the deadline. With the rule
     * "organiser decides", they decide each board in TournamentDesk instead.
     */
    public function closeRoundNow(Tournament $tournament, User $manager, int $now): void
    {
        $this->checkManager($tournament, $manager);
        if ($tournament->forfeitRule === self::FORFEIT_ORGANISER) {
            throw new ActionRefused('tournament.decide_each_board');
        }
        foreach ($this->tournaments->pairings($tournament->id) as $pairing) {
            if ($pairing->round === $tournament->currentRound && $pairing->state === Pairing::STATE_PLAYING && $pairing->gameId !== null) {
                $this->forfeitByRule($tournament->forfeitRule, $pairing->gameId, $now);
            }
        }
        $this->tournaments->log($tournament->id, $manager->id, 'round_closed_by_organiser', 'Round ' . $tournament->currentRound, $now);
        $this->tick($tournament->id, $now);
    }

    // --- Helpers ---

    private function checkManager(Tournament $tournament, User $user): void
    {
        if (!$this->access->mayManage($tournament, $user)) {
            throw new ActionRefused('tournament.not_allowed');
        }
    }

    /**
     * @return array{number: int, state: string, startedAt: ?int, deadlineAt: ?int, closedAt: ?int}|null
     */
    private function roundRow(int $tournamentId, int $number): ?array
    {
        foreach ($this->tournaments->rounds($tournamentId) as $round) {
            if ($round['number'] === $number) {
                return $round;
            }
        }

        return null;
    }

    /** @return array<int, int> user id => seed */
    private function seeds(int $tournamentId): array
    {
        $seeds = [];
        foreach ($this->tournaments->players($tournamentId) as $player) {
            $seeds[$player->userId] = $player->seed;
        }

        return $seeds;
    }

    /**
     * The players of each division (1, 2, ...), ordered by seed. For
     * formats without divisions everyone is in division 0.
     *
     * @param list<TournamentPlayer> $players
     * @param array<int, int> $overrides  user id => division, for players being placed
     * @return array<int, list<TournamentPlayer>>
     */
    public function divisionsOf(array $players, array $overrides = []): array
    {
        $divisions = [];
        foreach ($players as $player) {
            if ($player->state === TournamentPlayer::STATE_REMOVED) {
                continue;
            }
            $divisions[$overrides[$player->userId] ?? $player->division][] = $player;
        }
        ksort($divisions);
        foreach ($divisions as $division => $members) {
            usort($members, fn (TournamentPlayer $a, TournamentPlayer $b): int => [$a->seed, $a->username] <=> [$b->seed, $b->username]);
            $divisions[$division] = $members;
        }

        return $divisions;
    }

    /**
     * @param list<TournamentPlayer> $players
     * @return array<int, TournamentPlayer>
     */
    private static function playersById(array $players): array
    {
        $byId = [];
        foreach ($players as $player) {
            $byId[$player->userId] = $player;
        }

        return $byId;
    }
}
