<?php

declare(strict_types=1);

namespace PhpChess\Domain\Chess;

use RuntimeException;

/**
 * Thrown when a FEN text is not a position a game can start from (G-06).
 * The message says what is wrong, in plain English, for the admin or player.
 */
final class InvalidPosition extends RuntimeException
{
}
