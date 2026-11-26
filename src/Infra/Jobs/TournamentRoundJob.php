<?php

declare(strict_types=1);

namespace PhpChess\Infra\Jobs;

use PhpChess\Domain\Tournament\TournamentRounds;
use PhpChess\Infra\Tournament\TournamentRepository;

/**
 * The round job: keeps running tournaments moving (T-08, A104).
 *
 * Every minute it calls TournamentRounds::tick() for each running, not
 * paused tournament: rounds start, boards past their deadline are decided
 * by the visible forfeit rule (A102), finished rounds close and the next
 * round is paired. The tournament pages call tick() too, so a site without
 * cron still moves along when people look.
 */
final class TournamentRoundJob implements Job
{
    /** Tournaments handled in one run, at most; the rest continue next minute. */
    private const int TOURNAMENTS_PER_RUN = 20;

    public function __construct(
        private readonly TournamentRounds $rounds,
        private readonly TournamentRepository $tournaments,
        private readonly ?int $fixedNow = null,
    ) {
    }

    public function name(): string
    {
        return 'tournament_rounds';
    }

    public function intervalSeconds(): int
    {
        return 60;
    }

    public function run(): string
    {
        $now = $this->fixedNow ?? time();
        try {
            $tournamentIds = $this->tournaments->runningTournamentIds();
        } catch (\PDOException) {
            return 'waiting for the tournament update (migration 12)';
        }
        $lines = [];
        foreach (array_slice($tournamentIds, 0, self::TOURNAMENTS_PER_RUN) as $tournamentId) {
            $result = $this->rounds->tick($tournamentId, $now);
            if ($result !== '') {
                $lines[] = "#{$tournamentId}: {$result}";
            }
        }

        return $lines === [] ? count($tournamentIds) . ' tournaments, nothing due' : implode('; ', $lines);
    }
}
