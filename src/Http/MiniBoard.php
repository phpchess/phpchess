<?php

declare(strict_types=1);

namespace PhpChess\Http;

use PhpChess\Domain\Chess\Position;

/**
 * Draws a small picture of a position as SVG, for the game lists
 * ("My games", the start page): the player sees each game at a glance.
 *
 * Nothing is stored: the game already keeps its current position (FEN),
 * and drawing 64 squares is cheaper than reading a saved picture. The
 * pieces come from the same picture file the big board uses
 * (cm-chessboard's piece sprite, local, A16), drawn with <use>, so the
 * page carries only the square colours and 32 short references.
 *
 * Each square is 40 units wide, the size of one piece in the sprite.
 * The colours follow the member's board choice through the CSS classes
 * "mini-square-light" and "mini-square-dark" (play.css); no inline styles.
 */
final class MiniBoard
{
    private const int SQUARE = 40;

    /** Piece letter in FEN => its id in the sprite file. */
    private const array SPRITE_IDS = [
        'K' => 'wk', 'Q' => 'wq', 'R' => 'wr', 'B' => 'wb', 'N' => 'wn', 'P' => 'wp',
        'k' => 'bk', 'q' => 'bq', 'r' => 'br', 'b' => 'bb', 'n' => 'bn', 'p' => 'bp',
    ];

    /**
     * The SVG for a position, seen from White's side or (with $blackAtBottom) from Black's.
     *
     * @param string $spriteUrl address of the piece sprite, e.g. "/assets/vendor/cm-chessboard/assets/pieces/standard.svg"
     */
    public static function svg(Position $position, bool $blackAtBottom, string $spriteUrl, string $label): string
    {
        $size = 8 * self::SQUARE;
        $squaresSvg = '';
        $piecesSvg = '';

        for ($row = 0; $row < 8; $row++) {
            for ($column = 0; $column < 8; $column++) {
                // Row 0 is the top of the picture: rank 8 for White's view, rank 1 for Black's.
                $rank = $blackAtBottom ? $row : 7 - $row;
                $file = $blackAtBottom ? 7 - $column : $column;
                $x = $column * self::SQUARE;
                $y = $row * self::SQUARE;

                $colourClass = ($rank + $file) % 2 === 0 ? 'mini-square-dark' : 'mini-square-light';
                $squaresSvg .= '<rect class="' . $colourClass . '" x="' . $x . '" y="' . $y . '" width="40" height="40"/>';

                $piece = $position->board[$rank * 8 + $file];
                if (isset(self::SPRITE_IDS[$piece])) {
                    $href = htmlspecialchars($spriteUrl . '#' . self::SPRITE_IDS[$piece], ENT_QUOTES);
                    $piecesSvg .= '<use href="' . $href . '" x="' . $x . '" y="' . $y . '"/>';
                }
            }
        }

        return '<svg class="mini-board" viewBox="0 0 ' . $size . ' ' . $size . '" role="img" aria-label="'
            . htmlspecialchars($label, ENT_QUOTES) . '">' . $squaresSvg . $piecesSvg . '</svg>';
    }
}
