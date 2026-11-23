<?php

declare(strict_types=1);

namespace PhpChess\Infra\Identity;

use PhpChess\Domain\Identity\Preferences;
use PhpChess\Infra\Repository;

/**
 * The user_preferences table (ID-13).
 *
 * Owns the table "user_preferences". An account without a row has the
 * defaults (Preferences::defaults()).
 */
final class PreferenceRepository extends Repository
{
    public function findForUser(int $userId): Preferences
    {
        $row = $this->database->fetchRow(
            'SELECT language, colour_scheme, board_theme, piece_set, email_notices FROM '
            . $this->table('user_preferences') . ' WHERE user_id = ?',
            [$userId],
        );
        if ($row === null) {
            return Preferences::defaults();
        }

        return new Preferences(
            language: self::text($row['language']),
            colourScheme: self::text($row['colour_scheme']),
            boardTheme: self::text($row['board_theme']),
            pieceSet: self::text($row['piece_set']),
            emailNotices: (int) self::text($row['email_notices']) === 1,
        );
    }

    public function save(int $userId, Preferences $preferences): void
    {
        $this->database->run(
            'INSERT INTO ' . $this->table('user_preferences')
            . ' (user_id, language, colour_scheme, board_theme, piece_set, email_notices, updated_at)'
            . ' VALUES (?, ?, ?, ?, ?, ?, UTC_TIMESTAMP(3))'
            . ' ON DUPLICATE KEY UPDATE language = VALUES(language), colour_scheme = VALUES(colour_scheme),'
            . ' board_theme = VALUES(board_theme), piece_set = VALUES(piece_set),'
            . ' email_notices = VALUES(email_notices), updated_at = VALUES(updated_at)',
            [
                $userId,
                $preferences->language,
                $preferences->colourScheme,
                $preferences->boardTheme,
                $preferences->pieceSet,
                $preferences->emailNotices ? 1 : 0,
            ],
        );
    }

    private static function text(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }
}
