<?php

declare(strict_types=1);

namespace PhpChess\Domain\Tournament;

use PhpChess\Domain\ActionRefused;
use PhpChess\Domain\Identity\User;
use PhpChess\Domain\Play\Game;
use PhpChess\Domain\Play\Games;
use PhpChess\Domain\Play\PlayRefused;
use PhpChess\Infra\Identity\UserRepository;
use PhpChess\Infra\Play\GameRepository;
use PhpChess\Infra\Tournament\TournamentRepository;

/**
 * The organiser's desk: real life in a tournament (A107, A108, ADM-06).
 *
 * Tournaments do not always go to plan. From the tournament's manage page
 * the organiser (or an admin) can:
 * - mark a player away for a round (players can do this themselves);
 * - withdraw a player (players can withdraw themselves);
 * - replace a player who has not played yet (Round Robin, League);
 * - give a board more time (postpone its deadline), or start a postponed board now;
 * - decide a board: a running game, an aborted one, or one never started;
 * - correct the result of a finished game (through Games, K3), or order a replay;
 * - answer disputes: a player asks the organiser to look at a game;
 * - pause and resume the tournament.
 *
 * Every action is written to the tournament log with who did it, and the
 * players see the log on the tournament page. Results always go through
 * Games::end() or Games::correctResult() when a game exists (K3); only
 * boards without a game (forfeits) are written here directly.
 */
final class TournamentDesk
{
    // --- What an organiser can decide for a board ---
    public const string DECIDE_WHITE_WON = 'white_won';
    public const string DECIDE_BLACK_WON = 'black_won';
    public const string DECIDE_DRAW = 'draw';
    public const string DECIDE_DOUBLE_FORFEIT = 'double_forfeit';
    public const array DECISIONS = [self::DECIDE_WHITE_WON, self::DECIDE_BLACK_WON, self::DECIDE_DRAW, self::DECIDE_DOUBLE_FORFEIT];

    // --- Answers to a dispute ---
    public const string DISPUTE_KEEP = 'keep';
    public const string DISPUTE_CORRECT = 'correct';
    public const string DISPUTE_REPLAY = 'replay';

    /** The most days one postponement may add. */
    public const int MOST_POSTPONE_DAYS = 60;

    public function __construct(
        private readonly TournamentRepository $tournaments,
        private readonly TournamentAccess $access,
        private readonly TournamentRounds $rounds,
        private readonly Games $games,
        private readonly GameRepository $gameRecords,
        private readonly UserRepository $users,
    ) {
    }

    // --- Absence (A108) ---

    /**
     * Marks a player away for a coming round. The player may do it for
     * themselves; the organiser for anyone. Only rounds not yet started.
     */
    public function setAbsent(Tournament $tournament, User $actor, int $userId, int $round, bool $isAway, int $now): void
    {
        $this->checkSelfOrManager($tournament, $actor, $userId);
        if ($tournament->isOver()) {
            throw new ActionRefused('tournament.is_over');
        }
        if ($round < $this->firstOpenRound($tournament)) {
            throw new ActionRefused('tournament.round_already_started');
        }
        if ($isAway) {
            $this->tournaments->addAbsence($tournament->id, $userId, $round);
        } else {
            $this->tournaments->removeAbsence($tournament->id, $userId, $round);
        }
        $this->log($tournament, $actor, $isAway ? 'absent' : 'absence_removed', $this->nameOf($userId) . ', round ' . $round, $now);
    }

    /** The first round an absence can still change: the current one while only paired, else the next. */
    public function firstOpenRound(Tournament $tournament): int
    {
        foreach ($this->tournaments->rounds($tournament->id) as $round) {
            if ($round['number'] === $tournament->currentRound && $round['state'] === 'paired') {
                return $tournament->currentRound;
            }
        }

        return $tournament->currentRound + 1;
    }

    // --- Withdrawal (A108) ---

