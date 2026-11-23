<?php

declare(strict_types=1);

namespace PhpChess\Domain\Chess;

/**
 * One chess move: from a square, to a square, and the piece a pawn becomes
 * when it reaches the last rank (G-01, K1).
 *
 * Squares are numbers 0 to 63: a1 = 0, b1 = 1, ... h1 = 7, a2 = 8, ... h8 = 63.
 * The promotion piece is a lower-case letter (q, r, b, n) or '' for none.
 * A move in "UCI" text form is the two squares and the promotion, for
 * example "e2e4" or "e7e8q"; that is how the browser sends moves.
 */
final class Move
{
    public function __construct(
        public readonly int $from,
        public readonly int $to,
        public readonly string $promotion = '',
    ) {
    }

    // --- Squares as text ---

    /** The name of a square, for example 12 => "e2". */
    public static function squareName(int $square): string
    {
        return chr(ord('a') + $square % 8) . (string) (intdiv($square, 8) + 1);
    }

    /** The number of a square named like "e2", or null when the name is not a square. */
    public static function squareNumber(string $name): ?int
    {
        if (preg_match('/^[a-h][1-8]$/', $name) !== 1) {
            return null;
        }

        return (ord($name[0]) - ord('a')) + 8 * ((int) $name[1] - 1);
    }

    // --- UCI text ---

    /** The move as UCI text, for example "e7e8q". */
    public function toUci(): string
    {
        return self::squareName($this->from) . self::squareName($this->to) . $this->promotion;
    }

    /** Reads UCI text like "e2e4" or "e7e8q". Returns null for anything else. */
    public static function fromUci(string $text): ?self
    {
        if (preg_match('/^([a-h][1-8])([a-h][1-8])([qrbn]?)$/', $text, $parts) !== 1) {
            return null;
        }
        $from = self::squareNumber($parts[1]);
        $to = self::squareNumber($parts[2]);
        if ($from === null || $to === null) {
            return null;
        }

        return new self($from, $to, $parts[3]);
    }

    /** True when both moves go from the same square to the same square with the same promotion. */
    public function equals(self $other): bool
    {
        return $this->from === $other->from && $this->to === $other->to && $this->promotion === $other->promotion;
    }
}
