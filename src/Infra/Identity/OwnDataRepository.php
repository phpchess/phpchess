<?php

declare(strict_types=1);

namespace PhpChess\Infra\Identity;

use PhpChess\Infra\Repository;

/**
 * A member's own data, for the export and for account deletion (ID-11).
 *
 * The list of tables is here, in one place, so the export and the
 * deletion can't drift apart: everything exported is what gets removed.
 * Games stay (the opponent played them too), but with the account's name
 * replaced, so they no longer point at a person.
 */
final class OwnDataRepository extends Repository
{
    /**
     * Personal tables: table => the WHERE part picking the member's rows (with ? for the user id, once or twice).
     * Deleted on account deletion, in this order.
     */
    private const array PERSONAL_TABLES = [
        'profiles' => 'user_id = ?',
        'buddies' => 'user_id = ? OR buddy_id = ?',
        'messages' => 'from_user_id = ? OR to_user_id = ?',
        'game_chat' => 'user_id = ?',
        'notices' => 'user_id = ? OR from_user_id = ?',
        'api_tokens' => 'user_id = ?',
        'push_subscriptions' => 'user_id = ?',
        'user_tokens' => 'user_id = ?',
        'user_preferences' => 'user_id = ?',
        'school_class_members' => 'user_id = ?',
        'login_attempts' => 'user_id = ?',
        'vacation_days' => 'user_id = ?',
        'challenges' => 'challenger_id = ? OR opponent_id = ?',
        'newsletter_recipients' => 'user_id = ?',
    ];

    /** Columns never put in an export: secrets, even though they are hashed. */
    private const array SECRET_COLUMNS = ['password_hash', 'token_hash', 'auth_secret', 'public_key', 'invite_token_hash'];

    /**
     * Everything stored about a member: their account row and every personal table,
     * plus the games they played, with moves, and their ratings.
     *
     * @return array<string, list<array<string, mixed>>>
     */
    public function export(int $userId): array
    {
        $data = ['account' => $this->rows('SELECT * FROM ' . $this->table('users') . ' WHERE id = ?', [$userId])];
        foreach (self::PERSONAL_TABLES as $table => $where) {
            $data[$table] = $this->rows('SELECT * FROM ' . $this->table($table) . ' WHERE ' . $where, self::idsFor($where, $userId));
        }
        $data['games'] = $this->rows('SELECT * FROM ' . $this->table('games') . ' WHERE white_id = ? OR black_id = ? ORDER BY id', [$userId, $userId]);
        $data['game_moves'] = $this->rows(
            'SELECT m.* FROM ' . $this->table('game_moves') . ' m JOIN ' . $this->table('games') . ' g ON g.id = m.game_id'
            . ' WHERE g.white_id = ? OR g.black_id = ? ORDER BY m.game_id, m.ply',
            [$userId, $userId],
        );
        $data['ratings'] = $this->rows('SELECT * FROM ' . $this->table('ratings') . ' WHERE user_id = ?', [$userId]);
        $data['rating_history'] = $this->rows('SELECT * FROM ' . $this->table('rating_history') . ' WHERE user_id = ? ORDER BY id', [$userId]);

        return $data;
    }

    /**
     * Removes every personal row and makes the account anonymous:
     * a neutral name, no email, a password nobody knows, state "deleted".
     */
    public function deleteAndAnonymise(int $userId): void
    {
        $this->database->transaction(function () use ($userId): void {
            foreach (self::PERSONAL_TABLES as $table => $where) {
                $this->database->run('DELETE FROM ' . $this->table($table) . ' WHERE ' . $where, self::idsFor($where, $userId));
            }
            $this->database->run('DELETE FROM ' . $this->table('ratings') . ' WHERE user_id = ?', [$userId]);
            $this->database->run(
                'UPDATE ' . $this->table('users') . " SET username = ?, email = NULL, password_hash = ?, state = 'deleted',"
                . ' email_verified_at = NULL, last_seen_at = NULL, updated_at = ? WHERE id = ?',
                ['deleted-' . $userId, password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT), $this->now(), $userId],
            );
        });
    }

    /**
     * How many personal rows a member still has (0 after deletion; for the tests and the admin).
     */
    public function countPersonalRows(int $userId): int
    {
        $count = 0;
        foreach (self::PERSONAL_TABLES as $table => $where) {
            $count += self::intOf($this->database->fetchValue('SELECT COUNT(*) FROM ' . $this->table($table) . ' WHERE ' . $where, self::idsFor($where, $userId)));
        }

        return $count;
    }

    /**
     * @param list<int> $parameters
     * @return list<array<string, mixed>>
     */
    private function rows(string $sql, array $parameters): array
    {
        $rows = [];
        foreach ($this->database->fetchAll($sql, $parameters) as $row) {
            foreach (self::SECRET_COLUMNS as $secret) {
                unset($row[$secret]);
            }
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * The user id once for each "?" in the WHERE part.
     *
     * @return list<int>
     */
    private static function idsFor(string $where, int $userId): array
    {
        return array_fill(0, substr_count($where, '?'), $userId);
    }
}
