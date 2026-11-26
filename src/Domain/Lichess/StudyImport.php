<?php

declare(strict_types=1);

namespace PhpChess\Domain\Lichess;

use PhpChess\Domain\ActionRefused;
use PhpChess\Infra\Lichess\LichessClient;
use PhpChess\Infra\Lichess\StudyRepository;
use PhpChess\Infra\System\AuditLogRepository;
use PhpChess\Infra\System\SettingsRepository;

/**
 * Brings games into the study collection (C5-1, L-03, A78, A81, A88).
 *
 * Four ways in, and every one goes through the content scanner first:
 *   - importOneGame():    a player or admin pastes a Lichess game link;
 *   - importUserGames():  an admin fetches a Lichess player's newest games (up to 50);
 *   - importUpload():     an admin uploads or pastes PGN text (up to 1 MB, 50 games);
 *   - importPastedGame(): a player pastes one game as PGN text (B37; only
 *     while the admin allows it, PASTE_SETTING);
 *   - importStudy():      an admin imports a public Lichess study by its link,
 *     one game per chapter (A95).
 *
 * The admin sets how many own games a player may keep and how many imports
 * a player may make per hour (B37); the constants are the defaults.
 *
 * A player's import has no theme and only that player sees it (A88); an
 * admin's import goes into a theme everyone sees. Every rejected game is
 * written to the audit log, so the admin can see what was kept out.
 */
final class StudyImport
{
    /** A player keeps at most this many own imports, unless the admin changes it (A88, B37). */
    public const int MAX_OWN_GAMES = 50;

    /** A player may import this many games per hour, unless the admin changes it (B37). */
    public const int IMPORTS_PER_HOUR = 10;

    /** A player's own game is removed this many days after its import, unless kept (B37, A92). */
    public const int OWN_GAME_DAYS = 30;

    /** The admin's settings for players' own games (B37). */
    public const string MAX_OWN_SETTING = 'study.max_own_games';
    public const string PER_HOUR_SETTING = 'study.imports_per_hour';
    public const string PASTE_SETTING = 'study.paste_for_players';
    public const string DAYS_SETTING = 'study.own_game_days';

    public function __construct(
        private readonly LichessClient $lichess,
        private readonly ContentScanner $scanner,
        private readonly StudyRepository $studies,
        private readonly AuditLogRepository $auditLog,
        private readonly string $ipAddress,
        private readonly int $maxOwnGames = self::MAX_OWN_GAMES,
    ) {
    }

    // --- The admin's settings, read in one place ---

    /** How many own games a player may keep (1 to 500). */
    public static function maxOwnGamesIn(SettingsRepository $settings): int
    {
        return max(1, min(500, $settings->getInt(self::MAX_OWN_SETTING, self::MAX_OWN_GAMES)));
    }

    /** How many imports a player may make per hour (1 to 100). */
    public static function importsPerHourIn(SettingsRepository $settings): int
    {
        return max(1, min(100, $settings->getInt(self::PER_HOUR_SETTING, self::IMPORTS_PER_HOUR)));
    }

    /** Days before a player's own game is removed (0: never, up to 3650). */
    public static function ownGameDaysIn(SettingsRepository $settings): int
    {
        return max(0, min(3650, $settings->getInt(self::DAYS_SETTING, self::OWN_GAME_DAYS)));
    }

    /** True when every player may paste a game as PGN; admins always may (B37). */
    public static function pasteIsOpenIn(SettingsRepository $settings): bool
    {
        return $settings->getBool(self::PASTE_SETTING, false);
    }

    /**
     * Imports one game from a Lichess link or game id.
     *
     * @param int|null $themeId  the admin's theme, or null for a player's own import
     * @return int  the stored game's id
     */
    public function importOneGame(string $linkOrId, ?int $themeId, int $userId): int
    {
        $gameId = self::gameIdFrom($linkOrId);
        if ($gameId === null) {
            throw new ActionRefused('lichess.bad_link');
        }
        if ($themeId === null && $this->studies->countOwnGames($userId) >= $this->maxOwnGames) {
            throw new ActionRefused('study.too_many_own');
        }

        $result = $this->scanner->scan($this->lichess->gamePgn($gameId));
        $this->logRejections($result, $userId, 'lichess game ' . $gameId);
        if (count($result->accepted) !== 1) {
            throw new ActionRefused('study.rejected');
        }

        return $this->studies->addGame($result->accepted[0], $themeId, $themeId === null ? $userId : null, 'lichess', $gameId, $userId);
    }

