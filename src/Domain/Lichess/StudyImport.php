<?php

declare(strict_types=1);

namespace PhpChess\Domain\Lichess;

use PhpChess\Domain\ActionRefused;
use PhpChess\Infra\Lichess\LichessClient;
use PhpChess\Infra\Lichess\StudyRepository;
use PhpChess\Infra\System\AuditLogRepository;

/**
 * Brings games into the study collection (C5-1, L-03, A78, A81, A88).
 *
 * Three ways in, and every one goes through the content scanner first:
 *   - importOneGame():  a player or admin pastes a Lichess game link;
 *   - importUserGames(): an admin fetches a Lichess player's newest games (up to 50);
 *   - importUpload():   an admin uploads a PGN file (up to 1 MB, 50 games).
 *
 * A player's import has no theme and only that player sees it (A88); an
 * admin's import goes into a theme everyone sees. Every rejected game is
 * written to the audit log, so the admin can see what was kept out.
 */
final class StudyImport
{
    /** A player keeps at most this many own imports (A78: one at a time, A88). */
    public const int MAX_OWN_GAMES = 50;

    public function __construct(
        private readonly LichessClient $lichess,
        private readonly ContentScanner $scanner,
        private readonly StudyRepository $studies,
        private readonly AuditLogRepository $auditLog,
        private readonly string $ipAddress,
    ) {
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
        if ($themeId === null && $this->studies->countOwnGames($userId) >= self::MAX_OWN_GAMES) {
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
