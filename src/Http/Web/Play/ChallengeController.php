<?php

declare(strict_types=1);

namespace PhpChess\Http\Web\Play;

use PhpChess\Domain\Chess\Position;
use PhpChess\Domain\Computer\ComputerGames;
use PhpChess\Domain\Play\Challenge;
use PhpChess\Domain\Play\Game;
use PhpChess\Domain\Play\Games;
use PhpChess\Domain\Play\PlayRefused;
use PhpChess\Domain\Play\TimeControl;
use PhpChess\Domain\Play\TimedClock;
use PhpChess\Domain\Play\Vacations;
use PhpChess\Http\QrCode;
use PhpChess\Http\Request;
use PhpChess\Http\Response;
use PhpChess\Infra\Content\ContentRepository;
use PhpChess\Kernel\Services;

/**
 * The "Play" page (F2: tiles, the new game pop-up and live boxes), challenges and invite links
 * (G-05, G-06, G-20, G-21, A28, A35), with time controls for timed play
 * (G-14, G-16) and the rules page (A46).
 *
 * The form field "time_control" chooses the pace: empty for correspondence
 * (days per move), or "5+3" etc. for a timed game.
 *
 * Addresses (Routes.php), members only unless noted:
 *   GET  /play                          tiles for a new game, my games, challenges, admin news (F2)
 *   GET  /play/boxes                    only the boxes of the Play page, for its live refresh (A69)
 *   GET  /play/challenge                a challenge with more choices: one player, invite link, start position
 *   POST /play/start                    the new game pop-up: computer or person, pace, strength, colour (A70)
 *   POST /play/quick                    one-tap start with the remembered defaults (G-20)
 *   GET  /play/news                     JSON for the Play page's polling: keeps my open timed
 *                                       challenges listed, and names a new timed game to go to (G-16)
 *   GET  /rules                         the rules of this site, written from the admin's settings (A46)
 *   POST /challenges                    a new open, direct or invite challenge
 *   POST /challenges/{id}/accept | decline | cancel
 *   GET  /challenges/{id}/invite        the invite link and QR code, shown to its creator once
 *   GET  /invite/{token}                an invite, for anyone with the link (visitors are asked to log in)
 *   POST /invite/{token}                accept the invite
 */
final class ChallengeController
{
    /** Session key for the invite token, shown to its creator on the next page only. */
    private const string NEW_INVITE_KEY = 'new_invite';

    /** The days-per-move buttons of the new game pop-up (A70). */
    private const array DAY_CHOICES = [1, 3, 7, 14];

    /** The rating range buttons for a game against a person, 0 = anyone (A83). */
    public const array RATING_RANGES = [0, 100, 200, 400];

    public function __construct(
        private readonly Services $services,
        private readonly PlayPages $pages,
    ) {
    }

    // --- The Play page (F2: A69, A70) ---

    /**
     * The Play page: three big tiles (Computer, Live, Correspondence) that
     * open the new game pop-up, and the boxes: my games, received
     * challenges, open challenges, news from the admins.
     */
    public function lobby(Request $request): Response
    {
        $user = $this->pages->user();
        $play = $this->services->play();
        $boxData = $this->boxData($user->id);

        return $this->services->pages()->render($request, 'play/lobby', $this->pages->text('play.title'), $boxData + [
            'boxesVersion' => self::boxesVersion($boxData),
            'adminNews' => $this->services->content()->listKind(ContentRepository::NEWS, true, 3),
            'openPopUp' => $request->queryValue('new'),
            'playDefaults' => $this->services->preferenceRepository()->playDefaults($user->id),
            'timeControls' => $play->games()->allowedTimeControls(),
            'correspondenceDays' => $this->correspondenceDayChoices(),
            'computerBands' => ComputerGames::STRENGTH_BANDS,
            'ratingRanges' => self::RATING_RANGES,
        ]);
    }

