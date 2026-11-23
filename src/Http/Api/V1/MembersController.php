<?php

declare(strict_types=1);

namespace PhpChess\Http\Api\V1;

use LogicException;
use PhpChess\Domain\ActionRefused;
use PhpChess\Domain\Identity\User;
use PhpChess\Domain\Play\Challenge;
use PhpChess\Domain\Play\PlayRefused;
use PhpChess\Http\ApiError;
use PhpChess\Http\Request;
use PhpChess\Http\Response;
use PhpChess\Http\Web\Community\PlayersController;
use PhpChess\Http\Web\MemberPages;
use PhpChess\Kernel\Services;

/**
 * The rest of API v1 (API-01): the member, challenges, messages and players.
 * An app logs in with an API token (ID-10): "Authorization: Bearer <token>".
 * Reads are GET, every change is POST; errors use ApiError.
 *
 *   GET  /api/v1/me                          who I am, and how many games wait for my move
 *   GET  /api/v1/challenges                  open challenges, and those sent to me
 *   POST /api/v1/challenges/{id}/accept      accept one: answers the new game's id
 *   GET  /api/v1/messages                    my conversations          (switch "direct messages")
 *   GET  /api/v1/messages/{name}?after=ID    one conversation          (switch "direct messages")
 *   POST /api/v1/messages/{name}             field "body"              (switch "direct messages")
 *   GET  /api/v1/buddies                     my buddies
 *   GET  /api/v1/players/online              who's online              (switch "player list visibility")
 * The same rules as the web pages apply, school class limits included (SCH-05).
 */
final class MembersController
{
    public function __construct(private readonly Services $services)
    {
    }

    public function me(Request $request): Response
    {
        $user = $this->user();

        return Response::json([
            'id' => $user->id,
            'username' => $user->username,
            'role' => $user->role,
            'games_waiting_for_my_move' => $this->services->play()->gameRepository()->countWaitingFor($user->id),
            'unread_messages' => $this->services->unreadMessageCount(),
        ])->withHeader('Cache-Control', 'no-store');
    }

    // --- Challenges ---

    public function challenges(Request $request): Response
    {
        $user = $this->user();
        $now = $this->services->now;
        $repository = $this->services->play()->challengeRepository();
        $rules = $this->services->play()->classRules();
        $items = [];
        foreach ([...$repository->waitingFor($user->id, $now), ...$repository->waitingOpen($now, 50)] as $challenge) {
            $isMine = $challenge->challengerId === $user->id;
            $alreadyListed = isset($items[$challenge->id]);
            if ($alreadyListed || (!$isMine && !$rules->mayContact($user->id, $challenge->challengerId))) {
                continue;
            }
            $items[$challenge->id] = self::challengeJson($challenge, $user->id);
        }

        return Response::json(['challenges' => array_values($items)])->withHeader('Cache-Control', 'no-store');
    }

    /**
     * @param array<string, string> $parts
     */
    public function acceptChallenge(Request $request, array $parts): Response
    {
        try {
            $gameId = $this->services->play()->challenges()->accept(MemberPages::idFrom($parts), $this->user()->id, $this->services->now);
        } catch (PlayRefused $refusal) {
            return ApiError::response(409, $refusal->languageKey, $this->services->translator()->text($refusal->languageKey, $refusal->values));
        }

        return Response::json(['game_id' => $gameId]);
    }

    // --- Messages ---

    public function conversations(Request $request): Response
    {
        $conversations = $this->services->messageRepository()->conversationsOf($this->user()->id);

        return Response::json(['conversations' => array_map(fn (array $conversation): array => [
            'with' => $conversation['other_username'],
            'last_message' => $conversation['last_body'],
            'last_at' => $conversation['last_at'],
            'unread' => $conversation['unread'],
        ], $conversations)])->withHeader('Cache-Control', 'no-store');
    }

    /**
     * @param array<string, string> $parts
     */
    public function conversation(Request $request, array $parts): Response
    {
        $user = $this->user();
        $other = $this->services->users()->findByUsername(MemberPages::nameFrom($parts));
        if ($other === null) {
            return ApiError::response(404, 'not_found', 'There is no such player.');
        }
        $afterId = max(0, (int) $request->queryValue('after', '0'));
        $messages = $this->services->messages()->open($user->id, $other->id, $afterId, $this->services->now);

        return Response::json(['messages' => array_map(fn (array $message): array => [
            'id' => $message['id'],
            'mine' => $message['from_user_id'] === $user->id,
            'body' => $message['body'],
            'created_at' => $message['created_at'],
        ], $messages)])->withHeader('Cache-Control', 'no-store');
    }

    /**
     * @param array<string, string> $parts
     */
    public function send(Request $request, array $parts): Response
    {
        $other = $this->services->users()->findByUsername(MemberPages::nameFrom($parts));
        try {
            if ($other === null) {
                throw new ActionRefused('messages.unknown_player');
            }
            $messageId = $this->services->messages()->send($this->user()->id, $other->id, $request->formValue('body'), $this->services->now);
        } catch (ActionRefused $refusal) {
            return ApiError::response(422, $refusal->languageKey, $this->services->translator()->text($refusal->languageKey, $refusal->values));
        }

        return Response::json(['id' => $messageId], 201);
    }

    // --- Players ---

    public function buddies(Request $request): Response
    {
        return Response::json(['buddies' => array_map(fn (array $buddy): array => [
            'username' => $buddy['username'],
            'last_seen_at' => $buddy['last_seen_at'] === null ? null : gmdate('Y-m-d\TH:i:s\Z', $buddy['last_seen_at']),
        ], $this->services->buddies()->listFor($this->user()->id))]);
    }

    public function online(Request $request): Response
    {
        $user = $this->user();
        $rules = $this->services->play()->classRules();
        $names = [];
        foreach ($this->services->users()->seenSince($this->services->now - PlayersController::ONLINE_SECONDS) as $player) {
            if ($rules->mayContact($user->id, $player['id'])) {
                $names[] = $player['username'];
            }
        }

        return Response::json(['online' => $names])->withHeader('Cache-Control', 'no-store');
    }

    // --- Helpers ---

    /**
     * @return array<string, mixed>
     */
    private static function challengeJson(Challenge $challenge, int $viewerId): array
    {
        return [
            'id' => $challenge->id,
            'kind' => $challenge->kind,
            'from_me' => $challenge->challengerId === $viewerId,
            'for_me' => $challenge->opponentId === $viewerId,
            'colour' => $challenge->colour,
            'days_per_move' => $challenge->daysPerMove,
            'rated' => $challenge->isRated,
            'expires_at' => gmdate('Y-m-d\TH:i:s\Z', $challenge->expiresAt),
        ];
    }

    private function user(): User
    {
        $user = $this->services->authentication()->currentUser();
        if ($user === null) {
            throw new LogicException('A members-only API route ran without a logged-in user.');
        }

        return $user;
    }
}
