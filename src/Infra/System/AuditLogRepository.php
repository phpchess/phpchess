<?php

declare(strict_types=1);

namespace PhpChess\Infra\System;

use PhpChess\Infra\Repository;

/**
 * The audit log: who did what, for every admin action (ADM-03).
 *
 * Owns the table "audit_log". Rows are only added, never changed; the admin
 * reads them on the audit log page.
 */
final class AuditLogRepository extends Repository
{
    /**
     * Records one action.
     *
     * @param string $action   a fixed word such as "user.disable" or "settings.change"
     * @param string $details  what changed, in plain words
     */
    public function record(?int $userId, string $action, string $details, string $ipAddress): void
    {
        $this->database->run(
            'INSERT INTO ' . $this->table('audit_log')
            . ' (created_at, user_id, action, details, ip_address) VALUES (UTC_TIMESTAMP(3), ?, ?, ?, ?)',
            [$userId, mb_substr($action, 0, 100), $details, mb_substr($ipAddress, 0, 45)],
        );
    }

    /**
     * The newest entries, with the user name of who did them.
     *
     * @return list<array{created_at: string, username: string, action: string, details: string, ip_address: string}>
     */
    public function newest(int $limit = 200): array
    {
        $rows = $this->database->fetchAll(
            'SELECT a.created_at, COALESCE(u.username, \'\') AS username, a.action, a.details, a.ip_address'
            . ' FROM ' . $this->table('audit_log') . ' a'
            . ' LEFT JOIN ' . $this->table('users') . ' u ON u.id = a.user_id'
            . ' ORDER BY a.id DESC LIMIT ?',
            [max(1, min($limit, 1000))],
        );

        $entries = [];
        foreach ($rows as $row) {
            $entries[] = [
                'created_at' => self::text($row['created_at']),
                'username' => self::text($row['username']),
                'action' => self::text($row['action']),
                'details' => self::text($row['details']),
                'ip_address' => self::text($row['ip_address']),
            ];
        }

        return $entries;
    }

    private static function text(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }
}
