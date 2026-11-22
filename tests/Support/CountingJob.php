<?php

declare(strict_types=1);

namespace PhpChess\Tests\Support;

use PhpChess\Infra\Jobs\Job;

/** A test job that only counts how often it ran (used by JobRunnerTest). */
final class CountingJob implements Job
{
    public int $runs = 0;

    public function __construct(private readonly int $interval)
    {
    }

    public function name(): string
    {
        return 'counting';
    }

    public function intervalSeconds(): int
    {
        return $this->interval;
    }

    public function run(): string
    {
        $this->runs++;

        return 'counted';
    }
}
