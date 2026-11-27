<?php

declare(strict_types=1);

namespace PhpChess\Infra\Study;

use PhpChess\Domain\Study\MoveTree;
use PhpChess\Domain\Study\StudyBoards;
use PhpChess\Infra\Repository;

/**
 * Study boards and their move trees (C6-1, A132).
 *
 * Tables study_boards and study_moves (migration 14). A tree is always
 * saved whole: the old moves are deleted and the new ones written, in one
 * transaction. Every tree given here was built by MoveTree, which checked
 * every move with our rules, and passed StudyScanner.
 *
 * A board row is returned as an array:
 * {id, owner_id, owner, title, kind, visibility, is_approved, source,
 *  source_id, start_fen, result, tags, updated_at}.
 */
final class StudyBoardRepository extends Repository
{
    private const string BOARD_COLUMNS = 'b.id, b.owner_id, u.username AS owner, b.title, b.kind, b.visibility, b.is_approved,'
        . ' b.source, b.source_id, b.start_fen, b.result, b.tags, b.updated_at';

    // ===== Reading =====

    /** @return array{id: int, owner_id: int, owner: string, title: string, kind: string, visibility: string, is_approved: bool, source: string, source_id: int, start_fen: string, result: string, tags: array<string, string>, updated_at: int}|null */
    public function find(int $boardId): ?array
    {
        $row = $this->database->fetchRow(
            'SELECT ' . self::BOARD_COLUMNS . ' FROM ' . $this->table('study_boards') . ' b'
            . ' LEFT JOIN ' . $this->table('users') . ' u ON u.id = b.owner_id WHERE b.id = ?',
            [$boardId],
        );

        return $row === null ? null : self::boardOf($row);
    }

    /**
     * A member's own boards, the newest change first.
     *
     * @return list<array{id: int, owner_id: int, owner: string, title: string, kind: string, visibility: string, is_approved: bool, source: string, source_id: int, start_fen: string, result: string, tags: array<string, string>, updated_at: int}>
     */
    public function ownBoards(int $ownerId): array
    {
        return $this->boardsWhere('b.owner_id = ?', [$ownerId]);
    }

    /**
     * Public boards: approved ones for everybody; with $waiting, the ones
     * still waiting for an admin (A133).
     *
     * @return list<array{id: int, owner_id: int, owner: string, title: string, kind: string, visibility: string, is_approved: bool, source: string, source_id: int, start_fen: string, result: string, tags: array<string, string>, updated_at: int}>
     */
    public function publicBoards(bool $waiting = false): array
    {
        return $this->boardsWhere('b.visibility = ? AND b.is_approved = ?', [StudyBoards::PUBLIC, $waiting ? 0 : 1]);
    }

    public function countOwn(int $ownerId): int
    {
        return self::intOf($this->database->fetchValue('SELECT COUNT(*) FROM ' . $this->table('study_boards') . ' WHERE owner_id = ?', [$ownerId]));
    }

    /** The board's tree, read back from its moves. */
    public function tree(int $boardId): MoveTree
    {
        $board = $this->database->fetchRow(
            'SELECT start_fen, start_comment, start_drawings FROM ' . $this->table('study_boards') . ' WHERE id = ?',
            [$boardId],
        );
        $rows = $this->database->fetchAll(
            'SELECT node, parent, san, symbols, comment, drawings FROM ' . $this->table('study_moves')
            . ' WHERE board_id = ? ORDER BY parent, sibling_order',
            [$boardId],
        );

        // The rows in the shape MoveTree::fromList() reads; parents before children.
        $entries = [[
            'n' => 0,
            'c' => self::textOf($board['start_comment'] ?? ''),
            'd' => self::listOf(self::textOf($board['start_drawings'] ?? '')),
        ]];
        $byParent = [];
        foreach ($rows as $row) {
            $byParent[self::intOf($row['parent'])][] = [
                'n' => self::intOf($row['node']),
                'p' => self::intOf($row['parent']),
                'san' => self::textOf($row['san']),
                's' => array_map('intval', self::listOf(self::textOf($row['symbols']))),
                'c' => self::textOf($row['comment']),
                'd' => self::listOf(self::textOf($row['drawings'])),
            ];
        }
        $waiting = [0];
        while ($waiting !== []) {
            $parent = array_shift($waiting);
            foreach ($byParent[$parent] ?? [] as $entry) {
                $entries[] = $entry;
                $waiting[] = $entry['n'];
            }
        }

        return MoveTree::fromList(self::textOf($board['start_fen'] ?? ''), $entries, null, $this->authors($boardId));
    }

    /** @return array<int, ?int> node => who added it */
    public function authors(int $boardId): array
    {
        $authors = [];
        foreach ($this->database->fetchAll('SELECT node, author_id FROM ' . $this->table('study_moves') . ' WHERE board_id = ?', [$boardId]) as $row) {
            $authors[self::intOf($row['node'])] = self::intOrNull($row['author_id']);
        }

        return $authors;
    }

    // ===== Writing =====

