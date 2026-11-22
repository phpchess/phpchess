<?php

declare(strict_types=1);

namespace PhpChess\Infra\System;

use PhpChess\Infra\Repository;

/**
 * Rate limits kept in the database (API-04, ARCHITECTURE.md §9).
 *
 * Owns the table "rate_limits". A "bucket" names what is counted, for example
 * "api:ip:203.0.113.5" or, from C1-2, "login:user:42". Time is cut into fixed
 * windows (for example whole minutes); each hit adds one to the bucket's
 * counter for the current window. Old counters are removed by the CleanupJob.
 */
final class RateLimiter extends Repository
{
    /**
     * Counts one hit and says whether it is still within the limit.
     *
     * @param string $bucket         what is counted
     * @param int    $limit          hits allowed per window
     * @param int    $windowSeconds  length of the window
     * @param int    $now            the current Unix time
     * @return bool true when allowed, false when the limit is passed
     */
    public function hit(string $bucket, int $limit, int $windowSeconds, int $now): bool
    {
        $windowStart = gmdate('Y-m-d H:i:s', intdiv($now, $windowSeconds) * $windowSeconds);

        $this->database->run(
            'INSERT INTO ' . $this->table('rate_limits') . ' (bucket, window_start, hits) VALUES (?, ?, 1)'
            . ' ON DUPLICATE KEY UPDATE hits = hits + 1',
            [mb_substr($bucket, 0, 190), $windowStart],
        );

        $hits = $this->database->fetchValue(
            'SELECT hits FROM ' . $this->table('rate_limits') . ' WHERE bucket = ? AND window_start = ?',
            [mb_substr($bucket, 0, 190), $windowStart],
        );

        return is_numeric($hits) && (int) $hits <= $limit;
    }

    /** Seconds until the current window ends: the value for a Retry-After header. */
    public static function secondsUntilNextWindow(int $windowSeconds, int $now): int
    {
        return $windowSeconds - ($now % $windowSeconds);
    }
}
