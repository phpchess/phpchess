<?php

declare(strict_types=1);

namespace PhpChess\Infra\Play;

use PhpChess\Domain\Play\Game;
use PhpChess\Infra\Repository;

/**
 * The games and game_moves tables (G-02, G-03, G-07, G-11).
 *
 * Owns the tables "games" and "game_moves". Used by the Games service, which
 * keeps the contracts:
 * - K2: findForUpdate() locks the game row; call it inside a transaction.
 * - K3: writeResult() is the only code that writes games.result, and only
 *       Games::end() calls it.
 */
final class GameRepository extends Repository
{
    // --- Creating ---

    /** Creates a running game and returns its id. */
    public function create(int $whiteId, int $blackId, string $speed, ?int $daysPerMove, bool $isRated, string $startFen, int $now, ?int $moveDeadlineAt): int
    {
        $this->database->run(
            'INSERT INTO ' . $this->table('games')
            . ' (white_id, black_id, speed, days_per_move, is_rated, start_fen, current_fen, ply, state, started_at, move_deadline_at)'
            . " VALUES (?, ?, ?, ?, ?, ?, ?, 0, 'running', ?, ?)",
            [
                $whiteId, $blackId, $speed, $daysPerMove, $isRated ? 1 : 0, $startFen, $startFen,
                self::dateTime($now), $moveDeadlineAt === null ? null : self::dateTime($moveDeadlineAt),
            ],
        );

        return $this->database->lastInsertId();
    }

    // --- Reading ---

    public function find(int $gameId): ?Game
    {
        return $this->toGame($this->database->fetchRow('SELECT * FROM ' . $this->table('games') . ' WHERE id = ?', [$gameId]));
    }

    /** Reads the game and locks its row until the transaction ends (K2). */
    public function findForUpdate(int $gameId): ?Game
    {
        return $this->toGame($this->database->fetchRow('SELECT * FROM ' . $this->table('games') . ' WHERE id = ? FOR UPDATE', [$gameId]));
    }

    /**
     * A player's games, newest first: running ones, or finished ones (the archive, G-11).
     *
     * @return list<Game>
     */
    public function listForPlayer(int $userId, string $state, int $limit = 100): array
    {
        $rows = $this->database->fetchAll(
            'SELECT * FROM ' . $this->table('games') . ' WHERE (white_id = ? OR black_id = ?) AND state = ?'
            . ' ORDER BY COALESCE(ended_at, last_move_at, started_at) DESC, id DESC LIMIT ' . max(1, $limit),
            [$userId, $userId, $state],
        );

        return $this->toGames($rows);
    }

    /**
     * Games of everyone, newest first, for watching (G-13) and the admin's list (ADM-04).
     *
     * @return list<Game>
     */
    public function listAll(string $state, int $limit = 100): array
    {
        $rows = $this->database->fetchAll(
            'SELECT * FROM ' . $this->table('games') . ' WHERE state = ?'
            . ' ORDER BY COALESCE(ended_at, last_move_at, started_at) DESC, id DESC LIMIT ' . max(1, $limit),
            [$state],
        );

        return $this->toGames($rows);
    }

    /**
     * Running games whose move deadline has passed, for the clock job.
     *
     * @return list<int>
     */
    public function overdueGameIds(int $now, int $limit = 50): array
    {
        $rows = $this->database->fetchAll(
            'SELECT id FROM ' . $this->table('games') . " WHERE state = 'running' AND move_deadline_at < ?"
            . ' ORDER BY move_deadline_at LIMIT ' . max(1, $limit),
            [self::dateTime($now)],
        );

        return array_map(fn (array $row): int => self::intOf($row['id']), $rows);
    }

    /**
     * Running correspondence games of a player, for vacations (A29).
     *
     * @return list<Game>
     */
    public function runningCorrespondenceGamesOf(int $userId): array
    {
        $rows = $this->database->fetchAll(
            'SELECT * FROM ' . $this->table('games') . " WHERE state = 'running' AND speed = 'correspondence' AND (white_id = ? OR black_id = ?)",
            [$userId, $userId],
        );

        return $this->toGames($rows);
    }

    // --- Moves ---

    /**
     * Every move of the game in order, from ply 1, or only the ones after $sinceply.
     *
     * @return list<array{ply: int, uci: string, san: string, fen_after: string}>
     */
    public function moves(int $gameId, int $sincePly = 0): array
    {
        $rows = $this->database->fetchAll(
            'SELECT ply, uci, san, fen_after FROM ' . $this->table('game_moves') . ' WHERE game_id = ? AND ply > ? ORDER BY ply',
            [$gameId, $sincePly],
        );
        $moves = [];
        foreach ($rows as $row) {
            $moves[] = [
                'ply' => self::intOf($row['ply']),
                'uci' => self::textOf($row['uci']),
                'san' => self::textOf($row['san']),
                'fen_after' => self::textOf($row['fen_after']),
            ];
        }

        return $moves;
    }

