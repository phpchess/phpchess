<?php

declare(strict_types=1);

namespace PhpChess\Domain\Lichess;

/**
 * What the content scanner found (A81): the games that passed, and one
 * plain reason for every game that did not ("game 3: holds a blocked word").
 */
final class ScanResult
{
    /**
     * @param list<ReadGame> $accepted
     * @param list<string> $rejected
     */
    public function __construct(
        public readonly array $accepted,
        public readonly array $rejected,
    ) {
    }
}
