<?php

declare(strict_types=1);

namespace PhpChess\Infra\Play;

use PhpChess\Domain\Play\Challenge;
use PhpChess\Infra\Repository;

/**
 * The challenges table (G-05, G-20, G-21, A35).
 *
 * Owns the table "challenges". take() is how a challenge is accepted: one
 * UPDATE that only succeeds while the challenge is still waiting, so an open
 * challenge can be accepted once, even when two players click at the same
 * moment (the database decides who was first).
 */
final class ChallengeRepository extends Repository
{
    // --- Creating ---

    public function create(string $kind, int $challengerId, ?int $opponentId, ?string $inviteTokenHash, string $colour, int $daysPerMove, bool $isRated, ?string $startFen, int $now, int $expiresAt): int
    {
        $this->database->run(
            'INSERT INTO ' . $this->table('challenges')
            . ' (kind, challenger_id, opponent_id, invite_token_hash, colour, days_per_move, is_rated, start_fen, state, created_at, expires_at)'
            . " VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'waiting', ?, ?)",
            [$kind, $challengerId, $opponentId, $inviteTokenHash, $colour, $daysPerMove, $isRated ? 1 : 0, $startFen, self::dateTime($now), self::dateTime($expiresAt)],
        );

        return $this->database->lastInsertId();
    }

    // --- Reading ---

    public function find(int $challengeId): ?Challenge
    {
        return $this->toChallenge($this->database->fetchRow('SELECT * FROM ' . $this->table('challenges') . ' WHERE id = ?', [$challengeId]));
    }

    public function findByInviteTokenHash(string $tokenHash): ?Challenge
    {
        return $this->toChallenge($this->database->fetchRow('SELECT * FROM ' . $this->table('challenges') . ' WHERE invite_token_hash = ?', [$tokenHash]));
    }

    /**
     * Waiting open challenges, oldest first: the lobby.
     *
     * @return list<Challenge>
     */
    public function waitingOpen(int $now, int $limit = 100): array
    {
        return $this->toChallenges($this->database->fetchAll(
            'SELECT * FROM ' . $this->table('challenges') . " WHERE kind = 'open' AND state = 'waiting' AND expires_at > ? ORDER BY id LIMIT " . max(1, $limit),
            [self::dateTime($now)],
        ));
    }

    /**
     * Waiting challenges a user sent, or received directly.
     *
     * @return list<Challenge>
     */
    public function waitingFor(int $userId, int $now): array
    {
        return $this->toChallenges($this->database->fetchAll(
            'SELECT * FROM ' . $this->table('challenges') . " WHERE state = 'waiting' AND expires_at > ? AND (challenger_id = ? OR opponent_id = ?) ORDER BY id DESC",
            [self::dateTime($now), $userId, $userId],
        ));
    }

    // --- Changing state ---

    /**
     * Takes a waiting challenge: marks it accepted by this user. Returns
     * false when someone was first, it expired, or it is no longer waiting.
     */
    public function take(int $challengeId, int $acceptingUserId, int $now): bool
    {
        $statement = $this->database->run(
            'UPDATE ' . $this->table('challenges') . " SET state = 'accepted', opponent_id = ?"
            . " WHERE id = ? AND state = 'waiting' AND expires_at > ?",
            [$acceptingUserId, $challengeId, self::dateTime($now)],
        );

        return $statement->rowCount() === 1;
    }

    public function setGame(int $challengeId, int $gameId): void
    {
        $this->database->run('UPDATE ' . $this->table('challenges') . ' SET game_id = ? WHERE id = ?', [$gameId, $challengeId]);
    }

    /** Moves a waiting challenge to another state (declined, cancelled). Returns false when it was not waiting. */
    public function close(int $challengeId, string $state): bool
    {
        $statement = $this->database->run(
            'UPDATE ' . $this->table('challenges') . " SET state = ? WHERE id = ? AND state = 'waiting'",
            [$state, $challengeId],
        );

        return $statement->rowCount() === 1;
    }

    /** Marks every waiting challenge past its expiry as expired (the clock job). Returns how many. */
    public function expireOld(int $now): int
    {
        return $this->database->run(
            'UPDATE ' . $this->table('challenges') . " SET state = 'expired' WHERE state = 'waiting' AND expires_at <= ?",
            [self::dateTime($now)],
        )->rowCount();
    }

    // --- Turning rows into Challenges ---

    /**
     * @param list<array<string, mixed>> $rows
     * @return list<Challenge>
     */
    private function toChallenges(array $rows): array
    {
        $challenges = [];
        foreach ($rows as $row) {
            $challenge = $this->toChallenge($row);
            if ($challenge !== null) {
                $challenges[] = $challenge;
            }
        }

        return $challenges;
    }

    /**
     * @param array<string, mixed>|null $row
     */
    private function toChallenge(?array $row): ?Challenge
    {
        if ($row === null) {
            return null;
        }

        return new Challenge(
            id: self::intOf($row['id']),
            kind: self::textOf($row['kind']),
            challengerId: self::intOf($row['challenger_id']),
            opponentId: self::intOrNull($row['opponent_id']),
            colour: self::textOf($row['colour']),
            daysPerMove: self::intOf($row['days_per_move']),
            isRated: self::intOf($row['is_rated']) === 1,
            startFen: isset($row['start_fen']) ? self::textOf($row['start_fen']) : null,
            state: self::textOf($row['state']),
            gameId: self::intOrNull($row['game_id']),
            createdAt: self::timestampOrNull($row['created_at']) ?? 0,
            expiresAt: self::timestampOrNull($row['expires_at']) ?? 0,
        );
    }
}
