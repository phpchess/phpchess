<?php

declare(strict_types=1);

namespace PhpChess\Infra\Community;

use PhpChess\Infra\Repository;

/**
 * The messages table: direct messages between two members (C-04).
 *
 * A conversation is all messages between the same two members, in order.
 * A message is unread until the recipient opens the conversation.
 */
final class MessageRepository extends Repository
{
    public function add(int $fromUserId, int $toUserId, string $body, int $now): int
    {
        $this->database->run(
            'INSERT INTO ' . $this->table('messages') . ' (from_user_id, to_user_id, body, created_at) VALUES (?, ?, ?, ?)',
            [$fromUserId, $toUserId, $body, self::dateTime($now)],
        );

        return $this->database->lastInsertId();
    }

    /**
     * The messages between two members after a given id, oldest first
     * (at most the newest $limit).
     *
     * @return list<array{id: int, from_user_id: int, body: string, created_at: string, is_read: bool}>
     */
    public function conversation(int $userId, int $otherUserId, int $afterId = 0, int $limit = 100): array
    {
        $rows = $this->database->fetchAll(
            'SELECT id, from_user_id, body, created_at, read_at FROM ' . $this->table('messages')
            . ' WHERE ((from_user_id = ? AND to_user_id = ?) OR (from_user_id = ? AND to_user_id = ?)) AND id > ?'
            . ' ORDER BY id DESC LIMIT ' . max(1, $limit),
            [$userId, $otherUserId, $otherUserId, $userId, $afterId],
        );

        $messages = array_map(fn (array $row): array => [
            'id' => self::intOf($row['id']),
            'from_user_id' => self::intOf($row['from_user_id']),
            'body' => self::textOf($row['body']),
            'created_at' => self::textOf($row['created_at']),
            'is_read' => $row['read_at'] !== null,
        ], $rows);

        return array_reverse($messages);
    }

    /**
     * A member's conversations, newest first: the other member, the last
     * message and how many are unread.
     *
     * @return list<array{other_id: int, other_username: string, last_body: string, last_at: string, unread: int}>
     */
    public function conversationsOf(int $userId, int $limit = 50): array
    {
        $rows = $this->database->fetchAll(
            'SELECT IF(from_user_id = ?, to_user_id, from_user_id) AS other_id, MAX(id) AS last_id,'
            . ' SUM(to_user_id = ? AND read_at IS NULL) AS unread'
            . ' FROM ' . $this->table('messages') . ' WHERE from_user_id = ? OR to_user_id = ?'
            . ' GROUP BY other_id ORDER BY last_id DESC LIMIT ' . max(1, $limit),
            [$userId, $userId, $userId, $userId],
        );

        $conversations = [];
        foreach ($rows as $row) {
            $otherId = self::intOf($row['other_id']);
            $last = $this->database->fetchRow(
                'SELECT m.body, m.created_at, u.username FROM ' . $this->table('messages') . ' m'
                . ' JOIN ' . $this->table('users') . ' u ON u.id = ? WHERE m.id = ?',
                [$otherId, self::intOf($row['last_id'])],
            );
            $conversations[] = [
                'other_id' => $otherId,
                'other_username' => self::textOf($last['username'] ?? ''),
                'last_body' => self::textOf($last['body'] ?? ''),
                'last_at' => self::textOf($last['created_at'] ?? ''),
                'unread' => self::intOf($row['unread']),
            ];
        }

        return $conversations;
    }

    public function countUnread(int $userId): int
    {
        return self::intOf($this->database->fetchValue(
            'SELECT COUNT(*) FROM ' . $this->table('messages') . ' WHERE to_user_id = ? AND read_at IS NULL',
            [$userId],
        ));
    }

    /** Marks the messages from $otherUserId to $userId as read (the conversation was opened). */
    public function markRead(int $userId, int $otherUserId, int $now): void
    {
        $this->database->run(
            'UPDATE ' . $this->table('messages') . ' SET read_at = ? WHERE to_user_id = ? AND from_user_id = ? AND read_at IS NULL',
            [self::dateTime($now), $userId, $otherUserId],
        );
    }

    /** How many messages a member sent since a time (the sending limit). */
    public function countSentSince(int $userId, int $since): int
    {
        return self::intOf($this->database->fetchValue(
            'SELECT COUNT(*) FROM ' . $this->table('messages') . ' WHERE from_user_id = ? AND created_at >= ?',
            [$userId, self::dateTime($since)],
        ));
    }
}
