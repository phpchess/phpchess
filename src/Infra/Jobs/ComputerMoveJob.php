<?php

declare(strict_types=1);

namespace PhpChess\Infra\Jobs;

use PhpChess\Domain\Computer\ComputerGames;

/**
 * The computer's correspondence moves (F2 review, A86): plays the held
 * replies whose time has come, so the computer keeps the pace of the game
 * even when nobody is online.
 *
 * Runs every minute, from a real cron job or from the pseudo-cron after
 * page visits (ARCHITECTURE.md §8). Each move goes through Games::move()
 * like any other (K1), which also tells the player (N-01).
 */
final class ComputerMoveJob implements Job
{
    private const int MOVES_PER_RUN = 50;

    public function __construct(
        private readonly ComputerGames $computerGames,
        private readonly ?int $fixedNow = null,
    ) {
    }

    public function name(): string
    {
        return 'computer_moves';
    }

    public function intervalSeconds(): int
    {
        return 60;
    }

    public function run(): string
    {
        $now = $this->fixedNow ?? time();
        $played = $this->computerGames->playDueReplies($now, self::MOVES_PER_RUN);

        return "{$played} computer moves played";
    }
}
