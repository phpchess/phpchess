<?php

declare(strict_types=1);

namespace PhpChess\Domain\Study;

/**
 * Arrows and circles drawn on the board (C6-1, A129, A134).
 *
 * They are kept in the way Lichess, ChessBase and chess.com write them,
 * as commands inside a PGN comment:
 *   [%cal Ge2e4,Rd8d1]   arrows: colour letter, from square, to square;
 *   [%csl Gd4,Yf7]       circles (highlighted squares): colour letter, square.
 *
 * One drawing is stored as its short text: "Ge2e4" (an arrow) or "Gd4"
 * (a circle). The four colours and what they mean here (A129):
 *   G green  = a good move or idea
 *   R red    = a threat
 *   Y yellow = an alternative
 *   B blue   = a note
 */
final class Drawings
{
    /** The colour letters, => what they mean (the language key ends with this). */
    public const array COLOURS = ['G' => 'good', 'R' => 'threat', 'Y' => 'alternative', 'B' => 'note'];

    /** The most drawings on one move: enough for a lesson, small enough to read. */
    public const int MAX_PER_MOVE = 30;

    /** True for a valid drawing text: "Ge2e4" or "Gd4". */
    public static function isValid(string $drawing): bool
    {
        return preg_match('/^[GRYB][a-h][1-8]([a-h][1-8])?$/', $drawing) === 1
            && !self::isArrowToItself($drawing);
    }

    /** True for an arrow ("Ge2e4"), false for a circle ("Gd4"). */
    public static function isArrow(string $drawing): bool
    {
        return strlen($drawing) === 5;
    }

    /**
     * Takes the [%cal] and [%csl] commands out of a comment.
     *
     * @return array{0: string, 1: list<string>}  the comment without them, and the drawings
     */
    public static function takeFromComment(string $comment): array
    {
        $drawings = [];
        $found = preg_match_all('/\[%(cal|csl)\s+([^\]]*)\]/', $comment, $matches);
        for ($index = 0; $index < (int) $found; $index++) {
            foreach (explode(',', $matches[2][$index]) as $part) {
                $drawing = trim($part);
                $isArrowCommand = $matches[1][$index] === 'cal';
                // An arrow command holds arrows only, a circle command circles only.
                if (self::isValid($drawing) && self::isArrow($drawing) === $isArrowCommand) {
                    $drawings[] = $drawing;
                }
            }
        }
        $text = (string) preg_replace('/\[%(cal|csl)\s+[^\]]*\]/', ' ', $comment);

        return [$text, self::tidy($drawings)];
    }

    /**
     * The commands to put in a comment: "[%csl Gd4] [%cal Ge2e4]", or ''.
     *
     * @param list<string> $drawings
     */
    public static function toCommentPart(array $drawings): string
    {
        $circles = array_values(array_filter($drawings, static fn (string $drawing): bool => !self::isArrow($drawing)));
        $arrows = array_values(array_filter($drawings, static fn (string $drawing): bool => self::isArrow($drawing)));
        $parts = [];
        if ($circles !== []) {
            $parts[] = '[%csl ' . implode(',', $circles) . ']';
        }
        if ($arrows !== []) {
            $parts[] = '[%cal ' . implode(',', $arrows) . ']';
        }

        return implode(' ', $parts);
    }

    /**
     * Only valid drawings, each square or arrow once (the last colour wins),
     * at most MAX_PER_MOVE.
     *
     * @param list<string> $drawings
     * @return list<string>
     */
    public static function tidy(array $drawings): array
    {
        $bySquares = [];
        foreach ($drawings as $drawing) {
            if (self::isValid($drawing)) {
                $bySquares[substr($drawing, 1)] = $drawing;
            }
        }

        return array_slice(array_values($bySquares), 0, self::MAX_PER_MOVE);
    }

    private static function isArrowToItself(string $drawing): bool
    {
        return strlen($drawing) === 5 && substr($drawing, 1, 2) === substr($drawing, 3, 2);
    }
}