    /** Only the boxes of the Play page, for lobby.js to swap in without reloading the page (A69). */
    public function boxes(Request $request): Response
    {
        $user = $this->pages->user();
        $boxData = $this->boxData($user->id);

        return $this->services->pages()->renderPart($request, 'play/boxes', $boxData + [
            'boxesVersion' => self::boxesVersion($boxData),
        ]);
    }

    /** The page for challenges with more choices: one player, an invite link, a start position. */
    public function newChallengePage(Request $request): Response
    {
        $user = $this->pages->user();
        $play = $this->services->play();
        $opponents = $this->services->users()->activeUsernames();
        unset($opponents[$user->id]);

        return $this->services->pages()->render($request, 'play/new_challenge', $this->pages->text('challenge.new'), [
            'opponents' => $opponents,
            'playDefaults' => $this->services->preferenceRepository()->playDefaults($user->id),
            'maximumDays' => $play->challenges()->maximumDays(),
            'isFenAllowed' => $play->challenges()->isFenAllowed(),
            'timeControls' => $play->games()->allowedTimeControls(),
        ]);
    }

    /**
     * What the boxes of the Play page show for one player.
     *
     * @return array{viewerId: int, myGames: list<Game>, receivedChallenges: list<Challenge>,
     *               myChallenges: list<Challenge>, openChallenges: list<Challenge>, usernames: array<int, string>}
     */
    private function boxData(int $viewerId): array
    {
        $now = $this->services->now;
        $play = $this->services->play();
        $challengeRepository = $play->challengeRepository();
        $myGames = $play->gameRepository()->listForPlayer($viewerId, Game::STATE_RUNNING);

        // Challenges sent to me, and my own waiting ones.
        $receivedChallenges = [];
        $myChallenges = [];
        foreach ($challengeRepository->waitingFor($viewerId, $now) as $challenge) {
            if ($challenge->challengerId === $viewerId) {
                $myChallenges[] = $challenge;
            } else {
                $receivedChallenges[] = $challenge;
            }
        }

        // Open challenges of the other players (mine are already in $myChallenges).
        $openChallenges = [];
        foreach ($challengeRepository->waitingOpen($now) as $challenge) {
            if ($challenge->challengerId !== $viewerId) {
                $openChallenges[] = $challenge;
            }
        }

        $userIds = [];
        foreach ($myGames as $game) {
            $userIds[] = $game->whiteId;
            $userIds[] = $game->blackId;
        }
        foreach ([...$receivedChallenges, ...$myChallenges, ...$openChallenges] as $challenge) {
            $userIds[] = $challenge->challengerId;
            if ($challenge->opponentId !== null) {
                $userIds[] = $challenge->opponentId;
            }
        }

        return [
            'viewerId' => $viewerId,
            'myGames' => $myGames,
            'receivedChallenges' => $receivedChallenges,
            'myChallenges' => $myChallenges,
            'openChallenges' => $openChallenges,
            'usernames' => $this->services->users()->usernamesOf($userIds),
        ];
    }

    /**
     * A short fingerprint of what the boxes show: every game with its number
     * of moves, every challenge. When it changes (a move, a new or withdrawn
     * challenge), lobby.js fetches the boxes again (A69).
     *
     * @param array{myGames: list<Game>, receivedChallenges: list<Challenge>, myChallenges: list<Challenge>, openChallenges: list<Challenge>} $boxData
     */
    private static function boxesVersion(array $boxData): string
    {
        $parts = [];
        foreach ($boxData['myGames'] as $game) {
            $parts[] = 'g' . $game->id . '.' . $game->ply;
        }
        foreach ([...$boxData['receivedChallenges'], ...$boxData['myChallenges'], ...$boxData['openChallenges']] as $challenge) {
            $parts[] = 'c' . $challenge->id;
        }

        return substr(md5(implode(',', $parts)), 0, 12);
    }

