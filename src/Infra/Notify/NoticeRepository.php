<?php

declare(strict_types=1);

namespace PhpChess\Infra\Notify;

use PhpChess\Infra\Repository;

/**
 * The notices table: in-app notices and the move-email queue (N-01, N-02, A34).
 *
 * Owns the table "notices". A notice is unread until read_at is set; the
 * email job picks unread notices whose emailed_at is still NULL, and claims
 * them with one UPDATE before sending, so no email is sent twice.
 */
final class NoticeRepository extends Repository
{
    // --- Adding ---

    /**
     * Adds a notice, unless the same unread one is already there (same user,
     * kind, game and challenge): a player is told once, not once per event.
     * Returns true when a notice was added.
     */
    public function add(int $userId, string $kind, ?int $gameId, ?int $challengeId, ?int $fromUserId, int $now): bool
    {
        $existing = $this->database->fetchValue(
            'SELECT id FROM ' . $this->table('notices') . ' WHERE user_id = ? AND kind = ? AND game_id <=> ? AND challenge_id <=> ? AND read_at IS NULL',
            [$userId, $kind, $gameId, $challengeId],
        );
        if ($existing !== null) {
            return false;
        }
        $this->database->run(
            'INSERT INTO ' . $this->table('notices') . ' (user_id, kind, game_id, challenge_id, from_user_id, created_at) VALUES (?, ?, ?, ?, ?, ?)',
            [$userId, $kind, $gameId, $challengeId, $fromUserId, self::dateTime($now)],
        );

        return true;
    }

    // --- Reading ---

    /** The badge count: unread notices of a user. */
    public function countUnread(int $userId): int
    {
        return self::intOf($this->database->fetchValue(
            'SELECT COUNT(*) FROM ' . $this->table('notices') . ' WHERE user_id = ? AND read_at IS NULL',
            [$userId],
        ));
    }

    /**
     * A user's newest notices, with the other player's name.
     *
     * @return list<array{id: int, kind: string, game_id: int|null, challenge_id: int|null, from_username: string, created_at: string, is_read: bool}>
     */
    public function newestFor(int $userId, int $limit = 50): array
    {
        $rows = $this->database->fetchAll(
            'SELECT n.id, n.kind, n.game_id, n.challenge_id, n.created_at, n.read_at, u.username AS from_username'
            . ' FROM ' . $this->table('notices') . ' n LEFT JOIN ' . $this->table('users') . ' u ON u.id = n.from_user_id'
            . ' WHERE n.user_id = ? ORDER BY n.id DESC LIMIT ' . max(1, $limit),
            [$userId],
        );

        return array_map(fn (array $row): array => [
            'id' => self::intOf($row['id']),
            'kind' => self::textOf($row['kind']),
            'game_id' => self::intOrNull($row['game_id']),
            'challenge_id' => self::intOrNull($row['challenge_id']),
            'from_username' => self::textOf($row['from_username'] ?? ''),
            'created_at' => self::textOf($row['created_at']),
            'is_read' => $row['read_at'] !== null,
        ], $rows);
    }

    // --- Marking as read ---

    /** Marks a user's notices about one game as read (they opened the game). */
    public function markGameRead(int $userId, int $gameId, int $now): void
    {
        $this->database->run(
            'UPDATE ' . $this->table('notices') . ' SET read_at = ? WHERE user_id = ? AND game_id = ? AND read_at IS NULL',
            [self::dateTime($now), $userId, $gameId],
        );
    }

    public function markChallengeRead(int $userId, int $challengeId, int $now): void
    {
        $this->database->run(
            'UPDATE ' . $this->table('notices') . ' SET read_at = ? WHERE user_id = ? AND challenge_id = ? AND read_at IS NULL',
            [self::dateTime($now), $userId, $challengeId],
        );
    }

    public function markAllRead(int $userId, int $now): void
    {
        $this->database->run(
            'UPDATE ' . $this->table('notices') . ' SET read_at = ? WHERE user_id = ? AND read_at IS NULL',
            [self::dateTime($now), $userId],
        );
    }

    // --- The email queue (N-02, A34) ---

    /**
     * Users with unread notices not yet emailed, created before $createdBefore.
     *
     * @return list<int>
     */
    public function usersWithUnemailedNotices(int $createdBefore, int $limit = 50): array
    {
        $rows = $this->database->fetchAll(
            'SELECT DISTINCT user_id FROM ' . $this->table('notices')
            . ' WHERE emailed_at IS NULL AND read_at IS NULL AND created_at <= ? LIMIT ' . max(1, $limit),
            [self::dateTime($createdBefore)],
        );

        return array_map(fn (array $row): int => self::intOf($row['user_id']), $rows);
    }

    /**
     * Claims a user's unread, un-emailed notices for one email: marks them
     * emailed and returns them. The job runner holds its lock (JobRunner),
     * so no other run claims the same notices; a second run finds none.
     *
     * @return list<array{id: int, kind: string, game_id: int|null, challenge_id: int|null, from_username: string, created_at: string, is_read: bool}>
     */
    public function claimForEmail(int $userId, int $now): array
    {
        $waiting = [];
        foreach ($this->newestFor($userId, 100) as $notice) {
            $isWaiting = $this->database->fetchValue(
                'SELECT COUNT(*) FROM ' . $this->table('notices') . ' WHERE id = ? AND emailed_at IS NULL AND read_at IS NULL',
                [$notice['id']],
            );
            if (self::intOf($isWaiting) === 1) {
                $waiting[] = $notice;
                $this->database->run(
                    'UPDATE ' . $this->table('notices') . ' SET emailed_at = ? WHERE id = ?',
                    [self::dateTime($now), $notice['id']],
                );
            }
        }

        return $waiting;
    }

    /** When the oldest notice still waiting for an email was made, or null when none waits. */
    public function oldestWaitingAt(int $userId): ?int
    {
        return self::timestampOrNull($this->database->fetchValue(
            'SELECT MIN(created_at) FROM ' . $this->table('notices') . ' WHERE user_id = ? AND emailed_at IS NULL AND read_at IS NULL',
            [$userId],
        ));
    }

    /** Marks a user's waiting notices as handled without email (move emails off). */
    public function skipEmail(int $userId, int $now): void
    {
        $this->database->run(
            'UPDATE ' . $this->table('notices') . ' SET emailed_at = ? WHERE user_id = ? AND emailed_at IS NULL',
            [self::dateTime($now), $userId],
        );
    }

    /** The time of the user's last email of notices, for the daily digest; null when never. */
    public function lastEmailedAt(int $userId): ?int
    {
        return self::timestampOrNull($this->database->fetchValue(
            'SELECT MAX(emailed_at) FROM ' . $this->table('notices') . ' WHERE user_id = ?',
            [$userId],
        ));
    }
}
