<?php

declare(strict_types=1);

namespace PhpChess\Http\Web\Community;

use PhpChess\Domain\ActionRefused;
use PhpChess\Domain\Community\Avatars;
use PhpChess\Domain\Play\Game;
use PhpChess\Domain\System\FeatureSwitches;
use PhpChess\Http\Request;
use PhpChess\Http\Response;
use PhpChess\Http\Web\MemberPages;
use PhpChess\Kernel\Services;

/**
 * Players: who's online, buddies, profiles and avatars (C-01 to C-03, C-10).
 *
 * Addresses (Routes.php):
 *   GET  /players                  buddies and who's online (online part: switch "player list visibility")
 *   GET  /players/{name}           a profile; visitors only with the switch "public profiles"
 *   POST /players/{name}/buddy     add to my buddies
 *   POST /players/{name}/unbuddy   remove from my buddies
 *   GET  /account/profile          edit my profile and avatar
 *   POST /account/profile          save the text about me (word filter, C-05)
 *   POST /account/avatar           choose a bundled avatar, or none
 *   POST /account/avatar/upload    upload a picture (switch "file uploads")
 *   GET  /avatars/{id}             a member's uploaded picture
 * The school's class rule (SCH-05) hides profiles and buddies outside the class.
 */
final class PlayersController
{
    /** Seen in the last 5 minutes counts as online. */
    public const int ONLINE_SECONDS = 300;

    public const int ABOUT_MAX_LENGTH = 1000;

    public function __construct(
        private readonly Services $services,
        private readonly MemberPages $pages,
    ) {
    }

    // --- Lists ---

    public function list(Request $request): Response
    {
        $user = $this->pages->user();
        $rules = $this->services->play()->classRules();
        $online = [];
        if ($this->services->switches()->isOn(FeatureSwitches::PLAYER_LIST_VISIBILITY)) {
            foreach ($this->services->users()->seenSince($this->services->now - self::ONLINE_SECONDS) as $player) {
                if ($rules->mayContact($user->id, $player['id'])) {
                    $online[] = $player;
                }
            }
        }

        return $this->pages->render($request, 'community/players', 'players.title', [
            'online' => $online,
            'buddies' => $this->services->buddies()->listFor($user->id),
            'showOnline' => $this->services->switches()->isOn(FeatureSwitches::PLAYER_LIST_VISIBILITY),
            'onlineSince' => $this->services->now - self::ONLINE_SECONDS,
        ]);
    }

    // --- A profile (C-01) ---

    /**
     * @param array<string, string> $parts
     */
    public function profile(Request $request, array $parts): Response
    {
        $viewer = $this->services->authentication()->currentUser();
        if ($viewer === null && !$this->services->switches()->isOn(FeatureSwitches::PUBLIC_PROFILES)) {
            return Response::redirect($request->basePath . '/login?next=' . rawurlencode($request->path));
        }
        $player = $this->services->users()->findByUsername(MemberPages::nameFrom($parts));
        if ($player === null || !$player->isActive()) {
            return $this->pages->notFound($request);
        }
        if ($viewer !== null && !$this->services->play()->classRules()->mayContact($viewer->id, $player->id)) {
            return $this->pages->notFound($request);
        }

        $finished = $this->services->play()->gameRepository()->listForPlayer($player->id, Game::STATE_FINISHED, 10);

        return $this->services->pages()->render($request, 'community/profile', $player->username, [
            'player' => $player,
            'profile' => $this->services->profiles()->find($player->id),
            'rating' => $this->services->play()->ratingRepository()->find($player->id, Game::SPEED_CORRESPONDENCE),
            'finishedGames' => $finished,
            'usernames' => $this->usernamesOf($finished),
            'isBuddy' => $viewer !== null && $this->services->buddies()->isBuddy($viewer->id, $player->id),
            'isSelf' => $viewer !== null && $viewer->id === $player->id,
            'messagesAreOn' => $this->services->switches()->isOn(FeatureSwitches::DIRECT_MESSAGES),
            'lastSeenAt' => $this->services->users()->lastSeenAt($player->id),
        ]);
    }

    // --- Buddies (C-03) ---

