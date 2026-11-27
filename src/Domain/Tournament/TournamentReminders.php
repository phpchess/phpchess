<?php

declare(strict_types=1);

namespace PhpChess\Domain\Tournament;

use PhpChess\Infra\Notify\NoticeRepository;
use PhpChess\Infra\Tournament\TournamentRepository;

/**
 * Tournament notices (A117): the players are told when
 * - the tournament starts in an hour (it has a planned start),
 * - a round is paired,
 * - the round's deadline is 24 hours away (correspondence).
 *
 * They are ordinary notices (N-01): the badge in the menu, and email or
 * push as each player chose; the link opens the tournament list. The
 * table tournament_reminders remembers what was sent, so each notice goes
 * out once, however often the round job runs.
 *
 * Called by the round job (TournamentRoundJob) every minute.
 */
final class TournamentReminders
{
    public const string STARTS_SOON = 'tournament_starts_soon';
    public const string ROUND_PAIRED = 'tournament_round_paired';
    public const string DEADLINE_SOON = 'tournament_deadline_soon';

    private const int HOUR = 3600;

    public function __construct(
        private readonly TournamentRepository $tournaments,
        private readonly NoticeRepository $notices,
    ) {
    }

    /** Sends what is due for one tournament; returns how many players were told. */
    public function sendDue(Tournament $tournament, int $now): int
    {
        // --- Starts in an hour ---
        if ($tournament->isOpenForRegistration() && $tournament->startsAt !== null
            && $tournament->startsAt - self::HOUR <= $now && $now < $tournament->startsAt) {
            return $this->tellPlayers($tournament, self::STARTS_SOON, 0, $now);
        }
        if (!$tournament->isRunning() || $tournament->currentRound === 0) {
            return 0;
        }

        // --- The current round: paired, and its deadline ---
        $told = $this->tellPlayers($tournament, self::ROUND_PAIRED, $tournament->currentRound, $now);
        foreach ($this->tournaments->rounds($tournament->id) as $round) {
            $isSoon = $round['number'] === $tournament->currentRound && $round['state'] === 'running'
                && $round['deadlineAt'] !== null && $round['deadlineAt'] - 24 * self::HOUR <= $now && $now < $round['deadlineAt'];
            if ($isSoon) {
                $told += $this->tellPlayers($tournament, self::DEADLINE_SOON, $round['number'], $now);
            }
        }

        return $told;
    }

    /** One notice to every active person in the tournament, once per kind and round. */
    private function tellPlayers(Tournament $tournament, string $kind, int $round, int $now): int
    {
        if (!$this->tournaments->markReminderSent($tournament->id, $kind, $round, $now)) {
            return 0; // sent before
        }
        $told = 0;
        foreach ($this->tournaments->players($tournament->id) as $player) {
            if ($player->isActive() && !$player->isComputer && $this->notices->add($player->userId, $kind, null, null, null, $now)) {
                $told++;
            }
        }

        return $told;
    }
}
