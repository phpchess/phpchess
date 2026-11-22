<?php

declare(strict_types=1);

namespace PhpChess\Infra\Jobs;

use PhpChess\Infra\Database;
use PhpChess\Infra\System\JobRepository;
use PhpChess\Infra\System\SettingsRepository;
use Throwable;

/**
 * The job runner: runs the background jobs that are due (INS-08, ARCHITECTURE.md §8).
 *
 * Two ways in, same code:
 * - **With cron**: a real cron calls public/cron.php every minute (runFromCron).
 * - **Without cron** ("pseudo-cron"): after a page has been sent, the Kernel calls
 *   tickAfterRequest(). It runs at most once a minute, and not at all while a
 *   real cron is working. Switched by the setting jobs.pseudo_cron (on by default).
 *
 * A database lock (GET_LOCK) guards every run, so two runners never overlap:
 * a second runner that finds the lock taken returns at once and does nothing.
 */
final class JobRunner
{
    private const string LOCK_NAME = 'job_runner';

    /** Pseudo-cron runs at most once in this many seconds. */
    private const int TICK_SECONDS = 60;

    /** A real cron counts as present when it ran within this many seconds. */
    private const int CRON_PRESENT_SECONDS = 300;

    /**
     * @param list<Job> $jobs
     */
    public function __construct(
        private readonly Database $database,
        private readonly SettingsRepository $settings,
        private readonly JobRepository $jobRecords,
        private readonly array $jobs,
    ) {
    }

    // --- The two ways in ---

    /**
     * Called by public/cron.php. Runs every due job.
     *
     * @return list<string> one line per job run, for the cron log
     */
    public function runFromCron(int $now): array
    {
        $this->settings->set('jobs.last_cron_at', $now);

        return $this->runDueJobs($now);
    }

    /**
     * Called by the Kernel after a page was sent. Runs the due jobs when
     * pseudo-cron is switched on, a minute has passed, and no real cron is active.
     *
     * @return list<string> one line per job run (empty when nothing ran)
     */
    public function tickAfterRequest(int $now): array
    {
        if (!$this->settings->getBool('jobs.pseudo_cron', true)) {
            return [];
        }
        if ($now - $this->settings->getInt('jobs.last_cron_at') < self::CRON_PRESENT_SECONDS) {
            return [];
        }
        if ($now - $this->settings->getInt('jobs.last_tick_at') < self::TICK_SECONDS) {
            return [];
        }

        return $this->runDueJobs($now);
    }

    // --- Running the jobs under the lock ---

    /**
     * Runs every job whose interval has passed. Returns at once, doing nothing,
     * when another runner holds the lock.
     *
     * @return list<string>
     */
    public function runDueJobs(int $now): array
    {
        if (!$this->database->acquireLock(self::LOCK_NAME, 0)) {
            return ['Another job runner is busy; nothing done.'];
        }

        try {
            $this->settings->set('jobs.last_tick_at', $now);

            $lines = [];
            foreach ($this->jobs as $job) {
                if ($this->isDue($job, $now)) {
                    $lines[] = $this->runOneJob($job);
                }
            }

            return $lines;
        } finally {
            $this->database->releaseLock(self::LOCK_NAME);
        }
    }

    private function isDue(Job $job, int $now): bool
    {
        $lastFinishedAt = $this->jobRecords->lastFinishedAt($job->name());

        return $lastFinishedAt === null || $now - $lastFinishedAt >= $job->intervalSeconds();
    }

    /** Runs one job; a failing job is recorded and does not stop the others. */
    private function runOneJob(Job $job): string
    {
        $this->jobRecords->recordStart($job->name());
        try {
            $result = $job->run();
            $this->jobRecords->recordFinish($job->name(), $result, false);

            return $job->name() . ': ' . $result;
        } catch (Throwable $error) {
            $this->jobRecords->recordFinish($job->name(), 'Failed: ' . $error->getMessage(), true);

            return $job->name() . ': failed: ' . $error->getMessage();
        }
    }
}
