<?php

declare(strict_types=1);

namespace PhpChess\Domain\Identity;

use RuntimeException;

/**
 * Thrown when an account action is refused for a reason the user can fix:
 * a taken name, a short password, an expired link, too many tries.
 *
 * The message is a language key (for example "register.username_taken"),
 * which the controller turns into text with the Translator. "values" fills
 * in the text's placeholders.
 */
final class AccountRefused extends RuntimeException
{
    /**
     * @param array<string, string|int> $values
     */
    public function __construct(public readonly string $languageKey, public readonly array $values = [])
    {
        parent::__construct($languageKey);
    }
}
