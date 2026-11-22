<?php

declare(strict_types=1);

namespace PhpChess\Tests\Infra;

use PhpChess\Infra\Database;
use PhpChess\Infra\Jobs\Job;
use PhpChess\Infra\Jobs\JobRunner;
use PhpChess\Infra\Migration\MigrationRunner;
use PhpChess\Infra\System\JobRepository;
use PhpChess\Infra\System\SettingsRepository;
use PhpChess\Tests\Support\CountingJob;
use PhpChess\Tests\Support\TestDatabase;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Tests the job runner (INS-08): the database lock, due jobs, pseudo-cron.
 */
final class JobRunnerTest extends TestCase
{
    private Database $database;

    protected function setUp(): void
    {
        $this->database = TestDatabase::connectEmpty($this);
        (new MigrationRunner($this->database, MigrationRunner::loadFolder(dirname(__DIR__, 2) . '/migrations')))->runPending();
    }

    protected function tearDown(): void
    {
        if (isset($this->database)) {
            TestDatabase::dropTestTables($this->database);
        }
    }

    public function testTwoRunnersNeverOverlap(): void
    {
        $job = $this->countingJob();
        $runner = $this->runner($this->database, [$job]);

        // A second connection holds the lock, like a runner still at work.
        $otherConnection = TestDatabase::secondConnection($this);
        self::assertTrue($otherConnection->acquireLock('job_runner'));

        $lines = $runner->runDueJobs(1_000_000);

        self::assertSame(0, $job->runs);
        self::assertStringContainsString('busy', $lines[0]);

        $otherConnection->releaseLock('job_runner');
        $runner->runDueJobs(1_000_000);
        self::assertSame(1, $job->runs);
    }

    public function testRunsAJobOnlyWhenItIsDue(): void
    {
        $job = $this->countingJob();
        $runner = $this->runner($this->database, [$job]);

        $runner->runDueJobs(time());
        $runner->runDueJobs(time());

        self::assertSame(1, $job->runs, 'the interval (one hour) has not passed');
    }

    public function testAFailingJobIsRecordedAndOthersStillRun(): void
    {
        $failing = new class () implements Job {
            public function name(): string { return 'failing'; }
            public function intervalSeconds(): int { return 60; }
            public function run(): string { throw new RuntimeException('broken'); }
        };
        $job = $this->countingJob();

        $lines = $this->runner($this->database, [$failing, $job])->runDueJobs(time());

        self::assertStringContainsString('failed: broken', $lines[0]);
        self::assertSame(1, $job->runs);
    }

    public function testPseudoCronRunsAtMostOnceAMinute(): void
    {
        $job = $this->countingJob(0);
        $runner = $this->runner($this->database, [$job]);
        $now = time();

        $runner->tickAfterRequest($now);
        $runner->tickAfterRequest($now + 30);
        $runner->tickAfterRequest($now + 61);

        self::assertSame(2, $job->runs);
    }

    public function testPseudoCronStepsAsideWhileARealCronRuns(): void
    {
        $job = $this->countingJob(0);
        $runner = $this->runner($this->database, [$job]);
        $now = time();

        $runner->runFromCron($now);
        $runner->tickAfterRequest($now + 120);

        self::assertSame(1, $job->runs, 'only the cron run');
    }

    public function testPseudoCronCanBeSwitchedOff(): void
    {
        $job = $this->countingJob(0);
        (new SettingsRepository($this->database))->set('jobs.pseudo_cron', false);

        $this->runner($this->database, [$job])->tickAfterRequest(time());

        self::assertSame(0, $job->runs);
    }

    /**
     * @param list<Job> $jobs
     */
    private function runner(Database $database, array $jobs): JobRunner
    {
        return new JobRunner($database, new SettingsRepository($database), new JobRepository($database), $jobs);
    }

    private function countingJob(int $intervalSeconds = 3600): CountingJob
    {
        return new CountingJob($intervalSeconds);
    }
}
