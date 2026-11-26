<?php

declare(strict_types=1);

namespace PhpChess\Domain\Tournament;

/**
 * One player of a tournament (T-01): their seed, their division (League)
 * and whether they still play.
 *
 * The seed orders the players: 1 is the strongest. It is set from the
 * rating when the tournament starts, or by the organiser in the League
 * editor (A103). Built by TournamentRepository.
 */
final class TournamentPlayer
{
    public const string STATE_ACTIVE = 'active';
    /** Withdrew; games already played count, the rest are forfeits (A108). */
    public const string STATE_WITHDRAWN = 'withdrawn';
    /** Withdrew from a Round Robin or League before half of it: all their games are removed (A108). */
    public const string STATE_REMOVED = 'removed';

    public function __construct(
        public readonly int $tournamentId,
        public readonly int $userId,
        public readonly string $username,
        public readonly string $state,
        public readonly int $seed,
        public readonly int $division,
        public readonly int $rating,
        public readonly int $joinedRound,
        public readonly ?int $withdrawnRound,
        public readonly bool $isComputer,
        public readonly bool $isGuest,
    ) {
    }

    public function isActive(): bool
    {
        return $this->state === self::STATE_ACTIVE;
    }
}
