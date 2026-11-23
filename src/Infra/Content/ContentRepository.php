<?php

declare(strict_types=1);

namespace PhpChess\Infra\Content;

use PhpChess\Infra\Repository;

/**
 * The content_items table (C-06 to C-09, ADM-05): news, server messages,
 * tips of the day, FAQ entries and static pages, all kept by the admin.
 *
 * The text is plain text: templates escape it and turn blank lines into
 * paragraphs, so content can never carry a script (K6).
 */
final class ContentRepository extends Repository
{
    public const string NEWS = 'news';
    public const string SERVER_MESSAGE = 'server_message';
    public const string TIP = 'tip';
    public const string FAQ = 'faq';
    public const string PAGE = 'page';
    public const array KINDS = [self::NEWS, self::SERVER_MESSAGE, self::TIP, self::FAQ, self::PAGE];

    public function create(string $kind, string $slug, string $title, string $body, bool $isPublished, int $sortOrder): int
    {
        $now = $this->now();
        $this->database->run(
            'INSERT INTO ' . $this->table('content_items') . ' (kind, slug, title, body, is_published, sort_order, created_at, updated_at)'
            . ' VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [$kind, $slug, $title, $body, $isPublished ? 1 : 0, $sortOrder, $now, $now],
        );

        return $this->database->lastInsertId();
    }

    public function update(int $id, string $slug, string $title, string $body, bool $isPublished, int $sortOrder): void
    {
        $this->database->run(
            'UPDATE ' . $this->table('content_items') . ' SET slug = ?, title = ?, body = ?, is_published = ?, sort_order = ?, updated_at = ? WHERE id = ?',
            [$slug, $title, $body, $isPublished ? 1 : 0, $sortOrder, $this->now(), $id],
        );
    }

    public function delete(int $id): void
    {
        $this->database->run('DELETE FROM ' . $this->table('content_items') . ' WHERE id = ?', [$id]);
    }

    /**
     * @return array{id: int, kind: string, slug: string, title: string, body: string, is_published: bool, sort_order: int, created_at: string}|null
     */
    public function find(int $id): ?array
    {
        $row = $this->database->fetchRow('SELECT * FROM ' . $this->table('content_items') . ' WHERE id = ?', [$id]);

        return $row === null ? null : self::itemFromRow($row);
    }

    /**
     * A published page by its address name, such as "rules" for /pages/rules.
     *
     * @return array{id: int, kind: string, slug: string, title: string, body: string, is_published: bool, sort_order: int, created_at: string}|null
     */
    public function findPage(string $slug): ?array
    {
        $row = $this->database->fetchRow(
            'SELECT * FROM ' . $this->table('content_items') . ' WHERE kind = ? AND slug = ? AND is_published = 1',
            [self::PAGE, $slug],
        );

        return $row === null ? null : self::itemFromRow($row);
    }

    /**
     * Items of one kind: news newest first, the others in the admin's order.
     *
     * @return list<array{id: int, kind: string, slug: string, title: string, body: string, is_published: bool, sort_order: int, created_at: string}>
     */
    public function listKind(string $kind, bool $publishedOnly, int $limit = 100): array
    {
        $order = $kind === self::NEWS || $kind === self::SERVER_MESSAGE ? 'id DESC' : 'sort_order, id';
        $rows = $this->database->fetchAll(
            'SELECT * FROM ' . $this->table('content_items') . ' WHERE kind = ?' . ($publishedOnly ? ' AND is_published = 1' : '')
            . ' ORDER BY ' . $order . ' LIMIT ' . max(1, $limit),
            [$kind],
        );

        return array_map(self::itemFromRow(...), $rows);
    }

    /**
     * @param array<string, mixed> $row
     * @return array{id: int, kind: string, slug: string, title: string, body: string, is_published: bool, sort_order: int, created_at: string}
     */
    private static function itemFromRow(array $row): array
    {
        return [
            'id' => self::intOf($row['id']),
            'kind' => self::textOf($row['kind']),
            'slug' => self::textOf($row['slug']),
            'title' => self::textOf($row['title']),
            'body' => self::textOf($row['body']),
            'is_published' => self::intOf($row['is_published']) === 1,
            'sort_order' => self::intOf($row['sort_order']),
            'created_at' => self::textOf($row['created_at']),
        ];
    }
}
