<?php

declare(strict_types=1);

namespace PhpChess\Http\Web;

use LogicException;
use PhpChess\Domain\Play\Challenge;
use PhpChess\Http\Request;
use PhpChess\Http\Response;
use PhpChess\Kernel\Services;

/**
 * The site-wide news poll (B21, A63): one small question from every page.
 *
 * Address (Routes.php), members only:
 *   GET /news   answers {challenge, new_game_id, unread_messages, next_poll_ms}
 *
 * public/assets/js/app/site-news.js asks every few seconds (the admin's
 * setting "site_poll.seconds", default 15; at least 60 in a hidden tab):
 *   challenge        the newest timed challenge sent directly to me that still
 *                    waits, or null; the page shows it as a pop-up with
 *                    Accept / Decline;
 *   new_game_id      a timed game I have not opened yet (someone accepted my
 *                    challenge): the page goes there;
 *   unread_messages  for the badge on Messages.
 * Asking also counts as "online" for the member's open timed lobby
 * challenges, as the Play page did before (C3-1).
 * A direct challenge also sends a push through the notices (N-03), so an
 * app in the background learns about it too.
 */
final class SiteNewsController
{
    public const int DEFAULT_POLL_SECONDS = 15;

    /** The admin's choice is kept between these (B22 will suggest safe values). */
    public const int MIN_POLL_SECONDS = 5;

    public const int MAX_POLL_SECONDS = 120;

    public function __construct(private readonly Services $services)
    {
    }

    public function news(Request $request): Response
    {
        $user = $this->services->authentication()->currentUser();
        if ($user === null) {
            throw new LogicException('A members-only route ran without a logged-in user.');
        }
        $now = $this->services->now;
        $play = $this->services->play();
        $play->challengeRepository()->markPosterSeen($user->id, $now);

        return Response::json([
            'challenge' => $this->newestTimedChallengeFor($user->id, $now),
            'new_game_id' => $play->gameRepository()->newTimedGameNotSeenBy($user->id),
            'unread_messages' => $this->services->unreadMessageCount(),
            'next_poll_ms' => self::pollSeconds($this->services) * 1000,
        ])->withHeader('Cache-Control', 'no-store');
    }

    /** The admin's poll interval in seconds, kept within the limits. */
    public static function pollSeconds(Services $services): int
    {
        $seconds = $services->settings()->getInt('site_poll.seconds', self::DEFAULT_POLL_SECONDS);

        return max(self::MIN_POLL_SECONDS, min(self::MAX_POLL_SECONDS, $seconds));
    }

    /**
     * The newest waiting timed challenge sent directly to this member, as
     * the pop-up needs it, or null.
     *
     * @return array{id: int, from: string, time_control: string, colour: string, rated: bool}|null
     */
    private function newestTimedChallengeFor(int $userId, int $now): ?array
    {
        foreach ($this->services->play()->challengeRepository()->waitingFor($userId, $now) as $challenge) {
            if ($challenge->opponentId !== $userId || $challenge->timeControl === null || $challenge->kind === Challenge::KIND_OPEN) {
                continue;
            }
            $names = $this->services->users()->usernamesOf([$challenge->challengerId]);

            return [
                'id' => $challenge->id,
                'from' => $names[$challenge->challengerId] ?? '?',
                'time_control' => $challenge->timeControl,
                'colour' => $challenge->colour,
                'rated' => $challenge->isRated,
            ];
        }

        return null;
    }
}
