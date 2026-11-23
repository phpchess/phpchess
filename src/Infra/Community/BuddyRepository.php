<?php

declare(strict_types=1);

namespace PhpChess\Infra\Community;

use PhpChess\Infra\Repository;

/**
 * The buddies table (C-03): the players a member keeps in their list.
 *
 * A buddy is one-sided, like a bookmark: Anna can have Ben in her list
 * without Ben having Anna.
 */
final class BuddyRepository extends Repository
{
    public function add(int $userId, int $buddyId): void
    {
        $this->database->run(
            'INSERT IGNORE INTO ' . $this->table('buddies') . ' (user_id, buddy_id, created_at) VALUES (?, ?, ?)',
            [$userId, $buddyId, $this->now()],
        );
    }

    public function remove(int $userId, int $buddyId): void
    {
        $this->database->run('DELETE FROM ' . $this->table('buddies') . ' WHERE user_id = ? AND buddy_id = ?', [$userId, $buddyId]);
    }

    public function isBuddy(int $userId, int $buddyId): bool
    {
        return $this->database->fetchValue(
            'SELECT 1 FROM ' . $this->table('buddies') . ' WHERE user_id = ? AND buddy_id = ?',
            [$userId, $buddyId],
        ) !== null;
    }

    /**
     * A member's buddies, by name, with when each was last seen.
     *
     * @return list<array{id: int, username: string, last_seen_at: int|null}>
     */
    public function listFor(int $userId): array
    {
        $rows = $this->database->fetchAll(
            'SELECT u.id, u.username, u.last_seen_at FROM ' . $this->table('buddies') . ' b'
            . ' JOIN ' . $this->table('users') . ' u ON u.id = b.buddy_id'
            . " WHERE b.user_id = ? AND u.state = 'active' ORDER BY u.username",
            [$userId],
        );

        return array_map(fn (array $row): array => [
            'id' => self::intOf($row['id']),
            'username' => self::textOf($row['username']),
            'last_seen_at' => self::timestampOrNull($row['last_seen_at']),
        ], $rows);
    }
}
