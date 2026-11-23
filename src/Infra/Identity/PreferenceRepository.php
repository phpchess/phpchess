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

    // --- Move emails (A34) and the "Play" defaults (G-20) ---

    /** The player's move-email choice; '' means "the site's default". */
    public function moveEmailFor(int $userId): string
    {
        $value = $this->database->fetchValue('SELECT move_email FROM ' . $this->table('user_preferences') . ' WHERE user_id = ?', [$userId]);

        return self::text($value);
    }

    public function saveMoveEmail(int $userId, string $mode): void
    {
        $this->ensureRow($userId);
        $this->database->run('UPDATE ' . $this->table('user_preferences') . ' SET move_email = ? WHERE user_id = ?', [$mode, $userId]);
    }

    /**
     * The notification choices (N-04): per kind push, email or none; the
     * quiet hours (whole hours, null when none); the time zone they are in.
     *
     * @return array{games: string, challenges: string, messages: string, quiet_from: int|null, quiet_until: int|null, time_zone: string}
     */
    public function notificationChoices(int $userId): array
    {
        $row = $this->database->fetchRow(
            'SELECT notify_games, notify_challenges, notify_messages, quiet_from, quiet_until, time_zone FROM '
            . $this->table('user_preferences') . ' WHERE user_id = ?',
            [$userId],
        );

        return [
            'games' => self::text($row['notify_games'] ?? 'email'),
            'challenges' => self::text($row['notify_challenges'] ?? 'email'),
            'messages' => self::text($row['notify_messages'] ?? 'email'),
            'quiet_from' => is_numeric($row['quiet_from'] ?? null) ? (int) $row['quiet_from'] : null,
            'quiet_until' => is_numeric($row['quiet_until'] ?? null) ? (int) $row['quiet_until'] : null,
            'time_zone' => self::text($row['time_zone'] ?? 'UTC'),
        ];
    }

    /**
     * @param array{games: string, challenges: string, messages: string, quiet_from: int|null, quiet_until: int|null, time_zone: string} $choices
     */
    public function saveNotificationChoices(int $userId, array $choices): void
    {
        $this->ensureRow($userId);
        $this->database->run(
            'UPDATE ' . $this->table('user_preferences')
            . ' SET notify_games = ?, notify_challenges = ?, notify_messages = ?, quiet_from = ?, quiet_until = ?, time_zone = ? WHERE user_id = ?',
            [$choices['games'], $choices['challenges'], $choices['messages'], $choices['quiet_from'], $choices['quiet_until'], $choices['time_zone'], $userId],
        );
    }

    /** Removes a member's preferences (account deletion, ID-11). */
    public function deleteForUser(int $userId): void
    {
        $this->database->run('DELETE FROM ' . $this->table('user_preferences') . ' WHERE user_id = ?', [$userId]);
    }

    /**
     * The remembered settings of the "Play" button.
     *
     * @return array{days_per_move: int, rated: bool}
     */
    public function playDefaults(int $userId): array
    {
        $row = $this->database->fetchRow(
            'SELECT play_days_per_move, play_rated FROM ' . $this->table('user_preferences') . ' WHERE user_id = ?',
            [$userId],
        );
        if ($row === null) {
            return ['days_per_move' => 3, 'rated' => true];
        }

        return ['days_per_move' => (int) self::text($row['play_days_per_move']), 'rated' => (int) self::text($row['play_rated']) === 1];
    }

    public function savePlayDefaults(int $userId, int $daysPerMove, bool $isRated): void
    {
        $this->ensureRow($userId);
        $this->database->run(
            'UPDATE ' . $this->table('user_preferences') . ' SET play_days_per_move = ?, play_rated = ? WHERE user_id = ?',
            [$daysPerMove, $isRated ? 1 : 0, $userId],
        );
    }

    /** Makes the user's row with the defaults, if there is none yet. */
    private function ensureRow(int $userId): void
    {
        $this->database->run(
            'INSERT IGNORE INTO ' . $this->table('user_preferences') . ' (user_id, updated_at) VALUES (?, UTC_TIMESTAMP(3))',
            [$userId],
        );
    }

    private static function text(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }
}
