<?php

declare(strict_types=1);

namespace PhpChess\Domain\Play;

use PhpChess\Domain\Chess\Position;

/**
 * One game, as read from the games table (G-07: one game model for every pace).
 *
 * A Game is a snapshot: it never changes. The move function and the
 * game-end function (Games) read it with the row locked, decide, and write
 * through the GameRepository. Times are Unix seconds in UTC (K12).
 */
final class Game
{
    public const string STATE_RUNNING = 'running';
    public const string STATE_FINISHED = 'finished';

    public const string RESULT_WHITE_WON = 'white_won';
    public const string RESULT_BLACK_WON = 'black_won';
    public const string RESULT_DRAW = 'draw';
    public const string RESULT_ABORTED = 'aborted';

    /** The speed of a correspondence game (days per move). Timed speeds are in TimeControl (C3-1). */
    public const string SPEED_CORRESPONDENCE = 'correspondence';

    /** A pace offer for correspondence ends in "d": "3d" is 3 days per move (G-23). */
    public const string PACE_DAYS_SUFFIX = 'd';

    public function __construct(
        public readonly int $id,
        public readonly int $whiteId,
        public readonly int $blackId,
        public readonly string $speed,
        public readonly ?int $daysPerMove,
        public readonly bool $isRated,
        public readonly string $startFen,
        public readonly string $currentFen,
        public readonly int $ply,
        public readonly string $state,
        public readonly ?string $result,
        public readonly ?string $endReason,
        public readonly ?int $drawOfferedBy,
        public readonly ?int $rematchOfferedBy,
        public readonly ?int $rematchGameId,
        public readonly int $startedAt,
        public readonly ?int $lastMoveAt,
        public readonly ?int $moveDeadlineAt,
        public readonly ?int $endedAt,
        public readonly ?int $whiteSeenAt,
        public readonly ?int $blackSeenAt,
        // --- Timed play (C3-1); all null in a correspondence game ---
        public readonly ?string $timeControl = null,
        public readonly ?int $incrementMs = null,
        public readonly ?int $whiteMsLeft = null,
        public readonly ?int $blackMsLeft = null,
        public readonly ?int $turnSavedMs = null,
        public readonly ?int $turnDeliveredMs = null,
        // --- A waiting premove (G-17) and a pace proposal (G-23) ---
        public readonly ?int $premoveBy = null,
        public readonly ?string $premoveUci = null,
        public readonly ?int $premovePly = null,
        public readonly ?int $paceOfferedBy = null,
        public readonly ?string $paceOffer = null,
    ) {
    }

    // --- State ---

    public function isRunning(): bool
    {
        return $this->state === self::STATE_RUNNING;
    }

    /** True for a game with a chess clock (base + increment), false for correspondence. */
    public function isTimed(): bool
    {
        return $this->timeControl !== null;
    }

    /** The time left at the start of the current turn for a colour, in ms (timed games). */
    public function msLeftAtTurnStart(string $colour): int
    {
        return (int) ($colour === Position::WHITE ? $this->whiteMsLeft : $this->blackMsLeft);
    }

    public function position(): Position
    {
        return Position::fromFen($this->currentFen);
    }

    /** 'w' or 'b': whose turn it is. */
    public function sideToMove(): string
    {
        return explode(' ', $this->currentFen)[1] ?? Position::WHITE;
    }

    // --- Players ---

    public function isPlayer(int $userId): bool
    {
        return $userId === $this->whiteId || $userId === $this->blackId;
    }

    /** 'w' or 'b' for a player of this game, null for anyone else. */
    public function colourOf(int $userId): ?string
    {
        if ($userId === $this->whiteId) {
            return Position::WHITE;
        }
        if ($userId === $this->blackId) {
            return Position::BLACK;
        }

        return null;
    }

    /** The id of the player whose turn it is. */
    public function playerToMoveId(): int
    {
        return $this->sideToMove() === Position::WHITE ? $this->whiteId : $this->blackId;
    }

    /** The other player of the game. Only for players. */
    public function opponentOf(int $userId): int
    {
        return $userId === $this->whiteId ? $this->blackId : $this->whiteId;
    }

    /** The player of a colour. */
    public function playerOf(string $colour): int
    {
        return $colour === Position::WHITE ? $this->whiteId : $this->blackId;
    }

    /** When a player last had this game open (live delivery, G-10). */
    public function seenAtBy(int $userId): ?int
    {
        return $userId === $this->whiteId ? $this->whiteSeenAt : ($userId === $this->blackId ? $this->blackSeenAt : null);
    }

    /** The result as PGN writes it: "1-0", "0-1", "1/2-1/2" or "*". */
    public function pgnResult(): string
    {
        return match ($this->result) {
            self::RESULT_WHITE_WON => '1-0',
            self::RESULT_BLACK_WON => '0-1',
            self::RESULT_DRAW => '1/2-1/2',
            default => '*',
        };
    }
}
