<?php

declare(strict_types=1);

namespace PhpChess\Http\Web\Play;

use PhpChess\Domain\Play\Challenge;
use PhpChess\Domain\Play\Game;
use PhpChess\Domain\Play\PlayRefused;
use PhpChess\Http\QrCode;
use PhpChess\Http\Request;
use PhpChess\Http\Response;
use PhpChess\Kernel\Services;

/**
 * The "Play" page: the one-tap Play button, challenges and invite links
 * (G-05, G-06, G-20, G-21, A28, A35).
 *
 * Addresses (Routes.php), members only unless noted:
 *   GET  /play                          Play button, my games to move in, challenges, the lobby
 *   POST /play/quick                    one-tap start with the remembered defaults (G-20)
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

    public function __construct(
        private readonly Services $services,
        private readonly PlayPages $pages,
    ) {
    }

    // --- The Play page ---

    public function lobby(Request $request): Response
    {
        $user = $this->pages->user();
        $now = $this->services->now;
        $play = $this->services->play();
        $challengeRepository = $play->challengeRepository();
        $myGames = $play->gameRepository()->listForPlayer($user->id, Game::STATE_RUNNING);
        $myChallenges = $challengeRepository->waitingFor($user->id, $now);
        $openChallenges = $challengeRepository->waitingOpen($now);

        $userIds = [];
        foreach ($myGames as $game) {
            $userIds[] = $game->whiteId;
            $userIds[] = $game->blackId;
        }
        foreach ([...$myChallenges, ...$openChallenges] as $challenge) {
            $userIds[] = $challenge->challengerId;
            if ($challenge->opponentId !== null) {
                $userIds[] = $challenge->opponentId;
            }
        }
        $opponents = $this->services->users()->activeUsernames();
        unset($opponents[$user->id]);

        return $this->services->pages()->render($request, 'play/lobby', $this->pages->text('play.title'), [
            'viewerId' => $user->id,
            'myGames' => $myGames,
            'myChallenges' => $myChallenges,
            'openChallenges' => $openChallenges,
            'usernames' => $this->services->users()->usernamesOf($userIds),
            'opponents' => $opponents,
            'playDefaults' => $this->services->preferenceRepository()->playDefaults($user->id),
            'maximumDays' => $play->challenges()->maximumDays(),
            'isFenAllowed' => $play->challenges()->isFenAllowed(),
        ]);
    }

    /** The one-tap Play button: pairs with whoever waits, or opens a challenge (G-20). */
    public function quickPlay(Request $request): Response
    {
        $user = $this->pages->user();
        $challenges = $this->services->play()->challenges();
        $daysPerMove = (int) $request->formValue('days_per_move', '3');
        $isRated = $request->formValue('rated') === 'yes';
        try {
            $result = $challenges->quickPlay($user->id, $daysPerMove, $isRated, $this->services->now);
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

        try {
            switch ($request->formValue('kind')) {
                case Challenge::KIND_DIRECT:
                    $opponent = $this->services->users()->findByUsernameOrEmail(trim($request->formValue('opponent')));
                    if ($opponent === null || !$opponent->isActive()) {
                        throw new PlayRefused('challenge.opponent_unknown');
                    }
                    $challenges->createDirect($user->id, $opponent->id, $colour, $daysPerMove, $isRated, $startFen, $now);

                    return $this->pages->done($request, '/play', 'challenge.sent');
                case Challenge::KIND_INVITE:
                    $invite = $challenges->createInvite($user->id, $colour, $daysPerMove, $isRated, $startFen, $now);
                    $this->services->session->set(self::NEW_INVITE_KEY, $invite['token']);

                    return Response::redirect($request->basePath . '/challenges/' . $invite['challenge_id'] . '/invite');
                default:
                    $challenges->createOpen($user->id, $colour, $daysPerMove, $isRated, $now);

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
            $gameId = $this->services->play()->challenges()->accept(PlayPages::idFrom($parts), $this->pages->user()->id, $this->services->now);
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