    /**
     * The days-per-move buttons of the pop-up: 1, 3, 7 and 14 days, as far
     * as the admin allows; the admin's highest number when it is below all of them.
     *
     * @return list<int>
     */
    private function correspondenceDayChoices(): array
    {
        $maximumDays = $this->services->play()->challenges()->maximumDays();
        $choices = array_values(array_filter(self::DAY_CHOICES, fn (int $days): bool => $days <= $maximumDays));

        return $choices === [] ? [$maximumDays] : $choices;
    }

    /**
     * The Play page's polling (G-16, A69): the poster of an open timed
     * challenge stays listed while this page asks; when someone accepts, the
     * answer names the new game and the page goes there. "boxes_version"
     * tells the page when its boxes need fetching again.
     */
    public function news(Request $request): Response
    {
        $user = $this->pages->user();
        $now = $this->services->now;
        $play = $this->services->play();
        $play->challengeRepository()->markPosterSeen($user->id, $now);

        return Response::json([
            'new_game_id' => $play->gameRepository()->newTimedGameNotSeenBy($user->id),
            'boxes_version' => self::boxesVersion($this->boxData($user->id)),
            'next_poll_ms' => 3000,
        ])->withHeader('Cache-Control', 'no-store');
    }

    // --- Starting a game from the new game pop-up (F2, A70, A83) ---

    /**
     * The pop-up's one form: against the computer (a strength band) or a
     * person (a pace, rated or not, a rating range), and a colour.
     * Every value comes from a button, none is typed.
     */
    public function startNewGame(Request $request): Response
    {
        $user = $this->pages->user();
        $now = $this->services->now;
        $play = $this->services->play();
        $colour = $request->formValue('colour', Challenge::COLOUR_RANDOM);

        try {
            // ===== Against the computer =====
            if ($request->formValue('opponent') === 'computer') {
                $computerColour = match ($colour) {
                    Challenge::COLOUR_WHITE => Position::WHITE,
                    Challenge::COLOUR_BLACK => Position::BLACK,
                    default => 'random',
                };
                $gameId = $play->computerGames()->startInBand($user->id, $request->formValue('band'), $computerColour, $now);

                return Response::redirect($request->basePath . '/games/' . $gameId);
            }

            // ===== Against a person: the pace is "live:5+3" or "days:3" =====
            [$paceKind, $paceValue] = array_pad(explode(':', $request->formValue('pace', 'days:3'), 2), 2, '');
            $timeControl = $paceKind === 'live' ? $paceValue : '';
            $daysPerMove = $paceKind === 'days' ? (int) $paceValue : 3;
            $isRated = $request->formValue('rated') === 'yes';
            $skipIds = $this->challengesOutsideRange($user->id, (int) $request->formValue('rating_range', '0'), $timeControl);

            $result = $play->challenges()->quickPlay($user->id, $daysPerMove, $isRated, $now, $timeControl, $this->services->nowMs(), $colour, $skipIds);
        } catch (PlayRefused $refusal) {
            return $this->pages->refused($request, '/play', $refusal);
        }
        if ($timeControl === '') {
            $this->services->preferenceRepository()->savePlayDefaults($user->id, $daysPerMove, $isRated);
        }
        if ($result['game_id'] !== null) {
            return Response::redirect($request->basePath . '/games/' . $result['game_id']);
        }

        return $this->pages->done($request, '/play', 'play.waiting_for_opponent');
    }

    /**
     * The waiting open challenges whose challenger's rating is more than
     * $range away from the player's own, at this pace (A83). A range of 0
     * means anyone.
     *
     * @return list<int>
     */
    private function challengesOutsideRange(int $userId, int $range, string $timeControl): array
    {
        if ($range <= 0) {
            return [];
        }
        $speed = Game::SPEED_CORRESPONDENCE;
        if ($timeControl !== '') {
            $speed = TimeControl::fromText($timeControl)?->speed() ?? $speed;
        }
        $ratings = $this->services->play()->ratings();
        $myRating = $ratings->shownRating($userId, $speed)['rating'];

        $skipIds = [];
        foreach ($this->services->play()->challengeRepository()->waitingOpen($this->services->now) as $challenge) {
            $theirRating = $ratings->shownRating($challenge->challengerId, $speed)['rating'];
            if (abs($theirRating - $myRating) > $range) {
                $skipIds[] = $challenge->id;
            }
        }

        return $skipIds;
    }

