<?php

declare(strict_types=1);

namespace PhpChess\Infra\System;

use PhpChess\Infra\Repository;

/**
 * The jobs table: when each background job last ran, and how it went (INS-08).
 *
 * Owns the table "jobs". Used by the JobRunner to decide which jobs are due,
 * and by the unsafe-state check to warn when jobs stopped running (INS-07).
 */
final class JobRepository extends Repository
{
    /** Unix time a job last finished, or null when it never ran. */
    public function lastFinishedAt(string $jobName): ?int
    {
        $value = $this->database->fetchValue(
            'SELECT UNIX_TIMESTAMP(last_finished_at) FROM ' . $this->table('jobs') . ' WHERE name = ?',
            [$jobName],
        );

        return is_numeric($value) ? (int) $value : null;
    }

    /** Unix time any job last finished, or null when none ever ran. */
    public function lastFinishedAtOfAnyJob(): ?int
    {
        $value = $this->database->fetchValue(
            'SELECT UNIX_TIMESTAMP(MAX(last_finished_at)) FROM ' . $this->table('jobs'),
        );

        return is_numeric($value) ? (int) $value : null;
    }

    /** Records that a job has started. */
    public function recordStart(string $jobName): void
    {
        $this->database->run(
            'INSERT INTO ' . $this->table('jobs') . ' (name, last_started_at) VALUES (?, UTC_TIMESTAMP(3))'
            . ' ON DUPLICATE KEY UPDATE last_started_at = VALUES(last_started_at)',
            [$jobName],
        );
    }

    /** Records that a job has finished, with a short result text. */
    public function recordFinish(string $jobName, string $result, bool $failed): void
    {
        $this->database->run(
            'UPDATE ' . $this->table('jobs')
            . ' SET last_finished_at = UTC_TIMESTAMP(3), last_result = ?, last_failed = ? WHERE name = ?',
            [mb_substr($result, 0, 255), $failed ? 1 : 0, $jobName],
        );
    }
}
