<?php

declare(strict_types=1);

namespace PhpChess\Domain\Tournament;

/**
 * One board of a tournament round, or one bye (T-02 to T-08).
 *
 * A board is waiting (paired, no game yet), playing (its game runs),
 * postponed (an absent player, A108) or done. A bye has no Black player.
 *
 * The outcome says how the board was decided; the scores are in half
 * points (win 2, draw 1). Played boards copy their score from the game's
 * result (TournamentResults, called by Games::end, K3); forfeits and byes
 * are written by the round services and are never rated.
 */
final class Pairing
{
    // --- States ---
    public const string STATE_WAITING = 'waiting';
    public const string STATE_PLAYING = 'playing';
    public const string STATE_POSTPONED = 'postponed';
    public const string STATE_DONE = 'done';
    /** Removed from the scores, e.g. the games of a player who withdrew early (A108). */
    public const string STATE_VOID = 'void';

    // --- Outcomes ---
    public const string OUTCOME_WHITE_WON = 'white_won';
    public const string OUTCOME_BLACK_WON = 'black_won';
    public const string OUTCOME_DRAW = 'draw';
    /** Black lost by forfeit (round deadline, withdrawal): White gets the point, nothing was played. */
    public const string OUTCOME_WHITE_FORFEIT_WIN = 'white_forfeit_win';
    public const string OUTCOME_BLACK_FORFEIT_WIN = 'black_forfeit_win';
    /** Nobody moved (A102): both get nothing. */
    public const string OUTCOME_DOUBLE_FORFEIT = 'double_forfeit';
    /** A draw decided at the round deadline (forfeit rule "draw", A102). */
    public const string OUTCOME_ADJUDICATED_DRAW = 'adjudicated_draw';
    /** The pairing-bye of an odd number of players. */
    public const string OUTCOME_BYE = 'bye';
    /** A Swiss player who said they will be away this round (A108). */
    public const string OUTCOME_ABSENCE_BYE = 'absence_bye';
    /** The game was aborted; the organiser decides what happens (replay or a result). */
    public const string OUTCOME_ABORTED = 'aborted';

    public function __construct(
        public readonly int $id,
        public readonly int $tournamentId,
        public readonly int $round,
        public readonly int $division,
        public readonly int $board,
        public readonly int $matchNumber,
        public readonly int $gameNumber,
        public readonly bool $isArmageddon,
        public readonly int $whiteId,
        public readonly ?int $blackId,
        public readonly ?int $gameId,
        public readonly string $state,
        public readonly string $outcome,
        public readonly int $whiteHalves,
        public readonly int $blackHalves,
        public readonly ?int $deadlineAt,
    ) {
    }

    public function isBye(): bool
    {
        return $this->blackId === null;
    }

    public function isDone(): bool
    {
        return $this->state === Pairing::STATE_DONE;
    }

    /** Counts for scores and tie-breaks: done, not voided, and not an aborted game waiting for the organiser. */
    public function counts(): bool
    {
        return $this->state === self::STATE_DONE && $this->outcome !== self::OUTCOME_ABORTED;
    }

    /** Needs the organiser: an aborted game (replay it or give a result). */
    public function needsOrganiser(): bool
    {
        return $this->state === self::STATE_DONE && $this->outcome === self::OUTCOME_ABORTED;
    }

    /** A game was really played to a result on the board (not a forfeit, bye or adjudication). */
    public function wasPlayed(): bool
    {
        return $this->counts() && in_array($this->outcome, [self::OUTCOME_WHITE_WON, self::OUTCOME_BLACK_WON, self::OUTCOME_DRAW], true);
    }

    public function involves(int $userId): bool
    {
        return $this->whiteId === $userId || $this->blackId === $userId;
    }

    public function opponentOf(int $userId): ?int
    {
        return $this->whiteId === $userId ? $this->blackId : $this->whiteId;
    }

    /** The half points a player got from this board. */
    public function halvesOf(int $userId): int
    {
        return $this->whiteId === $userId ? $this->whiteHalves : $this->blackHalves;
    }

    /** The colour a player had: "w", "b", or "" for a bye. */
    public function colourOf(int $userId): string
    {
        if ($this->isBye()) {
            return '';
        }

        return $this->whiteId === $userId ? 'w' : 'b';
    }

    /** "1-0", "0-1", "½-½", "+/-" (forfeit), "0-0", or "" while not decided. */
    public function scoreText(): string
    {
        if ($this->state !== self::STATE_DONE) {
            return '';
        }
        return match ($this->outcome) {
            self::OUTCOME_WHITE_WON => '1-0',
            self::OUTCOME_BLACK_WON => '0-1',
            self::OUTCOME_DRAW, self::OUTCOME_ADJUDICATED_DRAW => '½-½',
            self::OUTCOME_WHITE_FORFEIT_WIN => '+/-',
            self::OUTCOME_BLACK_FORFEIT_WIN => '-/+',
            self::OUTCOME_DOUBLE_FORFEIT => '0-0',
            default => self::halvesText($this->whiteHalves),
        };
    }

    /** Half points as text: 3 -> "1½". */
    public static function halvesText(int $halves): string
    {
        $whole = intdiv($halves, 2);
        if ($halves % 2 === 0) {
            return (string) $whole;
        }

        return ($whole === 0 ? '' : (string) $whole) . '½';
    }
}
