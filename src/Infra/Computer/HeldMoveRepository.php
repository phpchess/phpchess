<?php

declare(strict_types=1);

namespace PhpChess\Infra\Computer;

use PhpChess\Infra\Repository;

/**
 * The computer's replies that wait for their time (F2 review, A86).
 *
 * Table computer_held_moves (migration 9): one row per game at most. The
 * computer's reply in a correspondence game is decided in the player's
 * browser, kept here, and played by ComputerMoveJob when play_at has come.
 */
final class HeldMoveRepository extends Repository
{
    /**
     * Keeps a reply. A reply already waiting for this game and ply stays as
     * it is (the page may send the same reply again after a reload); a
     * reply for an older ply is replaced. Returns when the waiting reply is played.
     */
    public function hold(int $gameId, int $ply, string $moveUci, int $playAt): int
    {
        $waiting = $this->find($gameId);
        if ($waiting !== null && $waiting['ply'] === $ply) {
            return $waiting['play_at'];
        }
        $this->database->run('DELETE FROM ' . $this->table('computer_held_moves') . ' WHERE game_id = ?', [$gameId]);
        $this->database->run(
            'INSERT INTO ' . $this->table('computer_held_moves') . ' (game_id, ply, move_uci, play_at) VALUES (?, ?, ?, ?)',
            [$gameId, $ply, $moveUci, self::dateTime($playAt)],
        );

        return $playAt;
    }

    /**
     * The reply waiting for a game, or null.
     *
     * @return array{game_id: int, ply: int, move_uci: string, play_at: int}|null
     */
    public function find(int $gameId): ?array
    {
        $row = $this->database->fetchRow(
            'SELECT game_id, ply, move_uci, play_at FROM ' . $this->table('computer_held_moves') . ' WHERE game_id = ?',
            [$gameId],
        );

        return $row === null ? null : self::heldMove($row);
    }

    /**
     * The replies whose time has come, oldest first.
     *
     * @return list<array{game_id: int, ply: int, move_uci: string, play_at: int}>
     */
    public function due(int $now, int $limit): array
    {
        $rows = $this->database->fetchAll(
            'SELECT game_id, ply, move_uci, play_at FROM ' . $this->table('computer_held_moves')
            . ' WHERE play_at <= ? ORDER BY play_at LIMIT ' . max(1, $limit),
            [self::dateTime($now)],
        );

        return array_map(fn (array $row): array => self::heldMove($row), $rows);
    }

    /** Forgets the reply of a game (played, or no longer fitting). */
    public function remove(int $gameId): void
    {
        $this->database->run('DELETE FROM ' . $this->table('computer_held_moves') . ' WHERE game_id = ?', [$gameId]);
    }

    /**
     * @param array<string, mixed> $row
     * @return array{game_id: int, ply: int, move_uci: string, play_at: int}
     */
    private static function heldMove(array $row): array
    {
        return [
            'game_id' => self::intOf($row['game_id']),
            'ply' => self::intOf($row['ply']),
            'move_uci' => self::textOf($row['move_uci']),
            'play_at' => self::timestampOrNull($row['play_at']) ?? 0,
        ];
    }
}
