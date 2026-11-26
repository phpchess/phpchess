<?php

declare(strict_types=1);

namespace PhpChess\Infra\Jobs;

use PhpChess\Domain\Lichess\StudyImport;
use PhpChess\Infra\Lichess\StudyRepository;
use PhpChess\Infra\System\SettingsRepository;

/**
 * Removes players' old own study games (B37, A92), once a day.
 *
 * A game a player imported or pasted is removed when it is older than the
 * admin's number of days (30 unless changed; 0 means never), unless the
 * player pressed "Keep". The game list shows each game's removal date,
 * so nothing disappears by surprise. Theme games are never removed here.
 */
final class StudyGamesCleanupJob implements Job
{
    private const int ROWS_PER_RUN = 1000;

    public function __construct(
        private readonly StudyRepository $studies,
        private readonly SettingsRepository $settings,
        /** A fixed time for tests; null means the real clock, like the other jobs. */
        private readonly ?int $fixedNow = null,
    ) {
    }

    public function name(): string
    {
        return 'study-games-cleanup';
    }

    public function intervalSeconds(): int
    {
        return 86400;
    }

    public function run(): string
    {
        $days = StudyImport::ownGameDaysIn($this->settings);
        if ($days === 0) {
            return 'own study games are kept for ever';
        }
        $removed = $this->studies->removeOldOwnGames(($this->fixedNow ?? time()) - $days * 86400, self::ROWS_PER_RUN);

        return "{$removed} old own study games removed";
    }
}
