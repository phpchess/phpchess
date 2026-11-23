<?php

declare(strict_types=1);

namespace PhpChess\Domain\Play;

/**
 * One challenge, as read from the challenges table (G-05, G-21).
 *
 * Kinds:
 * - open:   anyone may accept it (the lobby, and the "Play" button, G-20);
 * - direct: sent to one player;
 * - invite: a share link or QR code; whoever opens the link first may accept (G-21).
 * Times are Unix seconds in UTC.
 */
final class Challenge
{
    public const string KIND_OPEN = 'open';
    public const string KIND_DIRECT = 'direct';
    public const string KIND_INVITE = 'invite';

    public const string COLOUR_WHITE = 'white';
    public const string COLOUR_BLACK = 'black';
    public const string COLOUR_RANDOM = 'random';
    public const array COLOURS = [self::COLOUR_RANDOM, self::COLOUR_WHITE, self::COLOUR_BLACK];

    public const string STATE_WAITING = 'waiting';
    public const string STATE_ACCEPTED = 'accepted';
    public const string STATE_DECLINED = 'declined';
    public const string STATE_CANCELLED = 'cancelled';
    public const string STATE_EXPIRED = 'expired';

    public function __construct(
        public readonly int $id,
        public readonly string $kind,
        public readonly int $challengerId,
        public readonly ?int $opponentId,
        public readonly string $colour,
        public readonly int $daysPerMove,
        public readonly bool $isRated,
        public readonly ?string $startFen,
        public readonly string $state,
        public readonly ?int $gameId,
        public readonly int $createdAt,
        public readonly int $expiresAt,
    ) {
    }

    public function isWaiting(int $now): bool
    {
        return $this->state === self::STATE_WAITING && $this->expiresAt > $now;
    }
}
