<?php

declare(strict_types=1);

namespace PhpChess\Infra\Identity;

use PhpChess\Infra\Repository;

/**
 * The users table of the Identity module, as far as the installer needs it.
 *
 * Owns the table "users". In C1-1 it only creates the first admin account
 * (installer step 4) and counts admins; chunk C1-2 adds the rest of Identity.
 */
final class UserRepository extends Repository
{
    /** Creates an admin account and returns its id. The password must already be hashed. */
    public function createAdmin(string $username, string $email, string $passwordHash): int
    {
        $this->database->run(
            'INSERT INTO ' . $this->table('users')
            . ' (username, email, password_hash, role, state, created_at)'
            . " VALUES (?, ?, ?, 'admin', 'active', UTC_TIMESTAMP(3))",
            [$username, $email, $passwordHash],
        );

        return $this->database->lastInsertId();
    }

    /** How many admin accounts exist. */
    public function countAdmins(): int
    {
        $count = $this->database->fetchValue(
            'SELECT COUNT(*) FROM ' . $this->table('users') . " WHERE role = 'admin'",
        );

        return is_numeric($count) ? (int) $count : 0;
    }
}