    /**
     * A new board with its tree; returns its id.
     *
     * @param array<string, string> $tags
     */
    public function create(int $ownerId, string $title, string $kind, string $source, int $sourceId, MoveTree $tree, string $result, array $tags): int
    {
        return $this->database->transaction(function () use ($ownerId, $title, $kind, $source, $sourceId, $tree, $result, $tags): int {
            $now = $this->now();
            $this->database->run(
                'INSERT INTO ' . $this->table('study_boards')
                . ' (owner_id, title, kind, visibility, is_approved, source, source_id, start_fen, result, tags, start_comment, start_drawings, created_at, updated_at)'
                . ' VALUES (?, ?, ?, ?, 0, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [$ownerId, mb_substr($title, 0, 100), $kind, StudyBoards::PRIVATE, $source, $sourceId, $tree->startFen, $result, (string) json_encode($tags), '', '', $now, $now],
            );
            $boardId = $this->database->lastInsertId();
            $this->writeMoves($boardId, $tree);

            return $boardId;
        });
    }

    /** Saves a board's whole tree again (the old moves go). */
    public function saveTree(int $boardId, MoveTree $tree): void
    {
        $this->database->transaction(function () use ($boardId, $tree): void {
            $this->database->run('DELETE FROM ' . $this->table('study_moves') . ' WHERE board_id = ?', [$boardId]);
            $this->writeMoves($boardId, $tree);
            $this->database->run('UPDATE ' . $this->table('study_boards') . ' SET updated_at = ? WHERE id = ?', [$this->now(), $boardId]);
        });
    }

    public function rename(int $boardId, string $title): void
    {
        $this->database->run('UPDATE ' . $this->table('study_boards') . ' SET title = ? WHERE id = ?', [mb_substr($title, 0, 100), $boardId]);
    }

    /** Sets who may see a board; a change always needs a new approval for public (A133). */
    public function setVisibility(int $boardId, string $visibility): void
    {
        $this->database->run('UPDATE ' . $this->table('study_boards') . ' SET visibility = ?, is_approved = 0 WHERE id = ?', [$visibility, $boardId]);
    }

    public function approve(int $boardId): void
    {
        $this->database->run('UPDATE ' . $this->table('study_boards') . ' SET is_approved = 1 WHERE id = ?', [$boardId]);
    }

    public function remove(int $boardId): void
    {
        $this->database->transaction(function () use ($boardId): void {
            $this->database->run('DELETE FROM ' . $this->table('study_moves') . ' WHERE board_id = ?', [$boardId]);
            $this->database->run('DELETE FROM ' . $this->table('study_boards') . ' WHERE id = ?', [$boardId]);
        });
    }

    // ===== Helpers =====

    /** Writes the tree's moves and the start's comment and drawings. */
    private function writeMoves(int $boardId, MoveTree $tree): void
    {
        foreach ($tree->inOrder() as $node) {
            if ($node->number === 0) {
                $this->database->run(
                    'UPDATE ' . $this->table('study_boards') . ' SET start_comment = ?, start_drawings = ? WHERE id = ?',
                    [$node->comment, implode(',', $node->drawings), $boardId],
                );
                continue;
            }
            $this->database->run(
                'INSERT INTO ' . $this->table('study_moves')
                . ' (board_id, node, parent, sibling_order, san, uci, fen_after, symbols, comment, drawings, author_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $boardId, $node->number, $node->parent,
                    (int) array_search($node->number, $tree->childrenOf($node->parent), true),
                    $node->san, $node->uci, $node->fenAfter,
                    implode(',', $node->symbols), $node->comment, implode(',', $node->drawings), $node->authorId,
                ],
            );
        }
    }

    /**
     * @param list<string|int> $parameters
     * @return list<array{id: int, owner_id: int, owner: string, title: string, kind: string, visibility: string, is_approved: bool, source: string, source_id: int, start_fen: string, result: string, tags: array<string, string>, updated_at: int}>
     */
    private function boardsWhere(string $condition, array $parameters): array
    {
        $rows = $this->database->fetchAll(
            'SELECT ' . self::BOARD_COLUMNS . ' FROM ' . $this->table('study_boards') . ' b'
            . ' LEFT JOIN ' . $this->table('users') . ' u ON u.id = b.owner_id WHERE ' . $condition . ' ORDER BY b.updated_at DESC, b.id DESC',
            $parameters,
        );

        return array_values(array_map(static fn (array $row): array => self::boardOf($row), $rows));
    }

    /**
     * @param array<string, mixed> $row
     * @return array{id: int, owner_id: int, owner: string, title: string, kind: string, visibility: string, is_approved: bool, source: string, source_id: int, start_fen: string, result: string, tags: array<string, string>, updated_at: int}
     */
    private static function boardOf(array $row): array
    {
        $tags = json_decode(self::textOf($row['tags']), true);
        $cleanTags = [];
        foreach (is_array($tags) ? $tags : [] as $name => $value) {
            if (is_string($name) && is_string($value)) {
                $cleanTags[$name] = $value;
            }
        }

        return [
            'id' => self::intOf($row['id']),
            'owner_id' => self::intOf($row['owner_id']),
            'owner' => self::textOf($row['owner']),
            'title' => self::textOf($row['title']),
            'kind' => self::textOf($row['kind']),
            'visibility' => self::textOf($row['visibility']),
            'is_approved' => self::intOf($row['is_approved']) === 1,
            'source' => self::textOf($row['source']),
            'source_id' => self::intOf($row['source_id']),
            'start_fen' => self::textOf($row['start_fen']),
            'result' => self::textOf($row['result']),
            'tags' => $cleanTags,
            'updated_at' => self::timestampOrNull($row['updated_at']) ?? 0,
        ];
    }

    /** @return list<string> "a,b" => ['a', 'b']; '' => [] */
    private static function listOf(string $text): array
    {
        return $text === '' ? [] : explode(',', $text);
    }
}
