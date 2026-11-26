<?php

declare(strict_types=1);

namespace PhpChess\Http;

use PhpChess\Domain\Chess\Position;

/**
 * A micro board: a position as a 64 x 64 pixel icon (F2, decision A84).
 *
 * Each square is 8 x 8 pixels. A piece is only a 4 x 4 pixel mark in the
 * middle of its square: light for White, dark for Black. That is enough to
 * recognise a position at a glance (the start position, an opening, an
 * endgame), and it is far lighter than the mini board with piece pictures
 * (MiniBoard.php).
 *
 * The square colours follow the member's board choice through the same CSS
 * classes as the mini board (play.css: mini-square-light, mini-square-dark).
 */
final class MicroBoard
{
    /** Pixels per square; the board is 8 squares wide. */
    private const int SQUARE = 8;

    /** Pixels of a piece mark, and its distance from the square's edge. */
    private const int MARK = 4;
    private const int MARK_OFFSET = 2;

    /**
     * The SVG of the position, White at the bottom unless $blackAtBottom.
     * $label is read out by screen readers, e.g. "Board".
     */
    public static function svg(Position $position, bool $blackAtBottom, string $label): string
    {
        $size = 8 * self::SQUARE;
        $squaresSvg = '';
        $marksSvg = '';

        for ($row = 0; $row < 8; $row++) {
            for ($column = 0; $column < 8; $column++) {
                // Row 0 is the top of the picture: rank 8 for White's view, rank 1 for Black's.
                $rank = $blackAtBottom ? $row : 7 - $row;
                $file = $blackAtBottom ? 7 - $column : $column;
                $x = $column * self::SQUARE;
                $y = $row * self::SQUARE;

                $colourClass = ($rank + $file) % 2 === 0 ? 'mini-square-dark' : 'mini-square-light';
                $squaresSvg .= '<rect class="' . $colourClass . '" x="' . $x . '" y="' . $y . '" width="8" height="8"/>';

                // A piece: upper case letters are White ("P", "K"), lower case Black ("p", "k").
                $piece = $position->board[$rank * 8 + $file];
                if ($piece !== '') {
                    $markClass = ctype_upper($piece) ? 'micro-piece-white' : 'micro-piece-black';
                    $marksSvg .= '<rect class="' . $markClass . '" x="' . ($x + self::MARK_OFFSET) . '" y="' . ($y + self::MARK_OFFSET)
                        . '" width="' . self::MARK . '" height="' . self::MARK . '"/>';
                }
            }
        }

        return '<svg class="micro-board" width="' . $size . '" height="' . $size . '" viewBox="0 0 ' . $size . ' ' . $size
            . '" role="img" aria-label="' . htmlspecialchars($label, ENT_QUOTES) . '">' . $squaresSvg . $marksSvg . '</svg>';
    }
}
