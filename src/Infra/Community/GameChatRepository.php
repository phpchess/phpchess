<?php

declare(strict_types=1);

namespace PhpChess\Infra\Community;

use PhpChess\Infra\Repository;

/**
 * The game_chat table: the chat lines of a game (G-12).
 */
final class GameChatRepository extends Repository
{
    public function add(int $gameId, int $userId, string $body, int $now): void
    {
        $this->database->run(
            'INSERT INTO ' . $this->table('game_chat') . ' (game_id, user_id, body, created_at) VALUES (?, ?, ?, ?)',
            [$gameId, $userId, $body, self::dateTime($now)],
        );
    }

    /**
     * A game's chat, oldest first, with the writer's name.
     *
     * @return list<array{id: int, user_id: int, username: string, body: string, created_at: string}>
     */
    public function forGame(int $gameId, int $limit = 200): array
    {
        $rows = $this->database->fetchAll(
            'SELECT c.id, c.user_id, c.body, c.created_at, u.username FROM ' . $this->table('game_chat') . ' c'
            . ' JOIN ' . $this->table('users') . ' u ON u.id = c.user_id'
            . ' WHERE c.game_id = ? ORDER BY c.id DESC LIMIT ' . max(1, $limit),
            [$gameId],
        );

        return array_reverse(array_map(fn (array $row): array => [
            'id' => self::intOf($row['id']),
            'user_id' => self::intOf($row['user_id']),
            'username' => self::textOf($row['username']),
            'body' => self::textOf($row['body']),
            'created_at' => self::textOf($row['created_at']),
        ], $rows));
    }
}
