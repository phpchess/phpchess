<?php

declare(strict_types=1);

namespace PhpChess\Infra\Community;

use PhpChess\Infra\Repository;

/**
 * The profiles table (C-01, C-02): a member's avatar and a short text.
 *
 * "avatar" is '' (none), the name of a bundled avatar such as "knight",
 * or "upload" for the member's own picture (a file, see Avatars).
 * A member without a row has neither.
 */
final class ProfileRepository extends Repository
{
    /**
     * @return array{avatar: string, about: string}
     */
    public function find(int $userId): array
    {
        $row = $this->database->fetchRow('SELECT avatar, about FROM ' . $this->table('profiles') . ' WHERE user_id = ?', [$userId]);

        return [
            'avatar' => self::textOf($row['avatar'] ?? ''),
            'about' => self::textOf($row['about'] ?? ''),
        ];
    }

    /**
     * The avatars of several members at once, for lists.
     *
     * @param list<int> $userIds
     * @return array<int, string> user id => avatar
     */
    public function avatarsOf(array $userIds): array
    {
        $userIds = array_values(array_unique(array_filter($userIds, fn (int $id): bool => $id > 0)));
        if ($userIds === []) {
            return [];
        }
        $placeholders = implode(', ', array_fill(0, count($userIds), '?'));
        $rows = $this->database->fetchAll(
            'SELECT user_id, avatar FROM ' . $this->table('profiles') . ' WHERE user_id IN (' . $placeholders . ')',
            $userIds,
        );
        $avatars = [];
        foreach ($rows as $row) {
            $avatars[self::intOf($row['user_id'])] = self::textOf($row['avatar']);
        }

        return $avatars;
    }

    public function saveAbout(int $userId, string $about): void
    {
        $this->database->run(
            'INSERT INTO ' . $this->table('profiles') . ' (user_id, about, updated_at) VALUES (?, ?, ?)'
            . ' ON DUPLICATE KEY UPDATE about = VALUES(about), updated_at = VALUES(updated_at)',
            [$userId, $about, $this->now()],
        );
    }

    public function saveAvatar(int $userId, string $avatar): void
    {
        $this->database->run(
            'INSERT INTO ' . $this->table('profiles') . ' (user_id, avatar, updated_at) VALUES (?, ?, ?)'
            . ' ON DUPLICATE KEY UPDATE avatar = VALUES(avatar), updated_at = VALUES(updated_at)',
            [$userId, $avatar, $this->now()],
        );
    }

    public function delete(int $userId): void
    {
        $this->database->run('DELETE FROM ' . $this->table('profiles') . ' WHERE user_id = ?', [$userId]);
    }
}
