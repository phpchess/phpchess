<?php

declare(strict_types=1);

namespace PhpChess\Infra\Identity;

use PhpChess\Domain\Identity\User;
use PhpChess\Infra\Repository;

/**
 * The users table of the Identity module (ID-01, ID-07, ID-08, ID-09).
 *
 * Owns the table "users". Every read returns a User (without the password
 * hash); only passwordHashFor() returns the hash, for the LoginService.
 * Names and emails are compared without regard to case, as the table's
 * collation does.
 */
final class UserRepository extends Repository
{
    private const string COLUMNS =
        'id, username, email, role, state, email_verified_at, approved_at, created_at, last_login_at';

    // --- Creating ---

    /**
     * Creates the first admin account (installer) and returns its id: active,
     * verified and approved. The password must already be hashed. Runs after
     * all migrations, so every column exists.
     */
    public function createAdmin(string $username, string $email, string $passwordHash): int
    {
        $this->database->run(
            'INSERT INTO ' . $this->table('users')
            . ' (username, email, password_hash, role, state, created_at, email_verified_at, approved_at)'
            . " VALUES (?, ?, ?, 'admin', 'active', UTC_TIMESTAMP(3), UTC_TIMESTAMP(3), UTC_TIMESTAMP(3))",
            [$username, $email, $passwordHash],
        );

        return $this->database->lastInsertId();
    }

    /** Creates an account with the given role and state; returns its id. */
    public function create(string $username, string $email, string $passwordHash, string $role, string $state): int
    {
        $this->database->run(
            'INSERT INTO ' . $this->table('users')
            . ' (username, email, password_hash, role, state, created_at, updated_at)'
            . ' VALUES (?, ?, ?, ?, ?, UTC_TIMESTAMP(3), UTC_TIMESTAMP(3))',
            [$username, $email, $passwordHash, $role, $state],
        );

        return $this->database->lastInsertId();
    }

    // --- Finding ---

    public function findById(int $id): ?User
    {
        return $this->toUser($this->database->fetchRow(
            'SELECT ' . self::COLUMNS . ' FROM ' . $this->table('users') . ' WHERE id = ?',
            [$id],
        ));
    }

    /** Finds an account by user name or by email address (both are unique). */
    public function findByUsernameOrEmail(string $nameOrEmail): ?User
    {
        return $this->toUser($this->database->fetchRow(
            'SELECT ' . self::COLUMNS . ' FROM ' . $this->table('users') . ' WHERE username = ? OR email = ? LIMIT 1',
            [$nameOrEmail, $nameOrEmail],
        ));
    }

    public function usernameExists(string $username): bool
    {
        return $this->database->fetchValue('SELECT id FROM ' . $this->table('users') . ' WHERE username = ?', [$username]) !== null;
    }

    public function emailExists(string $email): bool
    {
        return $this->database->fetchValue('SELECT id FROM ' . $this->table('users') . ' WHERE email = ?', [$email]) !== null;
    }

    /** The stored password hash, for checking a password at login. */
    public function passwordHashFor(int $userId): string
    {
        $hash = $this->database->fetchValue('SELECT password_hash FROM ' . $this->table('users') . ' WHERE id = ?', [$userId]);

        return is_string($hash) ? $hash : '';
    }

    /**
     * Lists accounts for the admin, newest first, optionally filtered (ADM-01).
     *
     * @param string $search  part of a user name or email, or ""
     * @param string $state   one of User::STATES, or "" for all
     * @return list<User>
     */
    public function listForAdmin(string $search, string $state, int $limit = 100): array
    {
        $conditions = ['1 = 1'];
        $parameters = [];
        if ($search !== '') {
            $conditions[] = '(username LIKE ? OR email LIKE ?)';
            $pattern = '%' . addcslashes($search, '%_\\') . '%';
            $parameters[] = $pattern;
            $parameters[] = $pattern;
        }
        if (in_array($state, User::STATES, true)) {
            $conditions[] = 'state = ?';
            $parameters[] = $state;
        }
        $parameters[] = max(1, min($limit, 500));

        $rows = $this->database->fetchAll(
            'SELECT ' . self::COLUMNS . ' FROM ' . $this->table('users')
            . ' WHERE ' . implode(' AND ', $conditions) . ' ORDER BY id DESC LIMIT ?',
            $parameters,
        );

        $users = [];
        foreach ($rows as $row) {
            $user = $this->toUser($row);
            if ($user !== null) {
                $users[] = $user;
            }
        }

        return $users;
    }

