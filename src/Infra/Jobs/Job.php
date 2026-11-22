<?php

declare(strict_types=1);

namespace PhpChess\Infra\Jobs;

/**
 * One background job run by the JobRunner (INS-08, ARCHITECTURE.md §8).
 *
 * A job says its name, how often it should run, and does one short batch of
 * work each time. It must finish quickly (a few seconds), because without a
 * real cron it runs at the end of a visitor's page request. Work that is not
 * finished simply continues next time.
 *
 * Jobs so far: CleanupJob. Later chunks add timeouts, vote resolution,
 * round scheduling and the mail queue.
 */
interface Job
{
    /** A unique, fixed name, for example "cleanup". Stored in the jobs table. */
    public function name(): string;

    /** The least number of seconds between two runs. */
    public function intervalSeconds(): int;

    /** Does one batch of work and returns a short result text for the jobs table. */
    public function run(): string;
}
