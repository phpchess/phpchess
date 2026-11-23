<?php

declare(strict_types=1);

namespace PhpChess\Infra\Jobs;

use PhpChess\Domain\Play\Games;
use PhpChess\Infra\Play\ChallengeRepository;
use PhpChess\Infra\Play\GameRepository;

/**
 * The clock job: ends games whose move deadline has passed (correspondence)
 * or whose flag has fallen without a poll noticing it (timed, a backup),
 * and closes challenges past their expiry (G-07, G-08, A33, A35).
 *
 * Runs every minute. Each overdue game goes through Games::endIfOverdue(),
 * which locks the game, takes vacation days into account and ends it with
 * Games::end()'s rules (K3), so a move that arrives at the same moment is
 * either before or after the timeout, never both.
 */
final class GameClockJob implements Job
{
    private const int GAMES_PER_RUN = 50;

    public function __construct(
        private readonly Games $games,
        private readonly GameRepository $gameRecords,
        private readonly ChallengeRepository $challenges,
        private readonly ?int $fixedNow = null,
    ) {
    }

    public function name(): string
    {
        return 'game_clocks';
    }

    public function intervalSeconds(): int
    {
        return 60;
    }

    public function run(): string
    {
        $now = $this->fixedNow ?? time();

        $endedCount = 0;
        foreach ($this->gameRecords->overdueGameIds($now, self::GAMES_PER_RUN) as $gameId) {
            $nowMs = $this->fixedNow === null ? (int) floor(microtime(true) * 1000) : $now * 1000;
            if ($this->games->endIfOverdue($gameId, $now, $nowMs)) {
                $endedCount++;
            }
        }
        $expiredCount = $this->challenges->expireOld($now);

        return "{$endedCount} games ended on time, {$expiredCount} challenges expired";
    }
}
