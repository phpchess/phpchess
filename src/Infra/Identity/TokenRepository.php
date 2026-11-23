<?php

declare(strict_types=1);

namespace PhpChess\Infra\Identity;

use PhpChess\Infra\Repository;

/**
 * The user_tokens table: one-time and remember-me tokens (ID-02, ID-05, ID-06, ARCHITECTURE.md §9).
 *
 * A token is 256 random bits, shown to the user once (in an email link or a
 * cookie). Only its SHA-256 hash is stored, so a copy of the database can't
 * be used to log in. Every token has a type and an expiry, and is used once:
 * useToken() marks it used in the same statement that checks it.
 */
final class TokenRepository extends Repository
{
    public const string TYPE_VERIFY_EMAIL = 'verify_email';
    public const string TYPE_RESET_PASSWORD = 'reset_password';
    public const string TYPE_REMEMBER_ME = 'remember_me';

    /**
     * Creates a token and returns it (the only time it exists in plain form).
     * Older unused tokens of the same type for the same user are removed, so
     * only the newest email link works.
     */
    public function createToken(int $userId, string $type, int $lifetimeSeconds, bool $replaceOlder = true): string
    {
        if ($replaceOlder) {
            $this->database->run(
                'DELETE FROM ' . $this->table('user_tokens') . ' WHERE user_id = ? AND token_type = ? AND used_at IS NULL',
                [$userId, $type],
            );
        }

        $token = bin2hex(random_bytes(32));
        $this->database->run(
            'INSERT INTO ' . $this->table('user_tokens')
            . ' (user_id, token_type, token_hash, created_at, expires_at)'
            . ' VALUES (?, ?, ?, UTC_TIMESTAMP(3), UTC_TIMESTAMP(3) + INTERVAL ? SECOND)',
            [$userId, $type, self::hash($token), $lifetimeSeconds],
        );

        return $token;
    }

    /**
     * Uses a token: returns its user id and marks it used, or returns null when
     * the token is unknown, of another type, expired or already used.
     */
    public function useToken(string $token, string $type): ?int
    {
        if (preg_match('/^[0-9a-f]{64}$/', $token) !== 1) {
            return null;
        }

        $tokenHash = self::hash($token);
        $userId = $this->database->fetchValue(
            'SELECT user_id FROM ' . $this->table('user_tokens')
            . ' WHERE token_hash = ? AND token_type = ? AND used_at IS NULL AND expires_at > UTC_TIMESTAMP(3)',
            [$tokenHash, $type],
        );
        if (!is_numeric($userId)) {
            return null;
        }

        // Marking it used only succeeds once, even when two requests race.
        $marked = $this->database->run(
            'UPDATE ' . $this->table('user_tokens') . ' SET used_at = UTC_TIMESTAMP(3) WHERE token_hash = ? AND used_at IS NULL',
            [$tokenHash],
        )->rowCount();

        return $marked === 1 ? (int) $userId : null;
    }

    /** Checks a token without using it (to show the "set a new password" form). */
    public function isValid(string $token, string $type): bool
    {
        if (preg_match('/^[0-9a-f]{64}$/', $token) !== 1) {
            return false;
        }

        return $this->database->fetchValue(
            'SELECT id FROM ' . $this->table('user_tokens')
            . ' WHERE token_hash = ? AND token_type = ? AND used_at IS NULL AND expires_at > UTC_TIMESTAMP(3)',
            [self::hash($token), $type],
        ) !== null;
    }

    /** Removes every token of one type for a user (for example all remember-me tokens at logout). */
    public function deleteAllOfType(int $userId, string $type): void
    {
        $this->database->run(
            'DELETE FROM ' . $this->table('user_tokens') . ' WHERE user_id = ? AND token_type = ?',
            [$userId, $type],
        );
    }

    /** Removes tokens that expired or were used more than a day ago (CleanupJob). */
    public function deleteOldTokens(): int
    {
        return $this->database->run(
            'DELETE FROM ' . $this->table('user_tokens')
            . ' WHERE expires_at < UTC_TIMESTAMP(3) OR used_at < UTC_TIMESTAMP(3) - INTERVAL 1 DAY LIMIT 5000',
        )->rowCount();
    }

    private static function hash(string $token): string
    {
        return hash('sha256', $token);
    }
}
