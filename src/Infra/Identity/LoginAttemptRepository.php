<?php

declare(strict_types=1);

namespace PhpChess\Infra\Identity;

use PhpChess\Infra\Repository;

/**
 * The login_attempts table: a record of every login try (ID-04).
 *
 * Owns the table "login_attempts". The admin can see failed attempts; the
 * throttling itself uses the rate limiter. Old rows are removed by the CleanupJob.
 */
final class LoginAttemptRepository extends Repository
{
    public function record(string $usernameTried, ?int $userId, string $ipAddress, bool $succeeded): void
    {
        $this->database->run(
            'INSERT INTO ' . $this->table('login_attempts')
            . ' (attempted_at, username_tried, user_id, ip_address, succeeded) VALUES (UTC_TIMESTAMP(3), ?, ?, ?, ?)',
            [mb_substr($usernameTried, 0, 190), $userId, mb_substr($ipAddress, 0, 45), $succeeded ? 1 : 0],
        );
    }

    /** Removes attempts older than 90 days (CleanupJob). */
    public function deleteOlderThan90Days(): int
    {
        return $this->database->run(
            'DELETE FROM ' . $this->table('login_attempts') . ' WHERE attempted_at < UTC_TIMESTAMP(3) - INTERVAL 90 DAY LIMIT 5000',
        )->rowCount();
    }
}
