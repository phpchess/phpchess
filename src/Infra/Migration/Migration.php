<?php

declare(strict_types=1);

namespace PhpChess\Infra\Migration;

/**
 * One numbered migration: a change to the database schema (INS-05).
 *
 * Each file in migrations/ returns one Migration, for example
 * "0001_system_core.php" returns number 1. Migrations run in number order and
 * each of their steps is recorded once done, so a stopped run carries on where
 * it stopped. Later 6.x versions add new numbered files; old ones never change.
 */
final class Migration
{
    /**
     * @param list<MigrationStep> $steps
     */
    public function __construct(
        public readonly int $number,
        public readonly string $description,
        public readonly array $steps,
    ) {
    }
}
