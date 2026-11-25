<?php

declare(strict_types=1);

namespace PhpChess\Domain\Strength;

/**
 * One puzzle of the strength test (B15), as read from strength-puzzles.csv.
 *
 * $fen is the position before the opponent's move. $moves are UCI moves
 * ("e2e4", "e7e8q"): the first is the opponent's, which sets up the
 * puzzle; the rest alternate the player's answers and the opponent's replies.
 */
final class Puzzle
{
    /** @param list<string> $moves */
    public function __construct(
        public readonly string $id,
        public readonly string $fen,
        public readonly array $moves,
        public readonly int $rating,
    ) {
    }

    /**
     * What the browser needs to show the puzzle.
     *
     * @return array{id: string, fen: string, moves: list<string>}
     */
    public function forBrowser(): array
    {
        return ['id' => $this->id, 'fen' => $this->fen, 'moves' => $this->moves];
    }
}