    /** The rules of this site for players, written from the admin's settings (A46). */
    public function rules(Request $request): Response
    {
        return $this->services->pages()->render($request, 'play/rules', $this->pages->text('rules.title'), $this->rulesData());
    }

    /**
     * Everything the rules page says, read from the settings. The admin's
     * play settings page shows the same page.
     *
     * @return array<string, mixed>
     */
    public function rulesData(): array
    {
        $play = $this->services->play();
        $games = $play->games();
        $settings = $this->services->settings();

        return [
            'timeControls' => $games->allowedTimeControls(),
            'maxLiveGames' => $games->maxLiveGames(),
            'maxLiveGamesPerPlayer' => $games->maxLiveGamesPerPlayer(),
            'maximumDays' => $games->maximumDays(),
            'abortRule' => $games->abortRule(),
            'vacationDays' => Vacations::DAYS_PER_YEAR,
            'openDays' => $settings->getInt('challenge.open_days', 7),
            'directDays' => $settings->getInt('challenge.direct_days', 14),
            'inviteDays' => $settings->getInt('challenge.invite_days', 7),
            'deliveryWaitMs' => TimedClock::MAX_DELIVERY_WAIT_MS,
            'lagAllowanceMs' => TimedClock::LAG_ALLOWANCE_MS,
            'onlineSeconds' => Games::ONLINE_SECONDS,
        ];
    }

    /** The one-tap Play button: pairs with whoever waits, or opens a challenge (G-20). */
    public function quickPlay(Request $request): Response
    {
        $user = $this->pages->user();
        $challenges = $this->services->play()->challenges();
        $daysPerMove = (int) $request->formValue('days_per_move', '3');
        $isRated = $request->formValue('rated') === 'yes';
        $timeControl = $request->formValue('time_control');
        try {
            $result = $challenges->quickPlay($user->id, $daysPerMove, $isRated, $this->services->now, $timeControl, $this->services->nowMs());
        } catch (PlayRefused $refusal) {
            return $this->pages->refused($request, '/play', $refusal);
        }
        $this->services->preferenceRepository()->savePlayDefaults($user->id, $daysPerMove, $isRated);
        if ($result['game_id'] !== null) {
            return Response::redirect($request->basePath . '/games/' . $result['game_id']);
        }

        return $this->pages->done($request, '/play', 'play.waiting_for_opponent');
    }

    // --- Creating challenges ---

    /** Creates an open, direct or invite challenge from the form. */
    public function create(Request $request): Response
    {
        $user = $this->pages->user();
        $challenges = $this->services->play()->challenges();
        $now = $this->services->now;
        $colour = $request->formValue('colour', Challenge::COLOUR_RANDOM);
        $daysPerMove = (int) $request->formValue('days_per_move', '3');
        $isRated = $request->formValue('rated') === 'yes';
        $startFen = $request->formValue('start_fen');
        $timeControl = $request->formValue('time_control');

        try {
            switch ($request->formValue('kind')) {
                case Challenge::KIND_DIRECT:
                    $opponent = $this->services->users()->findByUsernameOrEmail(trim($request->formValue('opponent')));
                    if ($opponent === null || !$opponent->isActive()) {
                        throw new PlayRefused('challenge.opponent_unknown');
                    }
                    $challenges->createDirect($user->id, $opponent->id, $colour, $daysPerMove, $isRated, $startFen, $now, $timeControl);

                    return $this->pages->done($request, '/play', 'challenge.sent');
                case Challenge::KIND_INVITE:
                    $invite = $challenges->createInvite($user->id, $colour, $daysPerMove, $isRated, $startFen, $now, $timeControl);
                    $this->services->session->set(self::NEW_INVITE_KEY, $invite['token']);

                    return Response::redirect($request->basePath . '/challenges/' . $invite['challenge_id'] . '/invite');
                default:
                    $challenges->createOpen($user->id, $colour, $daysPerMove, $isRated, $now, $timeControl);

                    return $this->pages->done($request, '/play', 'challenge.opened');
            }
        } catch (PlayRefused $refusal) {
            return $this->pages->refused($request, '/play', $refusal);
        }
    }