    /** How many admin accounts exist. */
    public function countAdmins(): int
    {
        return $this->countWhereRoleAndState(User::ROLE_ADMIN, '');
    }

    /** How many active admins exist (the last one may not be demoted or blocked). */
    public function countActiveAdmins(): int
    {
        return $this->countWhereRoleAndState(User::ROLE_ADMIN, User::STATE_ACTIVE);
    }

    private function countWhereRoleAndState(string $role, string $state): int
    {
        $sql = 'SELECT COUNT(*) FROM ' . $this->table('users') . ' WHERE role = ?';
        $parameters = [$role];
        if ($state !== '') {
            $sql .= ' AND state = ?';
            $parameters[] = $state;
        }
        $count = $this->database->fetchValue($sql, $parameters);

        return is_numeric($count) ? (int) $count : 0;
    }

    // --- Changing ---

    public function setState(int $userId, string $state): void
    {
        $this->updateColumn($userId, 'state = ?', $state);
    }

    public function setRole(int $userId, string $role): void
    {
        $this->updateColumn($userId, 'role = ?', $role);
    }

    /** Renames an account; the id, and so all its games, stay the same (ID-09). */
    public function rename(int $userId, string $newUsername): void
    {
        $this->updateColumn($userId, 'username = ?', $newUsername);
    }

    public function setPasswordHash(int $userId, string $passwordHash): void
    {
        $this->updateColumn($userId, 'password_hash = ?', $passwordHash);
    }

    public function markEmailVerified(int $userId): void
    {
        $this->database->run(
            'UPDATE ' . $this->table('users') . ' SET email_verified_at = UTC_TIMESTAMP(3), updated_at = UTC_TIMESTAMP(3) WHERE id = ?',
            [$userId],
        );
    }

    public function markApproved(int $userId): void
    {
        $this->database->run(
            'UPDATE ' . $this->table('users') . ' SET approved_at = UTC_TIMESTAMP(3), updated_at = UTC_TIMESTAMP(3) WHERE id = ?',
            [$userId],
        );
    }

    public function markLoggedIn(int $userId): void
    {
        $this->database->run(
            'UPDATE ' . $this->table('users') . ' SET last_login_at = UTC_TIMESTAMP(3) WHERE id = ?',
            [$userId],
        );
    }

    /**
     * Sets one column. $assignment is a fixed text from this class ("state = ?"),
     * never from input; the value is a parameter (K5).
     */
    private function updateColumn(int $userId, string $assignment, string $value): void
    {
        $this->database->run(
            'UPDATE ' . $this->table('users') . ' SET ' . $assignment . ', updated_at = UTC_TIMESTAMP(3) WHERE id = ?',
            [$value, $userId],
        );
    }

    // --- Turning a row into a User ---

    /**
     * @param array<string, mixed>|null $row
     */
    private function toUser(?array $row): ?User
    {
        if ($row === null) {
            return null;
        }

        return new User(
            id: (int) self::text($row['id'] ?? 0),
            username: self::text($row['username'] ?? ''),
            email: self::text($row['email'] ?? ''),
            role: self::text($row['role'] ?? ''),
            state: self::text($row['state'] ?? ''),
            isEmailVerified: ($row['email_verified_at'] ?? null) !== null,
            isApproved: ($row['approved_at'] ?? null) !== null,
            createdAt: self::text($row['created_at'] ?? ''),
            lastLoginAt: isset($row['last_login_at']) ? self::text($row['last_login_at']) : null,
        );
    }

    private static function text(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }
}
