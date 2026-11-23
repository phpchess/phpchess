<?php

declare(strict_types=1);

namespace PhpChess\Domain\Notify;

use PhpChess\Domain\Play\Game;
use PhpChess\Infra\Notify\NoticeRepository;

/**
 * Who is told what, and how (G-10, N-01, ID-12): presence-aware delivery.
 *
 * Called by Games and Challenges when something happens that a player must
 * know about. The rule, in order:
 * 1. The player has the game open right now (the board page asked for news
 *    in the last LIVE_SECONDS): the move appears live, no notice.
 * 2. Otherwise: an in-app notice with the badge count (N-01). An unread
 *    notice of the same kind for the same game is not repeated.
 * 3. The move-email job (MoveEmailJob) later emails waiting notices to
 *    players who are offline, as each player chose (A34, N-02).
 * So a player is never told twice about the same thing.
 */
final class Notices
{
    /** A player counts as watching a game when its page asked for news this recently (seconds). */
    public const int LIVE_SECONDS = 30;

    public const string YOUR_MOVE = 'your_move';
    public const string GAME_ENDED = 'game_ended';
    public const string DRAW_OFFERED = 'draw_offered';
    public const string REMATCH_OFFERED = 'rematch_offered';
    public const string CHALLENGE_RECEIVED = 'challenge_received';
    public const string CHALLENGE_ACCEPTED = 'challenge_accepted';
    public const string ABORT_REQUESTED = 'abort_requested';
    public const string MESSAGE_RECEIVED = 'message_received';

    public function __construct(private readonly NoticeRepository $notices)
    {
    }

    // --- About games ---

    /** Tells a player about something in a game, unless they are watching it live. */
    public function tellAboutGame(Game $game, int $userId, string $kind, ?int $fromUserId, int $now): void
    {
        if (self::isWatchingLive($game, $userId, $now)) {
            return;
        }
        $this->notices->add($userId, $kind, $game->id, null, $fromUserId, $now);
    }

    /** True when the player's board page for this game asked for news in the last LIVE_SECONDS. */
    public static function isWatchingLive(Game $game, int $userId, int $now): bool
    {
        $seenAt = $game->seenAtBy($userId);

        return $seenAt !== null && $now - $seenAt <= self::LIVE_SECONDS;
    }

    // --- About challenges ---

    public function tellAboutChallenge(int $userId, string $kind, int $challengeId, ?int $gameId, int $fromUserId, int $now): void
    {
        $this->notices->add($userId, $kind, $gameId, $challengeId, $fromUserId, $now);
    }

    // --- To admins ---

    /**
     * Tells every admin that a player asks for an abort (A33, "ask an admin").
     *
     * @param list<int> $adminIds
     */
    public function tellAdmins(array $adminIds, Game $game, int $fromUserId, int $now): void
    {
        foreach ($adminIds as $adminId) {
            $this->notices->add($adminId, self::ABORT_REQUESTED, $game->id, null, $fromUserId, $now);
        }
    }
}
