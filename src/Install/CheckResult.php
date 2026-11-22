<?php

declare(strict_types=1);

namespace PhpChess\Install;

/**
 * The result of one installer check, shown as one line on the installer page (INS-03, INS-07).
 *
 * "required" checks must pass before the installer goes on; the others are
 * warnings. "fix" says in plain language what to do when the check fails.
 */
final class CheckResult
{
    public function __construct(
        public readonly string $name,
        public readonly bool $passed,
        public readonly bool $required,
        public readonly string $detail,
        public readonly string $fix = '',
    ) {
    }

    /**
     * True when every required check in the list passed.
     *
     * @param list<self> $results
     */
    public static function allRequiredPassed(array $results): bool
    {
        foreach ($results as $result) {
            if ($result->required && !$result->passed) {
                return false;
            }
        }

        return true;
    }
}
