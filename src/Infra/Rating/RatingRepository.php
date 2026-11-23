<?php

declare(strict_types=1);

namespace PhpChess\Infra\Rating;

use PhpChess\Infra\Repository;

/**
 * The ratings and rating_history tables (R-01 to R-04, K4).
 *
 * Owns the tables "ratings" and "rating_history". The unique key
 * (game_id, user_id) of rating_history is what makes a rating impossible to
 * apply twice for one game (K4): addHistory() returns false on a repeat,
 * and the Ratings service then changes nothing.
 */
final class RatingRepository extends Repository
{
    // --- Current ratings ---

    /**
     * The user's rating row at a speed, or null when they have none yet.
     *
     * @return array{rating: int, games_played: int, wins: int, draws: int, losses: int}|null
     */
    public function find(int $userId, string $speed, bool $forUpdate = false): ?array
    {
        $row = $this->database->fetchRow(
            'SELECT rating, games_played, wins, draws, losses FROM ' . $this->table('ratings') . ' WHERE user_id = ? AND speed = ?'
            . ($forUpdate ? ' FOR UPDATE' : ''),
            [$userId, $speed],
        );
        if ($row === null) {
            return null;
        }

        return [
            'rating' => self::intOf($row['rating']),
            'games_played' => self::intOf($row['games_played']),
            'wins' => self::intOf($row['wins']),
            'draws' => self::intOf($row['draws']),
            'losses' => self::intOf($row['losses']),
        ];
    }

    /** Creates the rating row with a start value, if the user has none at this speed. */
    public function createIfMissing(int $userId, string $speed, int $startRating): void
    {
        $this->database->run(
            'INSERT IGNORE INTO ' . $this->table('ratings') . ' (user_id, speed, rating, games_played) VALUES (?, ?, ?, 0)',
            [$userId, $speed, $startRating],
        );
    }

    /** Saves a rating after a game and counts the game as a win, draw or loss. */
    public function saveAfterGame(int $userId, string $speed, int $newRating, float $score): void
    {
        $column = $score === 1.0 ? 'wins' : ($score === 0.0 ? 'losses' : 'draws');
        $this->database->run(
            'UPDATE ' . $this->table('ratings') . ' SET rating = ?, games_played = games_played + 1, '
            . $column . ' = ' . $column . ' + 1 WHERE user_id = ? AND speed = ?',
            [$newRating, $userId, $speed],
        );
    }

    // --- History (R-03) ---

    /** Adds one history row. Returns false when this game was already rated for the user (K4). */
    public function addHistory(int $gameId, int $userId, string $speed, int $ratingBefore, int $ratingAfter, int $now): bool
    {
        $statement = $this->database->run(
            'INSERT IGNORE INTO ' . $this->table('rating_history')
            . ' (game_id, user_id, speed, rating_before, rating_after, created_at) VALUES (?, ?, ?, ?, ?, ?)',
            [$gameId, $userId, $speed, $ratingBefore, $ratingAfter, self::dateTime($now)],
        );

        return $statement->rowCount() === 1;
    }

    /**
     * A user's rating history at a speed, oldest first.
     *
     * @return list<array{game_id: int, rating_before: int, rating_after: int, created_at: string}>
     */
    public function history(int $userId, string $speed): array
    {
        $rows = $this->database->fetchAll(
            'SELECT game_id, rating_before, rating_after, created_at FROM ' . $this->table('rating_history')
            . ' WHERE user_id = ? AND speed = ? ORDER BY id',
            [$userId, $speed],
        );
        $history = [];
        foreach ($rows as $row) {
            $history[] = [
                'game_id' => self::intOf($row['game_id']),
                'rating_before' => self::intOf($row['rating_before']),
                'rating_after' => self::intOf($row['rating_after']),
                'created_at' => self::textOf($row['created_at']),
            ];
        }

        return $history;
    }

    /** How many history rows a game has (tests: a rating is applied once, K4). */
    public function countHistoryForGame(int $gameId): int
    {
        return self::intOf($this->database->fetchValue('SELECT COUNT(*) FROM ' . $this->table('rating_history') . ' WHERE game_id = ?', [$gameId]));
    }

    // --- The rating list (R-04) ---

    /**
     * Rated players at a speed, best first, with at least $minimumGames games.
     *
     * @return list<array{user_id: int, username: string, rating: int, games_played: int, wins: int, draws: int, losses: int}>
     */
    public function ratingList(string $speed, int $minimumGames, int $limit = 200): array
    {
        $rows = $this->database->fetchAll(
            'SELECT r.user_id, u.username, r.rating, r.games_played, r.wins, r.draws, r.losses FROM ' . $this->table('ratings') . ' r'
            . ' JOIN ' . $this->table('users') . " u ON u.id = r.user_id WHERE r.speed = ? AND r.games_played >= ? AND u.state = 'active'"
            . ' ORDER BY r.rating DESC, u.username LIMIT ' . max(1, $limit),
            [$speed, $minimumGames],
        );
        $list = [];
        foreach ($rows as $row) {
            $list[] = [
                'user_id' => self::intOf($row['user_id']),
                'username' => self::textOf($row['username']),
                'rating' => self::intOf($row['rating']),
                'games_played' => self::intOf($row['games_played']),
                'wins' => self::intOf($row['wins']),
                'draws' => self::intOf($row['draws']),
                'losses' => self::intOf($row['losses']),
            ];
        }

        return $list;
    }
}