    /**
     * Stores one move and the game's new position (inside Games::move's
     * transaction). The primary key (game_id, ply) refuses a second move
     * with the same number, K2.
     */
    public function addMove(int $gameId, int $ply, string $uci, string $san, string $fenAfter, int $now, ?int $moveDeadlineAt): void
    {
        $this->database->run(
            'INSERT INTO ' . $this->table('game_moves') . ' (game_id, ply, uci, san, fen_after, played_at) VALUES (?, ?, ?, ?, ?, ?)',
            [$gameId, $ply, $uci, $san, $fenAfter, self::dateTime($now)],
        );
        // A move answers any draw offer: it is declined by playing on.
        $this->database->run(
            'UPDATE ' . $this->table('games') . ' SET current_fen = ?, ply = ?, last_move_at = ?, move_deadline_at = ?, draw_offered_by = NULL WHERE id = ?',
            [$fenAfter, $ply, self::dateTime($now), $moveDeadlineAt === null ? null : self::dateTime($moveDeadlineAt), $gameId],
        );
    }

    // --- The end of a game (K3) ---

    /** Writes the result. Only Games::end() calls this, with the row locked. */
    public function writeResult(int $gameId, string $result, string $reason, int $now): void
    {
        $this->database->run(
            'UPDATE ' . $this->table('games') . " SET state = 'finished', result = ?, end_reason = ?, ended_at = ?,"
            . " move_deadline_at = NULL, draw_offered_by = NULL WHERE id = ? AND state = 'running'",
            [$result, $reason, self::dateTime($now), $gameId],
        );
    }

    // --- Offers, clocks and presence ---

    public function setDrawOffer(int $gameId, ?int $userId): void
    {
        $this->database->run('UPDATE ' . $this->table('games') . ' SET draw_offered_by = ? WHERE id = ?', [$userId, $gameId]);
    }

    public function setRematchOffer(int $gameId, ?int $userId): void
    {
        $this->database->run('UPDATE ' . $this->table('games') . ' SET rematch_offered_by = ? WHERE id = ?', [$userId, $gameId]);
    }

    public function setRematchGame(int $gameId, int $rematchGameId): void
    {
        $this->database->run('UPDATE ' . $this->table('games') . ' SET rematch_game_id = ?, rematch_offered_by = NULL WHERE id = ?', [$rematchGameId, $gameId]);
    }

    public function setDeadline(int $gameId, ?int $moveDeadlineAt): void
    {
        $this->database->run(
            'UPDATE ' . $this->table('games') . ' SET move_deadline_at = ? WHERE id = ?',
            [$moveDeadlineAt === null ? null : self::dateTime($moveDeadlineAt), $gameId],
        );
    }

    /** Notes that a player has the game open now (G-10, live delivery). */
    public function markSeen(int $gameId, string $colour, int $now): void
    {
        $column = $colour === 'w' ? 'white_seen_at' : 'black_seen_at';
        $this->database->run('UPDATE ' . $this->table('games') . ' SET ' . $column . ' = ? WHERE id = ?', [self::dateTime($now), $gameId]);
    }

    // --- Turning rows into Games ---

    /**
     * @param list<array<string, mixed>> $rows
     * @return list<Game>
     */
    private function toGames(array $rows): array
    {
        $games = [];
        foreach ($rows as $row) {
            $game = $this->toGame($row);
            if ($game !== null) {
                $games[] = $game;
            }
        }

        return $games;
    }

    /**
     * @param array<string, mixed>|null $row
     */
    private function toGame(?array $row): ?Game
    {
        if ($row === null) {
            return null;
        }

        return new Game(
            id: self::intOf($row['id']),
            whiteId: self::intOf($row['white_id']),
            blackId: self::intOf($row['black_id']),
            speed: self::textOf($row['speed']),
            daysPerMove: self::intOrNull($row['days_per_move']),
            isRated: self::intOf($row['is_rated']) === 1,
            startFen: self::textOf($row['start_fen']),
            currentFen: self::textOf($row['current_fen']),
            ply: self::intOf($row['ply']),
            state: self::textOf($row['state']),
            result: isset($row['result']) ? self::textOf($row['result']) : null,
            endReason: isset($row['end_reason']) ? self::textOf($row['end_reason']) : null,
            drawOfferedBy: self::intOrNull($row['draw_offered_by']),
            rematchOfferedBy: self::intOrNull($row['rematch_offered_by']),
            rematchGameId: self::intOrNull($row['rematch_game_id']),
            startedAt: self::timestampOrNull($row['started_at']) ?? 0,
            lastMoveAt: self::timestampOrNull($row['last_move_at']),
            moveDeadlineAt: self::timestampOrNull($row['move_deadline_at']),
            endedAt: self::timestampOrNull($row['ended_at']),
            whiteSeenAt: self::timestampOrNull($row['white_seen_at']),
            blackSeenAt: self::timestampOrNull($row['black_seen_at']),
        );
    }
}
