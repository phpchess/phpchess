<?php

declare(strict_types=1);

namespace PhpChess\Infra\Jobs;

use PhpChess\Domain\Tournament\TournamentReminders;
use PhpChess\Domain\Tournament\TournamentRounds;
use PhpChess\Infra\Tournament\TournamentRepository;

/**
 * The round job: keeps running tournaments moving (T-08, A104).
 *
 * Every minute it starts tournaments whose planned start has come (A114)
 * and calls TournamentRounds::tick() for each running, not
 * paused tournament: rounds start, boards past their deadline are decided
 * by the visible forfeit rule (A102), finished rounds close and the next
 * round is paired. The tournament pages call tick() too, so a site without
 * cron still moves along when people look. It also sends the tournament
 * notices (TournamentReminders, A117).
 */
final class TournamentRoundJob implements Job
{
    /** Tournaments handled in one run, at most; the rest continue next minute. */
    private const int TOURNAMENTS_PER_RUN = 20;

    public function __construct(
        private readonly TournamentRounds $rounds,
        private readonly TournamentRepository $tournaments,
        private readonly ?int $fixedNow = null,
        private readonly ?TournamentReminders $reminders = null,
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
        $lines = [];
        try {
            // Tournaments whose planned start has come (A114) start first.
            foreach (array_slice($this->tournaments->scheduledToStartIds($now), 0, self::TOURNAMENTS_PER_RUN) as $tournamentId) {
                $lines[] = "#{$tournamentId}: " . $this->rounds->startScheduled($tournamentId, $now);
            }
            $tournamentIds = $this->tournaments->runningTournamentIds();
        } catch (\PDOException) {
            return 'waiting for the tournament updates (migrations 12 and 13)';
        }
        foreach (array_slice($tournamentIds, 0, self::TOURNAMENTS_PER_RUN) as $tournamentId) {
            $result = $this->rounds->tick($tournamentId, $now);
            if ($result !== '') {
                $lines[] = "#{$tournamentId}: {$result}";
            }
        }

        // --- Tournament notices (A117): starting in an hour, round paired, deadline in 24 hours ---
        if ($this->reminders !== null) {
            $told = 0;
            foreach ([...$this->tournaments->scheduledToStartIds($now + 3600), ...$tournamentIds] as $tournamentId) {
                $tournament = $this->tournaments->find($tournamentId);
                $told += $tournament === null ? 0 : $this->reminders->sendDue($tournament, $now);
            }
            if ($told > 0) {
                $lines[] = "{$told} tournament notices";
            }
        }

        return $lines === [] ? count($tournamentIds) . ' tournaments, nothing due' : implode('; ', $lines);
    }
}