    /**
     * A player leaves a running tournament. Their running games are lost
     * (unrated). In Round Robin and League, a player who played less than
     * half of their games is removed with all their results (FIDE); otherwise
     * their games so far count and the rest are forfeits.
     */
    public function withdraw(Tournament $tournament, User $actor, int $userId, int $now): void
    {
        $this->checkSelfOrManager($tournament, $actor, $userId);
        if (!$tournament->isRunning()) {
            throw new ActionRefused('tournament.not_running');
        }
        $pairings = array_values(array_filter($this->tournaments->pairings($tournament->id), fn (Pairing $pairing): bool => $pairing->involves($userId) && !$pairing->isBye()));

        // --- Running games are lost ---
        foreach ($pairings as $pairing) {
            if ($pairing->state === Pairing::STATE_PLAYING && $pairing->gameId !== null) {
                $result = $pairing->whiteId === $userId ? Game::RESULT_BLACK_WON : Game::RESULT_WHITE_WON;
                $this->games->end($pairing->gameId, $result, TournamentResults::REASON_WITHDRAWN, $now, false);
            }
        }

        // --- Removed altogether, or withdrawn with forfeits ---
        $removesResults = $tournament->hasFixedTables() && $this->playedLessThanHalf($tournament, $userId, $pairings);
        $state = $removesResults ? TournamentPlayer::STATE_REMOVED : TournamentPlayer::STATE_WITHDRAWN;
        $this->tournaments->markWithdrawn($tournament->id, $userId, max(1, $tournament->currentRound), $state);
        foreach ($this->tournaments->pairings($tournament->id) as $pairing) {
            if (!$pairing->involves($userId) || $pairing->isBye()) {
                continue;
            }
            if ($removesResults) {
                $this->tournaments->setPairingState($pairing->id, Pairing::STATE_VOID);
            } elseif ($pairing->state === Pairing::STATE_WAITING || $pairing->state === Pairing::STATE_POSTPONED) {
                $this->writeForfeitAgainst($pairing, $userId);
            }
        }
        $this->log($tournament, $actor, $removesResults ? 'removed' : 'withdrawn', $this->nameOf($userId), $now);
        $this->rounds->tick($tournament->id, $now);
    }

    /**
     * @param list<Pairing> $pairings  the player's boards (no byes)
     */
    private function playedLessThanHalf(Tournament $tournament, int $userId, array $pairings): bool
    {
        $players = $this->tournaments->players($tournament->id);
        $division = 0;
        $divisionSize = 0;
        foreach ($players as $player) {
            if ($player->userId === $userId) {
                $division = $player->division;
            }
        }
        foreach ($players as $player) {
            if ($player->division === $division) {
                $divisionSize++;
            }
        }
        $gamesInAll = ($divisionSize - 1) * ($tournament->isDoubleRound ? 2 : 1);
        $played = count(array_filter($pairings, fn (Pairing $pairing): bool => $pairing->counts()));

        return $played * 2 < $gamesInAll;
    }

    /** A board without a game: the opponent of $loserId wins by forfeit. */
    private function writeForfeitAgainst(Pairing $pairing, int $loserId): void
    {
        if ($pairing->whiteId === $loserId) {
            $this->tournaments->writeOutcome($pairing->id, Pairing::OUTCOME_BLACK_FORFEIT_WIN, 0, 2);
        } else {
            $this->tournaments->writeOutcome($pairing->id, Pairing::OUTCOME_WHITE_FORFEIT_WIN, 2, 0);
        }
    }

    // --- Replacement (A108) ---

    /**
     * Puts a new player in the place of one who has not played yet: Round
     * Robin and League only (in Swiss, everyone else is simply paired on).
     */
    public function replace(Tournament $tournament, User $manager, int $oldUserId, string $newUsername, int $now): void
    {
        $this->checkManager($tournament, $manager);
        if (!$tournament->hasFixedTables()) {
            throw new ActionRefused('tournament.replace_fixed_only');
        }
        foreach ($this->tournaments->pairings($tournament->id) as $pairing) {
            if ($pairing->involves($oldUserId) && $pairing->gameId !== null) {
                throw new ActionRefused('tournament.replace_before_first_game');
            }
        }
        $newUser = $this->users->findByUsername(trim($newUsername));
        if ($newUser === null || $newUser->state !== User::STATE_ACTIVE) {
            throw new ActionRefused('tournament.player_unknown');
        }
        foreach ($this->tournaments->players($tournament->id) as $player) {
            if ($player->userId === $newUser->id) {
                throw new ActionRefused('tournament.already_joined');
            }
        }
        $this->tournaments->replacePlayer($tournament->id, $oldUserId, $newUser->id);
        $this->log($tournament, $manager, 'replaced', $this->nameOf($oldUserId) . ' -> ' . $newUser->username, $now);
    }

    // --- Boards: more time, start now, decide, replay ---

    /** Gives a running board more days before its deadline. */
    public function postpone(Tournament $tournament, User $manager, int $pairingId, int $days, int $now): void
    {
        $this->checkManager($tournament, $manager);
        $pairing = $this->pairingOf($tournament, $pairingId);
        if ($pairing->state !== Pairing::STATE_PLAYING || $tournament->isTimed()) {
            throw new ActionRefused('tournament.board_not_running');
        }
        $days = max(1, min(self::MOST_POSTPONE_DAYS, $days));
        $this->tournaments->setPairingDeadline($pairing->id, max($pairing->deadlineAt ?? $now, $now) + $days * 86400);
        $this->log($tournament, $manager, 'postponed', 'Board ' . $pairing->board . ', round ' . $pairing->round . ': +' . $days . ' days', $now);
    }

