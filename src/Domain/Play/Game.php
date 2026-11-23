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

    /** The speed of a correspondence game (days per move). Rapid and blitz arrive in C3-1. */
    public const string SPEED_CORRESPONDENCE = 'correspondence';

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
    ) {
    }

    // --- State ---

    public function isRunning(): bool
    {
        return $this->state === self::STATE_RUNNING;
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