    /**
     * The invite link and its QR code, for the creator, right after creating
     * it. The token is only in the session now: the database keeps its hash.
     *
     * @param array<string, string> $parts
     */
    public function showNewInvite(Request $request, array $parts): Response
    {
        $token = $this->services->session->getString(self::NEW_INVITE_KEY);
        $challenge = $this->services->play()->challengeRepository()->find(PlayPages::idFrom($parts));
        if ($token === '' || $challenge === null || $challenge->challengerId !== $this->pages->user()->id) {
            return $this->pages->done($request, '/play', 'challenge.invite_shown_once');
        }
        $link = $request->baseUrl() . '/invite/' . $token;

        return $this->services->pages()->render($request, 'play/invite_link', $this->pages->text('invite.title'), [
            'link' => $link,
            'qrSvgHtml' => QrCode::svg($link),
            'expiresAt' => $challenge->expiresAt,
        ]);
    }

    // --- Answering challenges ---

    /**
     * @param array<string, string> $parts
     */
    public function accept(Request $request, array $parts): Response
    {
        try {
            $gameId = $this->services->play()->challenges()->accept(PlayPages::idFrom($parts), $this->pages->user()->id, $this->services->now, $this->services->nowMs());
        } catch (PlayRefused $refusal) {
            return $this->pages->refused($request, '/play', $refusal);
        }

        return Response::redirect($request->basePath . '/games/' . $gameId);
    }

    /**
     * @param array<string, string> $parts
     */
    public function decline(Request $request, array $parts): Response
    {
        try {
            $this->services->play()->challenges()->decline(PlayPages::idFrom($parts), $this->pages->user()->id);
        } catch (PlayRefused $refusal) {
            return $this->pages->refused($request, '/play', $refusal);
        }

        return $this->pages->done($request, '/play', 'challenge.declined');
    }

    /**
     * @param array<string, string> $parts
     */
    public function cancel(Request $request, array $parts): Response
    {
        try {
            $this->services->play()->challenges()->cancel(PlayPages::idFrom($parts), $this->pages->user()->id);
        } catch (PlayRefused $refusal) {
            return $this->pages->refused($request, '/play', $refusal);
        }

        return $this->pages->done($request, '/play', 'challenge.cancelled');
    }

    // --- Invite links (G-21) ---

    /**
     * Shows an invite to whoever has the link. Visitors are asked to log in
     * or register first, and come back here afterwards.
     *
     * @param array<string, string> $parts
     */
    public function showInvite(Request $request, array $parts): Response
    {
        $token = $parts['token'] ?? '';
        $challenge = $this->services->play()->challenges()->findInvite($token, $this->services->now);
        $user = $this->services->authentication()->currentUser();
        $challengerName = $challenge === null ? '' : ($this->services->users()->usernamesOf([$challenge->challengerId])[$challenge->challengerId] ?? '');

        return $this->services->pages()->render($request, 'play/invite', $this->pages->text('invite.title'), [
            'challenge' => $challenge,
            'challengerName' => $challengerName,
            'token' => $token,
            'isOwnInvite' => $challenge !== null && $user !== null && $challenge->challengerId === $user->id,
        ], $challenge === null ? 404 : 200);
    }

    /**
     * @param array<string, string> $parts
     */
    public function acceptInvite(Request $request, array $parts): Response
    {
        $token = $parts['token'] ?? '';
        $challenge = $this->services->play()->challenges()->findInvite($token, $this->services->now);
        if ($challenge === null) {
            return $this->pages->refused($request, '/play', new PlayRefused('challenge.no_longer_open'));
        }

        return $this->accept($request, ['id' => (string) $challenge->id]);
    }
}