    /** Starts a postponed board now, whatever the absences say. */
    public function startNow(Tournament $tournament, User $manager, int $pairingId, int $now): void
    {
        $this->checkManager($tournament, $manager);
        $pairing = $this->pairingOf($tournament, $pairingId);
        if (($pairing->state !== Pairing::STATE_POSTPONED && $pairing->state !== Pairing::STATE_WAITING) || $pairing->blackId === null) {
            throw new ActionRefused('tournament.board_not_waiting');
        }
        [$white, $black] = $this->playersOf($tournament, $pairing);
        $this->rounds->startGameFor($tournament, $pairing, $white, $black, $now);
        $this->log($tournament, $manager, 'board_started', 'Board ' . $pairing->board . ', round ' . $pairing->round, $now);
    }

    /**
     * Decides a board. A running game ends through Games::end(), unrated;
     * a finished game is corrected through Games::correctResult() (K3); a
     * board without a game (never started, aborted, postponed) gets the
     * decision written as a forfeit.
     */
    public function decide(Tournament $tournament, User $manager, int $pairingId, string $decision, int $now): void
    {
        $this->checkManager($tournament, $manager);
        if (!in_array($decision, self::DECISIONS, true)) {
            throw new ActionRefused('play.result_invalid');
        }
        $pairing = $this->pairingOf($tournament, $pairingId);
        if ($pairing->isBye()) {
            throw new ActionRefused('tournament.board_is_bye');
        }
        $game = $pairing->gameId === null ? null : $this->gameRecords->find($pairing->gameId);

        if ($game !== null && $game->isRunning()) {
            if ($decision === self::DECIDE_DOUBLE_FORFEIT) {
                $this->games->end($game->id, Game::RESULT_ABORTED, TournamentResults::REASON_NO_MOVES, $now, false);
            } else {
                $this->games->end($game->id, self::gameResult($decision, $pairing, $game), TournamentResults::REASON_ORGANISER, $now, false);
            }
        } elseif ($game !== null && $game->result !== Game::RESULT_ABORTED && $decision !== self::DECIDE_DOUBLE_FORFEIT) {
            try {
                $this->games->correctResult($game->id, self::gameResult($decision, $pairing, $game), TournamentResults::REASON_ORGANISER, $now);
            } catch (PlayRefused $refusal) {
                throw new ActionRefused($refusal->languageKey);
            }
        } else {
            [$outcome, $whiteHalves, $blackHalves] = match ($decision) {
                self::DECIDE_WHITE_WON => [Pairing::OUTCOME_WHITE_FORFEIT_WIN, 2, 0],
                self::DECIDE_BLACK_WON => [Pairing::OUTCOME_BLACK_FORFEIT_WIN, 0, 2],
                self::DECIDE_DRAW => [Pairing::OUTCOME_ADJUDICATED_DRAW, 1, 1],
                default => [Pairing::OUTCOME_DOUBLE_FORFEIT, 0, 0],
            };
            $this->tournaments->writeOutcome($pairing->id, $outcome, $whiteHalves, $blackHalves);
        }
        $this->log($tournament, $manager, 'decided', 'Board ' . $pairing->board . ', round ' . $pairing->round . ': ' . $decision, $now);
        $this->rounds->tick($tournament->id, $now);
    }

    /**
     * The game result for a decision, from the board's point of view; the
     * game may have the colours the other way round after a replay.
     */
    private static function gameResult(string $decision, Pairing $pairing, Game $game): string
    {
        if ($decision === self::DECIDE_DRAW) {
            return Game::RESULT_DRAW;
        }
        $boardWhiteWins = $decision === self::DECIDE_WHITE_WON;
        $sameColours = $game->whiteId === $pairing->whiteId;

        return $boardWhiteWins === $sameColours ? Game::RESULT_WHITE_WON : Game::RESULT_BLACK_WON;
    }

    /** Orders the board to be played again: a new game, the old one is kept but no longer counts. */
    public function replay(Tournament $tournament, User $manager, int $pairingId, int $now): void
    {
        $this->checkManager($tournament, $manager);
        $pairing = $this->pairingOf($tournament, $pairingId);
        if ($pairing->isBye() || $pairing->gameId === null) {
            throw new ActionRefused('tournament.board_has_no_game');
        }
        $game = $this->gameRecords->find($pairing->gameId);
        if ($game !== null && $game->isRunning()) {
            $this->games->end($game->id, Game::RESULT_ABORTED, TournamentResults::REASON_ORGANISER, $now, false);
        }
        $this->tournaments->resetPairing($pairing->id);
        $fresh = $this->tournaments->findPairing($pairing->id);
        if ($fresh !== null) {
            [$white, $black] = $this->playersOf($tournament, $fresh);
            $this->rounds->startGameFor($tournament, $fresh, $white, $black, $now);
        }
        $this->log($tournament, $manager, 'replay', 'Board ' . $pairing->board . ', round ' . $pairing->round, $now);
    }

