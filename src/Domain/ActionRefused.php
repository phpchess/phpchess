<?php

declare(strict_types=1);

namespace PhpChess\Domain;

use RuntimeException;

/**
 * Thrown when a community, school or account action is refused (C2-2):
 * a message to someone outside the class, a file that is not a picture,
 * a word the filter blocks.
 *
 * Works like PlayRefused: the message is a language key (for example
 * "messages.not_allowed"), which the controller turns into text, or into
 * an API error code. "values" fills in the text's placeholders.
 */
final class ActionRefused extends RuntimeException
{
    /**
     * @param array<string, string|int> $values
     */
    public function __construct(public readonly string $languageKey, public readonly array $values = [])
    {
        parent::__construct($languageKey);
    }
}
