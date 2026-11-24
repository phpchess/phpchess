<?php

declare(strict_types=1);

namespace PhpChess\Infra\Computer;

use PhpChess\Domain\Identity\User;
use PhpChess\Infra\Repository;

/**
 * The user accounts of the computer players (AI-03, AI-06, decision A53).
 *
 * Uses the "users" table, column computer_player_id (migration 7). A computer
 * account has the state "computer" and a password hash nobody can match, so
 * it can never log in.
 */
final class ComputerAccountRepository extends Repository
{
    /** The user id of a computer player's account, or null when it has none yet. */
    public function userIdFor(int $computerPlayerId): ?int
    {
        return self::intOrNull($this->database->fetchValue(
            'SELECT id FROM ' . $this->table('users') . ' WHERE computer_player_id = ?',
            [$computerPlayerId],
        ));
    }

    /** The computer player an account plays as, or null for a human member. */
    public function computerPlayerIdOf(int $userId): ?int
    {
        return self::intOrNull($this->database->fetchValue(
            'SELECT computer_player_id FROM ' . $this->table('users') . ' WHERE id = ?',
            [$userId],
        ));
    }

    /** Creates the account of a computer player and returns its user id. */
    public function create(int $computerPlayerId, string $username): int
    {
        $this->database->run(
            'INSERT INTO ' . $this->table('users')
            . ' (username, email, password_hash, role, state, created_at, updated_at, computer_player_id)'
            . ' VALUES (?, NULL, ?, ?, ?, ?, ?, ?)',
            [$username, '!computer', User::ROLE_PLAYER, User::STATE_COMPUTER, $this->now(), $this->now(), $computerPlayerId],
        );

        return $this->database->lastInsertId();
    }
}