    // --- Disputes (A108) ---

    /** A player of the board asks the organiser to look at it. */
    public function raiseDispute(Tournament $tournament, User $player, int $pairingId, string $reason, int $now): int
    {
        $pairing = $this->pairingOf($tournament, $pairingId);
        if (!$pairing->involves($player->id) && !$this->access->mayManage($tournament, $player)) {
            throw new ActionRefused('tournament.not_your_board');
        }
        $reason = trim($reason);
        if ($reason === '' || mb_strlen($reason) > 2000) {
            throw new ActionRefused('tournament.dispute_reason_needed');
        }
        $disputeId = $this->tournaments->createDispute($tournament->id, $pairing->id, $player->id, $reason, $now);
        $this->log($tournament, $player, 'dispute', 'Board ' . $pairing->board . ', round ' . $pairing->round, $now);

        return $disputeId;
    }

    /**
     * Answers a dispute: keep the result, correct it (with a decision for
     * the board), or replay the board. The note is shown to the players.
     */
    public function answerDispute(Tournament $tournament, User $manager, int $disputeId, string $answer, string $decision, string $note, int $now): void
    {
        $this->checkManager($tournament, $manager);
        $dispute = null;
        foreach ($this->tournaments->disputes($tournament->id) as $candidate) {
            if ($candidate['id'] === $disputeId) {
                $dispute = $candidate;
            }
        }
        if ($dispute === null || $dispute['state'] !== 'open') {
            throw new ActionRefused('tournament.dispute_unknown');
        }
        if ($answer === self::DISPUTE_CORRECT) {
            $this->decide($tournament, $manager, $dispute['pairingId'], $decision, $now);
        } elseif ($answer === self::DISPUTE_REPLAY) {
            $this->replay($tournament, $manager, $dispute['pairingId'], $now);
        } elseif ($answer !== self::DISPUTE_KEEP) {
            throw new ActionRefused('tournament.dispute_answer_needed');
        }
        $this->tournaments->decideDispute($disputeId, $answer, mb_substr(trim($note), 0, 2000), $manager->id, $now);
        $this->log($tournament, $manager, 'dispute_answered', $answer, $now);
    }

    // --- Pause and resume ---

    public function setPaused(Tournament $tournament, User $manager, bool $isPaused, int $now): void
    {
        $this->checkManager($tournament, $manager);
        $this->tournaments->setPaused($tournament->id, $isPaused);
        $this->log($tournament, $manager, $isPaused ? 'paused' : 'resumed', '', $now);
    }

    // --- Helpers ---

    private function pairingOf(Tournament $tournament, int $pairingId): Pairing
    {
        $pairing = $this->tournaments->findPairing($pairingId);
        if ($pairing === null || $pairing->tournamentId !== $tournament->id) {
            throw new ActionRefused('tournament.board_unknown');
        }

        return $pairing;
    }

    /**
     * @return array{0: TournamentPlayer, 1: TournamentPlayer}
     */
    private function playersOf(Tournament $tournament, Pairing $pairing): array
    {
        $white = null;
        $black = null;
        foreach ($this->tournaments->players($tournament->id) as $player) {
            if ($player->userId === $pairing->whiteId) {
                $white = $player;
            }
            if ($player->userId === $pairing->blackId) {
                $black = $player;
            }
        }
        if ($white === null || $black === null) {
            throw new ActionRefused('tournament.board_unknown');
        }

        return [$white, $black];
    }

    private function checkManager(Tournament $tournament, User $user): void
    {
        if (!$this->access->mayManage($tournament, $user)) {
            throw new ActionRefused('tournament.not_allowed');
        }
    }

    /** The player themself, or a manager. */
    private function checkSelfOrManager(Tournament $tournament, User $actor, int $userId): void
    {
        if ($actor->id === $userId && $this->access->isPlayer($tournament, $userId)) {
            return;
        }
        $this->checkManager($tournament, $actor);
    }

    private function nameOf(int $userId): string
    {
        return $this->users->usernamesOf([$userId])[$userId] ?? ('#' . $userId);
    }

    private function log(Tournament $tournament, User $actor, string $action, string $details, int $now): void
    {
        $this->tournaments->log($tournament->id, $actor->id, $action, $details, $now);
    }
}
