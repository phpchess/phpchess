<?php

declare(strict_types=1);

namespace PhpChess\Infra\Lichess;

use PhpChess\Domain\Lichess\ReadGame;
use PhpChess\Infra\Repository;

/**
 * Study themes and study games (C5-1, L-03, A78, A88).
 *
 * Tables study_themes and study_games (migration 10). Every game stored
 * here has passed the content scanner (A81); this class never stores a
 * PGN text it was not given as a scanned ReadGame.
 *
 * A player's own game is removed some days after its import (B37, A92),
 * unless the player keeps it (is_kept). Theme games are never removed
 * by time.
 */
final class StudyRepository extends Repository
{
    // --- Themes ---

    /** @return list<array{id: int, title: string, games: int}> newest first */
    public function themes(): array
    {
        $rows = $this->database->fetchAll(
            'SELECT t.id, t.title, (SELECT COUNT(*) FROM ' . $this->table('study_games') . ' g WHERE g.theme_id = t.id) AS games'
            . ' FROM ' . $this->table('study_themes') . ' t ORDER BY t.id DESC',
        );

        return array_map(static fn (array $row): array => [
            'id' => self::intOf($row['id']),
            'title' => self::textOf($row['title']),
            'games' => self::intOf($row['games']),
        ], $rows);
    }

    /** @return array{id: int, title: string}|null */
    public function findTheme(int $themeId): ?array
    {
        $row = $this->database->fetchRow('SELECT id, title FROM ' . $this->table('study_themes') . ' WHERE id = ?', [$themeId]);

        return $row === null ? null : ['id' => self::intOf($row['id']), 'title' => self::textOf($row['title'])];
    }

    public function createTheme(string $title, int $adminId): int
    {
        $this->database->run(
            'INSERT INTO ' . $this->table('study_themes') . ' (title, created_by, created_at) VALUES (?, ?, ?)',
            [$title, $adminId, $this->now()],
        );

        return $this->database->lastInsertId();
    }

    /** Removes a theme and its games. */
    public function removeTheme(int $themeId): void
    {
        $this->database->run('DELETE FROM ' . $this->table('study_games') . ' WHERE theme_id = ?', [$themeId]);
        $this->database->run('DELETE FROM ' . $this->table('study_themes') . ' WHERE id = ?', [$themeId]);
    }

    // --- Games ---

    /**
     * Stores a scanned game, in a theme (admin) or as a player's own import.
     *
     * @param string $source  'lichess' or 'upload'
     * @param string $sourceId  the Lichess game id, or ''
     */
    public function addGame(ReadGame $game, ?int $themeId, ?int $ownerId, string $source, string $sourceId, int $importedBy): int
    {
        $this->database->run(
            'INSERT INTO ' . $this->table('study_games')
            . ' (theme_id, owner_id, white, black, event, played_on, result, opening, pgn, source, source_id, imported_by, created_at)'
            . ' VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $themeId,
                $ownerId,
                mb_substr($game->tag('White'), 0, 100),
                mb_substr($game->tag('Black'), 0, 100),
                mb_substr($game->tag('Event'), 0, 100),
                substr($game->tag('Date'), 0, 10),
                $game->result,
                mb_substr($game->tag('Opening'), 0, 100),
                $game->toPgn(),
                $source,
                substr($sourceId, 0, 12),
                $importedBy,
                $this->now(),
            ],
        );

        return $this->database->lastInsertId();
    }

    /**
     * The games of a theme, or a player's own imports.
     *
     * @return list<array{id: int, white: string, black: string, event: string, played_on: string, result: string, opening: string, is_kept: bool, created_at: int}>
     */
    public function gamesOfTheme(int $themeId): array
    {
        return $this->listOf('theme_id = ?', $themeId);
    }

    /** @return list<array{id: int, white: string, black: string, event: string, played_on: string, result: string, opening: string, is_kept: bool, created_at: int}> */
    public function ownGames(int $ownerId): array
    {
        return $this->listOf('owner_id = ?', $ownerId);
    }

    /** How many own imports a player has. */
    public function countOwnGames(int $ownerId): int
    {
        return self::intOf($this->database->fetchValue('SELECT COUNT(*) FROM ' . $this->table('study_games') . ' WHERE owner_id = ?', [$ownerId]));
    }

    /**
     * One game with its PGN.
     *
     * @return array{id: int, theme_id: int|null, owner_id: int|null, white: string, black: string, event: string,
     *               played_on: string, result: string, opening: string, pgn: string, source: string, source_id: string,
     *               is_kept: bool, created_at: int}|null
     */
    public function findGame(int $gameId): ?array
    {
        $row = $this->database->fetchRow(
            'SELECT id, theme_id, owner_id, white, black, event, played_on, result, opening, pgn, source, source_id, is_kept, created_at FROM '
            . $this->table('study_games') . ' WHERE id = ?',
            [$gameId],
        );
        if ($row === null) {
            return null;
        }

        return [
            'id' => self::intOf($row['id']),
            'theme_id' => self::intOrNull($row['theme_id']),
            'owner_id' => self::intOrNull($row['owner_id']),
            'white' => self::textOf($row['white']),
            'black' => self::textOf($row['black']),
            'event' => self::textOf($row['event']),
            'played_on' => self::textOf($row['played_on']),
            'result' => self::textOf($row['result']),
            'opening' => self::textOf($row['opening']),
            'pgn' => self::textOf($row['pgn']),
            'source' => self::textOf($row['source']),
            'source_id' => self::textOf($row['source_id']),
            'is_kept' => self::intOf($row['is_kept']) === 1,
            'created_at' => self::timestampOrNull($row['created_at']) ?? 0,
        ];
    }

    public function removeGame(int $gameId): void
    {
        $this->database->run('DELETE FROM ' . $this->table('study_games') . ' WHERE id = ?', [$gameId]);
    }

    /** Marks one game as kept (never removed by time) or not (B37). */
    public function setKept(int $gameId, bool $isKept): void
    {
        $this->database->run('UPDATE ' . $this->table('study_games') . ' SET is_kept = ? WHERE id = ?', [$isKept ? 1 : 0, $gameId]);
    }

    /**
     * Removes players' own games imported before $before (Unix seconds)
     * that are not kept, at most $limit of them. Theme games stay.
     *
     * @return int  how many were removed
     */
    public function removeOldOwnGames(int $before, int $limit): int
    {
        return $this->database->run(
            'DELETE FROM ' . $this->table('study_games')
            . ' WHERE owner_id IS NOT NULL AND is_kept = 0 AND created_at < ?'
            . ' LIMIT ' . max(1, $limit),
            [self::dateTime($before)],
        )->rowCount();
    }

    /** @return list<array{id: int, white: string, black: string, event: string, played_on: string, result: string, opening: string, is_kept: bool, created_at: int}> */
    private function listOf(string $condition, int $value): array
    {
        $rows = $this->database->fetchAll(
            'SELECT id, white, black, event, played_on, result, opening, is_kept, created_at FROM ' . $this->table('study_games')
            . ' WHERE ' . $condition . ' ORDER BY played_on, id',
            [$value],
        );

        return array_map(static fn (array $row): array => [
            'id' => self::intOf($row['id']),
            'white' => self::textOf($row['white']),
            'black' => self::textOf($row['black']),
            'event' => self::textOf($row['event']),
            'played_on' => self::textOf($row['played_on']),
            'result' => self::textOf($row['result']),
            'opening' => self::textOf($row['opening']),
            'is_kept' => self::intOf($row['is_kept']) === 1,
            'created_at' => self::timestampOrNull($row['created_at']) ?? 0,
        ], $rows);
    }
}
