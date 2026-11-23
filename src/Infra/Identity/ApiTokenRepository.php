<?php

declare(strict_types=1);

namespace PhpChess\Infra\Identity;

use PhpChess\Infra\Repository;

/**
 * The api_tokens table (ID-10): one access token per device, for the API.
 *
 * Life cycle: a member creates a token in their settings and sees it once;
 * only its SHA-256 hash is stored, like the other tokens. An app sends it as
 * "Authorization: Bearer <token>". Each use notes the time. The member can
 * revoke it at any time, and then it stops working at once.
 */
final class ApiTokenRepository extends Repository
{
    /** Every token starts with this, so a leaked one is easy to recognise. */
    public const string PREFIX = 'pc6_';

    /** At most this many tokens per member. */
    public const int MAX_PER_USER = 20;

    /** Creates a token for a device and returns it (shown to the member once). */
    public function create(int $userId, string $deviceName): string
    {
        $token = self::PREFIX . bin2hex(random_bytes(24));
        $this->database->run(
            'INSERT INTO ' . $this->table('api_tokens') . ' (user_id, device_name, token_hash, created_at) VALUES (?, ?, ?, ?)',
            [$userId, $deviceName, hash('sha256', $token), $this->now()],
        );

        return $token;
    }

    /** The user id for a token, or null when it is unknown or revoked; notes the use. */
    public function userIdFor(string $token): ?int
    {
        if (!str_starts_with($token, self::PREFIX)) {
            return null;
        }
        $row = $this->database->fetchRow(
            'SELECT id, user_id FROM ' . $this->table('api_tokens') . ' WHERE token_hash = ?',
            [hash('sha256', $token)],
        );
        if ($row === null) {
            return null;
        }
        $this->database->run('UPDATE ' . $this->table('api_tokens') . ' SET last_used_at = ? WHERE id = ?', [$this->now(), self::intOf($row['id'])]);

        return self::intOf($row['user_id']);
    }

    /**
     * A member's tokens (never the tokens themselves, only their names and times).
     *
     * @return list<array{id: int, device_name: string, created_at: string, last_used_at: string}>
     */
    public function listFor(int $userId): array
    {
        $rows = $this->database->fetchAll(
            'SELECT id, device_name, created_at, last_used_at FROM ' . $this->table('api_tokens') . ' WHERE user_id = ? ORDER BY id',
            [$userId],
        );

        return array_map(fn (array $row): array => [
            'id' => self::intOf($row['id']),
            'device_name' => self::textOf($row['device_name']),
            'created_at' => self::textOf($row['created_at']),
            'last_used_at' => self::textOf($row['last_used_at']),
        ], $rows);
    }

    public function countFor(int $userId): int
    {
        return self::intOf($this->database->fetchValue('SELECT COUNT(*) FROM ' . $this->table('api_tokens') . ' WHERE user_id = ?', [$userId]));
    }

    /** Revokes one of a member's tokens; the user id makes sure nobody revokes someone else's. */
    public function revoke(int $tokenId, int $userId): void
    {
        $this->database->run('DELETE FROM ' . $this->table('api_tokens') . ' WHERE id = ? AND user_id = ?', [$tokenId, $userId]);
    }
}
