<?php

declare(strict_types=1);

namespace PhpChess\Infra\Content;

use PhpChess\Infra\Repository;

/**
 * The newsletter tables (ADM-07): newsletters and newsletter_recipients.
 *
 * Writing a newsletter puts one row per recipient in the queue; the
 * newsletter job sends a few each run (A41) and marks them sent, so a
 * second run never sends the same one again.
 */
final class NewsletterRepository extends Repository
{
    /** Saves a newsletter and queues it for every active member with an email address; returns the number queued. */
    public function createAndQueue(string $subject, string $body, int $adminId, int $now): int
    {
        return $this->database->transaction(function () use ($subject, $body, $adminId, $now): int {
            $this->database->run(
                'INSERT INTO ' . $this->table('newsletters') . ' (subject, body, created_by, created_at) VALUES (?, ?, ?, ?)',
                [$subject, $body, $adminId, self::dateTime($now)],
            );
            $newsletterId = $this->database->lastInsertId();

            return $this->database->run(
                'INSERT INTO ' . $this->table('newsletter_recipients') . ' (newsletter_id, user_id)'
                . ' SELECT ?, id FROM ' . $this->table('users') . " WHERE state = 'active' AND email IS NOT NULL AND email <> ''",
                [$newsletterId],
            )->rowCount();
        });
    }

    /**
     * The next letters to send: newsletter and recipient address.
     *
     * @return list<array{newsletter_id: int, user_id: int, email: string, subject: string, body: string}>
     */
    public function nextToSend(int $limit): array
    {
        $rows = $this->database->fetchAll(
            'SELECT r.newsletter_id, r.user_id, u.email, n.subject, n.body FROM ' . $this->table('newsletter_recipients') . ' r'
            . ' JOIN ' . $this->table('newsletters') . ' n ON n.id = r.newsletter_id'
            . ' JOIN ' . $this->table('users') . ' u ON u.id = r.user_id'
            . ' WHERE r.sent_at IS NULL ORDER BY r.newsletter_id, r.user_id LIMIT ' . max(1, $limit),
        );

        return array_map(fn (array $row): array => [
            'newsletter_id' => self::intOf($row['newsletter_id']),
            'user_id' => self::intOf($row['user_id']),
            'email' => self::textOf($row['email']),
            'subject' => self::textOf($row['subject']),
            'body' => self::textOf($row['body']),
        ], $rows);
    }

    public function markSent(int $newsletterId, int $userId, int $now): void
    {
        $this->database->run(
            'UPDATE ' . $this->table('newsletter_recipients') . ' SET sent_at = ? WHERE newsletter_id = ? AND user_id = ?',
            [self::dateTime($now), $newsletterId, $userId],
        );
    }

    /**
     * The newsletters, newest first, with how many are sent and waiting.
     *
     * @return list<array{id: int, subject: string, created_at: string, sent: int, waiting: int}>
     */
    public function listAll(int $limit = 50): array
    {
        $rows = $this->database->fetchAll(
            'SELECT n.id, n.subject, n.created_at, SUM(r.sent_at IS NOT NULL) AS sent, SUM(r.sent_at IS NULL) AS waiting'
            . ' FROM ' . $this->table('newsletters') . ' n LEFT JOIN ' . $this->table('newsletter_recipients') . ' r ON r.newsletter_id = n.id'
            . ' GROUP BY n.id, n.subject, n.created_at ORDER BY n.id DESC LIMIT ' . max(1, $limit),
        );

        return array_map(fn (array $row): array => [
            'id' => self::intOf($row['id']),
            'subject' => self::textOf($row['subject']),
            'created_at' => self::textOf($row['created_at']),
            'sent' => self::intOf($row['sent']),
            'waiting' => self::intOf($row['waiting']),
        ], $rows);
    }
}