    /**
     * @param array<string, string> $parts
     */
    public function addBuddy(Request $request, array $parts): Response
    {
        $user = $this->pages->user();
        $buddy = $this->services->users()->findByUsername(MemberPages::nameFrom($parts));
        if ($buddy === null || $buddy->id === $user->id || !$this->services->play()->classRules()->mayContact($user->id, $buddy->id)) {
            return $this->pages->notFound($request);
        }
        $this->services->buddies()->add($user->id, $buddy->id);

        return $this->pages->done($request, '/players/' . rawurlencode($buddy->username), 'players.buddy_added');
    }

    /**
     * @param array<string, string> $parts
     */
    public function removeBuddy(Request $request, array $parts): Response
    {
        $user = $this->pages->user();
        $buddy = $this->services->users()->findByUsername(MemberPages::nameFrom($parts));
        if ($buddy !== null) {
            $this->services->buddies()->remove($user->id, $buddy->id);
        }

        return $this->pages->done($request, '/players', 'players.buddy_removed');
    }

    // --- My profile and avatar (C-01, C-02) ---

    public function editProfile(Request $request): Response
    {
        $user = $this->pages->user();

        return $this->pages->render($request, 'community/edit_profile', 'profile.edit_title', [
            'profile' => $this->services->profiles()->find($user->id),
            'bundledAvatars' => Avatars::BUNDLED,
            'uploadsAreOn' => $this->services->switches()->isOn(FeatureSwitches::FILE_UPLOADS),
            'maxMegabytes' => (int) (Avatars::MAX_BYTES / 1_000_000),
        ]);
    }

    public function saveProfile(Request $request): Response
    {
        $user = $this->pages->user();
        $about = trim($request->formValue('about'));
        if (mb_strlen($about) > self::ABOUT_MAX_LENGTH) {
            return $this->pages->refused($request, '/account/profile', new ActionRefused('profile.about_too_long', ['max' => self::ABOUT_MAX_LENGTH]));
        }
        $this->services->profiles()->saveAbout($user->id, $this->services->wordFilter()->clean($about));

        return $this->pages->done($request, '/account/profile', 'profile.saved');
    }

    public function chooseAvatar(Request $request): Response
    {
        try {
            $this->services->avatars()->choose($this->pages->user()->id, $request->formValue('avatar'));
        } catch (ActionRefused $refusal) {
            return $this->pages->refused($request, '/account/profile', $refusal);
        }

        return $this->pages->done($request, '/account/profile', 'profile.avatar_saved');
    }

    public function uploadAvatar(Request $request): Response
    {
        $file = $request->file('picture');
        try {
            if ($file === null) {
                throw new ActionRefused('avatar.not_received');
            }
            $this->services->avatars()->upload($this->pages->user()->id, $file);
        } catch (ActionRefused $refusal) {
            return $this->pages->refused($request, '/account/profile', $refusal);
        }

        return $this->pages->done($request, '/account/profile', 'profile.avatar_saved');
    }

    /**
     * Sends a member's uploaded picture. It is a PNG we made ourselves (Avatars), so it is safe to show.
     *
     * @param array<string, string> $parts
     */
    public function avatarImage(Request $request, array $parts): Response
    {
        $isVisitor = $this->services->authentication()->currentUser() === null;
        if ($isVisitor && !$this->services->switches()->isOn(FeatureSwitches::PUBLIC_PROFILES)) {
            return Response::text('Not found', 404);
        }
        $path =$this->services->avatars()->uploadPath(MemberPages::idFrom($parts));
        $bytes = is_file($path) ? file_get_contents($path) : false;
        if ($bytes === false) {
            return Response::text('Not found', 404);
        }

        return new Response(200, $bytes, ['Content-Type' => 'image/png', 'Cache-Control' => 'private, max-age=3600']);
    }

    /**
     * @param list<Game> $games
     * @return array<int, string>
     */
    private function usernamesOf(array $games): array
    {
        $ids = [];
        foreach ($games as $game) {
            $ids[] = $game->whiteId;
            $ids[] = $game->blackId;
        }

        return $this->services->users()->usernamesOf($ids);
    }
}
