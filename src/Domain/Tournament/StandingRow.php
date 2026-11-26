<?php

declare(strict_types=1);

namespace PhpChess\Domain\Tournament;

/**
 * One line of a standings table (T-07): the player, their points (in half
 * points), each tie-break value (in points) and their rank.
 * Made by TieBreaks::standings(); shown by templates/tournaments/standings.php.
 */
final class StandingRow
{
    /**
     * @param array<string, float> $tieBreaks
     */
    public function __construct(
        public readonly TournamentPlayer $player,
        public readonly int $halves,
        public readonly array $tieBreaks,
        public readonly int $gamesPlayed,
        public readonly int $rank = 0,
    ) {
    }

    public function withRank(int $rank): self
    {
        return new self($this->player, $this->halves, $this->tieBreaks, $this->gamesPlayed, $rank);
    }

    /** A tie-break value as text: 7.5 -> "7½", 7.25 -> "7.25". */
    public static function valueText(float $value): string
    {
        if (fmod($value * 2, 1.0) === 0.0) {
            return Pairing::halvesText((int) round($value * 2));
        }

        return number_format($value, 2);
    }
}
