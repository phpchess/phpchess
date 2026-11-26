<?php

declare(strict_types=1);

namespace PhpChess\Domain\Chess;

/**
 * Writes a game as PGN, the standard text format chess programs read (G-11).
 *
 * The seven required tags come first (Event, Site, Date, Round, White,
 * Black, Result); a game from a custom start position adds SetUp and FEN.
 * Then the moves in SAN with move numbers, wrapped at 80 characters, and
 * the result. Comments (study games, B38) are written in { } after their
 * move; a comment before the first move comes first.
 */
final class Pgn
{
    /**
     * @param array<string, string> $tags  tag name => value, in order; Result is required
     * @param list<string> $sanMoves  the moves in SAN, from the start position
     * @param array<int, string> $comments  half-moves played => comment (0: before the first move);
     *                                      the text must hold no { or } (the scanner makes sure)
     */
    public static function write(array $tags, string $startFen, array $sanMoves, array $comments = []): string
    {
        if ($startFen !== Position::START_FEN) {
            $tags['SetUp'] = '1';
            $tags['FEN'] = $startFen;
        }

        $lines = [];
        foreach ($tags as $name => $value) {
            $lines[] = '[' . $name . ' "' . self::escape($value) . '"]';
        }
        $lines[] = '';
        $lines[] = self::movetext($startFen, $sanMoves, $tags['Result'] ?? '*', $comments);

        return implode("\n", $lines) . "\n";
    }

    /**
     * The moves with numbers: "1. e4 e5 2. Nf3". When Black moves first
     * (a custom position), the first number is written "1...".
     *
     * @param list<string> $sanMoves
     * @param array<int, string> $comments
     */
    private static function movetext(string $startFen, array $sanMoves, string $result, array $comments = []): string
    {
        $start = Position::fromFen($startFen);
        $moveNumber = $start->fullmoveNumber;
        $isWhiteToMove = $start->sideToMove === Position::WHITE;

        $words = [];
        if (($comments[0] ?? '') !== '') {
            $words[] = '{ ' . $comments[0] . ' }';
        }
        foreach ($sanMoves as $index => $san) {
            $afterComment = ($comments[$index] ?? '') !== '';
            if ($isWhiteToMove) {
                $words[] = $moveNumber . '.';
            } elseif ($index === 0 || $afterComment) {
                // Black's move after a comment gets its number again: "3... Nf6".
                $words[] = $moveNumber . '...';
            }
            $words[] = $san;
            if (($comments[$index + 1] ?? '') !== '') {
                $words[] = '{ ' . $comments[$index + 1] . ' }';
            }
            if (!$isWhiteToMove) {
                $moveNumber++;
            }
            $isWhiteToMove = !$isWhiteToMove;
        }
        $words[] = $result;

        return wordwrap(implode(' ', $words), 80, "\n");
    }

    /** Tag values may not break the quotes. */
    private static function escape(string $value): string
    {
        return str_replace(['\\', '"', "\n", "\r"], ['\\\\', '\\"', ' ', ' '], $value);
    }
}
