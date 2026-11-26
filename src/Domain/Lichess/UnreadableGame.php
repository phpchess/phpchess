<?php

declare(strict_types=1);

namespace PhpChess\Domain\Lichess;

use RuntimeException;

/**
 * Thrown by PgnReader when a game can't be read: a bad tag line, an
 * illegal move, an unclosed comment. The message says why, in plain
 * English, for the admin's rejection log (A81).
 */
final class UnreadableGame extends RuntimeException
{
}
