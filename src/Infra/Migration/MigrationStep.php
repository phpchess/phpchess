<?php

declare(strict_types=1);

namespace PhpChess\Infra\Migration;

/**
 * One step of a migration: a name and one SQL statement (INS-05, contract K11).
 *
 * Table names are written in braces, "{settings}"; the migration runner turns
 * them into the install's prefixed names ("`phpc_settings`"). A statement that
 * names a table without braces is refused, so a migration can only ever touch
 * phpChess's own tables.
 *
 * A step should be safe to repeat (CREATE TABLE IF NOT EXISTS, and so on): if
 * the request dies after the statement but before the step is recorded, the
 * next run repeats it.
 */
final class MigrationStep
{
    public function __construct(
        public readonly string $name,
        public readonly string $sql,
    ) {
    }
}
