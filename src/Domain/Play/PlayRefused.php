<?php

declare(strict_types=1);

namespace PhpChess\Domain\Play;

use RuntimeException;

/**
 * Thrown when a game or challenge action is refused: not your turn, an
 * illegal move, a game already over, a challenge already taken (G-02 to G-09).
 *
 * The message is a language key (for example "play.not_your_turn"), which
 * the controller turns into text with the Translator, or into an API error
 * code. "values" fills in the text's placeholders.
 */
final class PlayRefused extends RuntimeException
{
    /**
     * @param array<string, string|int> $values
     */
    public function __construct(public readonly string $languageKey, public readonly array $values = [])
    {
        parent::__construct($languageKey);
    }
}
