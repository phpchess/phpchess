<?php

declare(strict_types=1);

namespace PhpChess\Infra\Notify;

use PhpChess\Infra\Repository;

/**
 * The push_subscriptions table (N-03): one row per browser or installed
 * app that allowed notifications.
 *
 * The browser gives us an "endpoint" (an address at the browser maker's
 * push service) and two keys to encrypt with. A member can have several:
 * phone, tablet, laptop. When the push service answers that an endpoint
 * is gone, the row is deleted.
 */
final class PushSubscriptionRepository extends Repository
{
    /** Saves a subscription for a member; the same endpoint again replaces the old row. */
    public function save(int $userId, string $endpoint, string $publicKey, string $authSecret): void
    {
        $this->database->run('DELETE FROM ' . $this->table('push_subscriptions') . ' WHERE endpoint = ?', [$endpoint]);
        $this->database->run(
            'INSERT INTO ' . $this->table('push_subscriptions') . ' (user_id, endpoint, public_key, auth_secret, created_at) VALUES (?, ?, ?, ?, ?)',
            [$userId, $endpoint, $publicKey, $authSecret, $this->now()],
        );
    }

    /**
     * @return list<array{id: int, endpoint: string, public_key: string, auth_secret: string, created_at: string}>
     */
    public function listFor(int $userId): array
    {
        $rows = $this->database->fetchAll(
            'SELECT id, endpoint, public_key, auth_secret, created_at FROM ' . $this->table('push_subscriptions') . ' WHERE user_id = ? ORDER BY id',
            [$userId],
        );

        return array_map(fn (array $row): array => [
            'id' => self::intOf($row['id']),
            'endpoint' => self::textOf($row['endpoint']),
            'public_key' => self::textOf($row['public_key']),
            'auth_secret' => self::textOf($row['auth_secret']),
            'created_at' => self::textOf($row['created_at']),
        ], $rows);
    }

    /** Removes one of a member's subscriptions (the user id keeps others' rows safe). */
    public function remove(int $subscriptionId, int $userId): void
    {
        $this->database->run('DELETE FROM ' . $this->table('push_subscriptions') . ' WHERE id = ? AND user_id = ?', [$subscriptionId, $userId]);
    }

    /** Removes a subscription the push service says is gone. */
    public function removeEndpoint(string $endpoint): void
    {
        $this->database->run('DELETE FROM ' . $this->table('push_subscriptions') . ' WHERE endpoint = ?', [$endpoint]);
    }
}
