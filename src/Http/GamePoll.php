<?php

declare(strict_types=1);

namespace PhpChess\Http;

use PhpChess\Domain\Play\Game;
use PhpChess\Domain\Play\Games;
use PhpChess\Kernel\Services;

/**
 * One poll of a game by the board or an app (G-10, G-15, API-03, A46).
 *
 * Live play uses short polling: the page asks "anything new?" every second
 * or so. Each poll:
 *   1. notes that a player is watching (no notices for moves seen live),
 *      at most once a second;
 *   2. notes the moment the player to move first received the last move:
 *      their clock starts there (lag compensation, K12);
 *   3. ends a timed game whose running clock reached zero (inside the lock, K2/K3);
 *   4. answers. When the caller sends the version it already has and
 *      nothing changed, the answer is short: {"unchanged": true, ...}.
 *
 * Every answer says when to ask next ("next_poll_ms", admin settings):
 *   timed game, opponent to move   play.poll_fast_ms    default 1000
 *   timed game, your own move      play.poll_own_ms     default 3000
 *   correspondence, or a watcher   play.poll_slow_ms    default 15000
 * and how long the opponent's board has been silent ("opponent_idle_s"),
 * so the page can say "opponent disconnected" (after 10 s, A46).
 */
final class GamePoll
{
    public const int DEFAULT_FAST_MS = 1000;
    public const int DEFAULT_OWN_MOVE_MS = 3000;
    public const int DEFAULT_SLOW_MS = 15000;

    public function __construct(private readonly Services $services)
    {
    }

    /**
     * The answer for one viewer.
     *
     * @return array<string, mixed>
     */
    public function answer(Game $game, ?int $viewerId, int $since, string $knownVersion): array
    {
        $now = $this->services->now;
        $nowMs = $this->services->nowMs();
        $play = $this->services->play();
        $gameRepository = $play->gameRepository();

        // --- 1. and 2. A player's poll: presence and delivery ---
        if ($viewerId !== null && $game->isPlayer($viewerId)) {
            // Presence is kept to the second: one write a second at most.
            if ($game->seenAtBy($viewerId) !== $now) {
                $play->games()->markSeen($game, $viewerId, $now);
            }
            if ($game->isTimed() && $game->isRunning() && $game->playerToMoveId() === $viewerId && $game->turnDeliveredMs === null) {
                $gameRepository->markDelivered($game->id, $game->ply, $nowMs);
                $game = $gameRepository->find($game->id) ?? $game;
            }
        }

        // --- 3. The flag ---
        $left = Games::runningClockLeft($game, $nowMs);
        if ($left !== null && $left <= 0) {
            $play->games()->endIfFlagged($game->id, $now, $nowMs);
            $game = $gameRepository->find($game->id) ?? $game;
        }

        // --- 4. The answer ---
        $extra = [
            'next_poll_ms' => $this->nextPollMs($game, $viewerId),
            'opponent_idle_s' => $this->opponentIdleSeconds($game, $viewerId, $now),
        ];
        $version = GameStateJson::version($game);
        if ($knownVersion !== '' && $knownVersion === $version) {
            return ['unchanged' => true, 'version' => $version, 'clock' => GameStateJson::clock($game, $nowMs)] + $extra;
        }
        // Something changed (a move, an offer): the notices about this game are read now.
        if ($viewerId !== null && $game->isPlayer($viewerId)) {
            $play->noticeRepository()->markGameRead($viewerId, $game->id, $now);
        }
        $usernames = $this->services->users()->usernamesOf([$game->whiteId, $game->blackId]);

        return GameStateJson::of($game, $gameRepository->moves($game->id, $since), $usernames, $viewerId, $nowMs) + $extra;
    }

    /** How long to wait before the next poll (API-03: adaptive interval). */
    public function nextPollMs(Game $game, ?int $viewerId): int
    {
        $settings = $this->services->settings();
        $slow = max(2000, $settings->getInt('play.poll_slow_ms', self::DEFAULT_SLOW_MS));
        if (!$game->isRunning() || !$game->isTimed() || $viewerId === null || !$game->isPlayer($viewerId)) {
            return $slow;
        }
        if ($game->playerToMoveId() === $viewerId) {
            return max(500, $settings->getInt('play.poll_own_ms', self::DEFAULT_OWN_MOVE_MS));
        }

        return max(500, $settings->getInt('play.poll_fast_ms', self::DEFAULT_FAST_MS));
    }

    /** Seconds since the opponent's board last polled; null for a watcher or when never. */
    private function opponentIdleSeconds(Game $game, ?int $viewerId, int $now): ?int
    {
        if ($viewerId === null || !$game->isPlayer($viewerId)) {
            return null;
        }
        $seenAt = $game->seenAtBy($game->opponentOf($viewerId));

        return $seenAt === null ? null : max(0, $now - $seenAt);
    }
}
