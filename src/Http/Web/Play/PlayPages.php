<?php

declare(strict_types=1);

namespace PhpChess\Http\Web\Play;

use LogicException;
use PhpChess\Domain\Identity\User;
use PhpChess\Domain\Play\Game;
use PhpChess\Domain\Play\PlayRefused;
use PhpChess\Domain\System\FeatureSwitches;
use PhpChess\Http\Request;
use PhpChess\Http\Response;
use PhpChess\Kernel\Services;

/**
 * Helpers shared by the play pages (C2-1): the logged-in player, texts,
 * redirects with a notice, and who may look at a game.
 *
 * Used by the GameController, ChallengeController, RatingController,
 * NoticeController and VacationController.
 */
final class PlayPages
{
    public function __construct(private readonly Services $services)
    {
    }

    /** The logged-in user; play routes are for members only, so there always is one. */
    public function user(): User
    {
        $user = $this->services->authentication()->currentUser();
        if ($user === null) {
            throw new LogicException('A members-only route ran without a logged-in user.');
        }

        return $user;
    }

    /**
     * @param array<string, string|int> $values
     */
    public function text(string $key, array $values = []): string
    {
        return $this->services->translator()->text($key, $values);
    }

    /** Back to a page with a success notice. */
    public function done(Request $request, string $path, string $messageKey): Response
    {
        return $this->services->pages()->redirectWithMessage($request, $path, 'success', $this->text($messageKey));
    }

    /** Back to a page with the refusal's text as an error notice. */
    public function refused(Request $request, string $path, PlayRefused $refusal): Response
    {
        return $this->services->pages()->redirectWithMessage($request, $path, 'error', $this->text($refusal->languageKey, $refusal->values));
    }

    /**
     * Who may look at a game (G-13, K14): its players and admins always;
     * other members only while the "player list visibility" switch is on.
     */
    public function mayWatch(Game $game, User $user): bool
    {
        if ($game->isPlayer($user->id) || $user->isAdmin()) {
            return true;
        }
        // A teacher may always see their students' games (SCH-06).
        $rules = $this->services->play()->classRules();
        if ($user->role === User::ROLE_TEACHER && ($rules->mayLookAfter($user, $game->whiteId) || $rules->mayLookAfter($user, $game->blackId))) {
            return true;
        }

        return $this->services->switches()->isOn(FeatureSwitches::PLAYER_LIST_VISIBILITY);
    }

    /** The page for a missing or hidden game: the same 404 for both, so a hidden game is not revealed. */
    public function notFound(Request $request): Response
    {
        return $this->services->pages()->render($request, 'play/message', $this->text('play.game_not_found'), [
            'message' => $this->text('play.game_not_found'),
        ], 404);
    }

    /**
     * A number from the address, such as the 12 of /games/12.
     *
     * @param array<string, string> $parts
     */
    public static function idFrom(array $parts, string $name = 'id'): int
    {
        $value = $parts[$name] ?? '';

        return preg_match('/^[1-9][0-9]{0,9}$/', $value) === 1 ? (int) $value : 0;
    }
}