    /**
     * Imports a Lichess player's newest games into a theme (admins).
     *
     * @return array{added: int, rejected: int}
     */
    public function importUserGames(string $username, int $count, int $themeId, int $adminId): array
    {
        if (preg_match('/^[A-Za-z0-9_-]{2,30}$/', $username) !== 1) {
            throw new ActionRefused('lichess.bad_username');
        }
        $count = max(1, min(ContentScanner::MAX_GAMES, $count));
        $result = $this->scanner->scan($this->lichess->userGamesPgn($username, $count));

        return $this->store($result, $themeId, $adminId, 'lichess', 'lichess player ' . $username);
    }

    /**
     * Imports an uploaded PGN text into a theme (admins).
     *
     * @return array{added: int, rejected: int}
     */
    public function importUpload(string $pgnText, int $themeId, int $adminId): array
    {
        return $this->store($this->scanner->scan($pgnText), $themeId, $adminId, 'upload', 'upload');
    }

    /**
     * Imports a public Lichess study into a theme (admins, A95): every
     * chapter passes the scanner on its own; chapters it rejects are left
     * out and listed in the audit log.
     *
     * @return array{added: int, rejected: int}
     */
    public function importStudy(string $link, int $themeId, int $adminId): array
    {
        $studyId = self::studyIdFrom($link);
        if ($studyId === null) {
            throw new ActionRefused('lichess.bad_link');
        }
        $result = $this->scanner->scan($this->lichess->studyPgn($studyId));
        if ($this->studies->findTheme($themeId) === null) {
            throw new ActionRefused('study.no_theme');
        }
        $this->logRejections($result, $adminId, 'lichess study ' . $studyId);
        foreach ($result->accepted as $game) {
            $this->studies->addGame($game, $themeId, null, 'study', $studyId, $adminId);
        }

        return ['added' => count($result->accepted), 'rejected' => count($result->rejected)];
    }

    /**
     * The study id in a link like https://lichess.org/study/abcdEFGH or
     * https://lichess.org/study/abcdEFGH/chapter12; null for anything else.
     */
    public static function studyIdFrom(string $link): ?string
    {
        $text = trim($link);
        if (preg_match('~^https://lichess\.org/study/([A-Za-z0-9]{8})(?:/[A-Za-z0-9]{8})?(?:#\d+)?$~', $text, $match) === 1) {
            return $match[1];
        }

        return null;
    }

    /**
     * Imports one game a player pasted as PGN text into their own list
     * (B37). The text must hold exactly one game, and the game must pass
     * the scanner; otherwise nothing is kept.
     *
     * @return int  the stored game's id
     */
    public function importPastedGame(string $pgnText, int $userId): int
    {
        if ($this->studies->countOwnGames($userId) >= $this->maxOwnGames) {
            throw new ActionRefused('study.too_many_own');
        }
        if (count(PgnReader::splitGames($pgnText)) > 1) {
            throw new ActionRefused('study.paste_one_game');
        }

        $result = $this->scanner->scan($pgnText);
        $this->logRejections($result, $userId, 'pasted game');
        if (count($result->accepted) !== 1) {
            throw new ActionRefused('study.rejected');
        }

        return $this->studies->addGame($result->accepted[0], null, $userId, 'paste', '', $userId);
    }

    /**
     * The Lichess game id in a link like https://lichess.org/abcdEFGH,
     * https://lichess.org/abcdEFGH/black#12 or a bare 8-letter id; null when
     * it is none of those. Only lichess.org links are accepted.
     */
    public static function gameIdFrom(string $linkOrId): ?string
    {
        $text = trim($linkOrId);
        if (preg_match('~^[A-Za-z0-9]{8}$~', $text) === 1) {
            return $text;
        }
        if (preg_match('~^https://lichess\.org/(?:game/export/)?([A-Za-z0-9]{8})(?:[A-Za-z0-9]{4})?(?:/(?:white|black))?(?:#\d+)?$~', $text, $match) === 1) {
            return $match[1];
        }

        return null;
    }

    // --- Helpers ---

    /** @return array{added: int, rejected: int} */
    private function store(ScanResult $result, int $themeId, int $adminId, string $source, string $from): array
    {
        if ($this->studies->findTheme($themeId) === null) {
            throw new ActionRefused('study.no_theme');
        }
        $this->logRejections($result, $adminId, $from);
        foreach ($result->accepted as $game) {
            $this->studies->addGame($game, $themeId, null, $source, self::gameIdFrom($game->tag('Site')) ?? '', $adminId);
        }

        return ['added' => count($result->accepted), 'rejected' => count($result->rejected)];
    }

    private function logRejections(ScanResult $result, int $userId, string $from): void
    {
        foreach ($result->rejected as $reason) {
            $this->auditLog->record($userId, 'study.rejected', $from . ': ' . $reason, $this->ipAddress);
        }
    }
}
