<?php

declare(strict_types=1);

namespace PhpChess\Domain\Identity;

/**
 * One user's preferences (ID-13): language, colour scheme, board, pieces, email notices.
 *
 * The lists of allowed values live here, so the preferences page, the
 * repository and the tests all use the same ones. The board and piece
 * choices are stored now and used by the board in C2-1.
 */
final class Preferences
{
    /** "auto" follows the phone's or computer's light/dark setting. */
    public const array COLOUR_SCHEMES = ['auto', 'light', 'dark'];

    public const array BOARD_THEMES = ['green', 'brown', 'blue', 'grey'];

    public const array PIECE_SETS = ['standard'];

    /** How long computer players wait before moving (B30, A65); the times are in computer-opponent.js. */
    public const array COMPUTER_DELAYS = ['none', 'short', 'normal', 'long'];

    public function __construct(
        public readonly string $language = 'en',
        public readonly string $colourScheme = 'auto',
        public readonly string $boardTheme = 'green',
        public readonly string $pieceSet = 'standard',
        public readonly bool $emailNotices = true,
    ) {
    }

    /** The defaults for someone without saved preferences (a visitor, a new account). */
    public static function defaults(): self
    {
        return new self();
    }
}
