<?php

declare(strict_types=1);

namespace PhpChess\Infra\Jobs;

use PhpChess\Infra\Database;

/**
 * The cleanup job: removes data that is no longer needed (INS-08, retention).
 *
 * In C1-1 it removes old rate-limit counters and old log files. Later chunks
 * add their own cleanup here (expired tokens, old notifications), and C1-2
 * gives admins the cleanup pages that phpChess 4.x had as scripts (A22).
 * Each run deletes a limited number of rows, so it stays short.
 */
final class CleanupJob implements Job
{
    /** Rate-limit counters older than this are deleted. */
    private const int RATE_LIMIT_KEEP_SECONDS = 86400;

    /** Log files older than this are deleted. */
    private const int LOG_KEEP_DAYS = 30;

    private const int ROWS_PER_RUN = 5000;

    public function __construct(
        private readonly Database $database,
        private readonly string $logFolder,
    ) {
    }

    public function name(): string
    {
        return 'cleanup';
    }

    public function intervalSeconds(): int
    {
        return 3600;
    }

    public function run(): string
    {
        $deletedCounters = $this->database->run(
            'DELETE FROM ' . $this->database->table('rate_limits')
            . ' WHERE window_start < UTC_TIMESTAMP() - INTERVAL ' . self::RATE_LIMIT_KEEP_SECONDS . ' SECOND'
            . ' LIMIT ' . self::ROWS_PER_RUN,
        )->rowCount();

        $deletedLogs = $this->deleteOldLogFiles();

        return "{$deletedCounters} rate-limit rows and {$deletedLogs} old log files removed";
    }

    /** Deletes the daily request logs (requests-YYYY-MM-DD.log) older than LOG_KEEP_DAYS. */
    private function deleteOldLogFiles(): int
    {
        $files = glob($this->logFolder . '/requests-*.log');
        if ($files === false) {
            return 0;
        }

        $oldestKept = time() - self::LOG_KEEP_DAYS * 86400;
        $deleted = 0;
        foreach ($files as $file) {
            $modifiedAt = filemtime($file);
            if ($modifiedAt !== false && $modifiedAt < $oldestKept && unlink($file)) {
                $deleted++;
            }
        }

        return $deleted;
    }
}
