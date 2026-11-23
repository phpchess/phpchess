<?php

declare(strict_types=1);

namespace PhpChess\Http\Api\V1;

use PhpChess\Domain\Play\Game;
use PhpChess\Domain\Play\PlayRefused;
use PhpChess\Http\ApiError;
use PhpChess\Http\GamePoll;
use PhpChess\Http\GameStateJson;
use PhpChess\Http\Request;
use PhpChess\Http\Response;
use PhpChess\Kernel\Services;

/**
 * The games API (API-02): polling for game state, and moves.
 *
 *   GET  /api/v1/games                 my running games, with an ETag (304 when unchanged)
 *   GET  /api/v1/games/{id}?since=N    one game, with the moves after ply N
 *   POST /api/v1/games/{id}/move       fields ply and move; answers the new state
 *
 * The same JSON shape as the board's polling (GameStateJson). Logged-in
 * members, by session or API token (ID-10). Errors use ApiError.
 */
final class GamesController
{
    public function __construct(private readonly Services $services)
    {
    }

    /**
     * My running games. The ETag changes whenever a game gets a move or
     * ends, so a client that sends If-None-Match gets a short 304 otherwise.
     */
    public function list(Request $request): Response
    {
        $user = $this->currentUserId();
        $games = $this->services->play()->gameRepository()->listForPlayer($user, Game::STATE_RUNNING);

        $items = [];
        $fingerprint = '';
        foreach ($games as $game) {
            $items[] = [
                'id' => $game->id,
                'ply' => $game->ply,
                'your_turn' => $game->playerToMoveId() === $user,
                'move_deadline_at' => $game->moveDeadlineAt === null ? null : gmdate('Y-m-d\TH:i:s\Z', $game->moveDeadlineAt),
            ];
            $fingerprint .= $game->id . ':' . $game->ply . ':' . $game->state . ';';
        }
        $etag = '"' . hash('sha256', $fingerprint) . '"';
        if ($request->header('If-None-Match') === $etag) {
            return (new Response(304))->withHeader('ETag', $etag);
        }

        return Response::json(['games' => $items])->withHeader('ETag', $etag)->withHeader('Cache-Control', 'private, no-cache');
    }

    /**
     * @param array<string, string> $parts
     */
    public function show(Request $request, array $parts): Response
    {
        $game = $this->visibleGame($parts);
        if ($game === null) {
            return ApiError::response(404, 'not_found', 'There is no such game.');
        }
        $since = max(0, (int) $request->queryValue('since', '0'));
        $answer = (new GamePoll($this->services))->answer($game, $this->currentUserId(), $since, $request->queryValue('v', ''));

        return Response::json($answer)->withHeader('Cache-Control', 'no-store');
    }

    /**
     * @param array<string, string> $parts
     */
    public function move(Request $request, array $parts): Response
    {
        $gameId = (int) ($parts['id'] ?? 0);
        $expectedPly = (int) $request->formValue('ply', '-1');
        try {
            $game = $this->services->play()->games()->move($gameId, $this->currentUserId(), $expectedPly, $request->formValue('move'), $this->services->now, $this->services->nowMs());
        } catch (PlayRefused $refusal) {
            $status = $refusal->languageKey === 'play.not_your_game' ? 404 : 409;

            return ApiError::response($status, $refusal->languageKey, $this->services->translator()->text($refusal->languageKey, $refusal->values));
        }

        return $this->stateResponse($game, max(0, $expectedPly));
    }

    // --- Helpers ---

    private function stateResponse(Game $game, int $since): Response
    {
        $usernames = $this->services->users()->usernamesOf([$game->whiteId, $game->blackId]);
        $moves = $this->services->play()->gameRepository()->moves($game->id, $since);

        return Response::json(GameStateJson::of($game, $moves, $usernames, $this->currentUserId(), $this->services->nowMs()))->withHeader('Cache-Control', 'no-store');
    }

    /**
     * @param array<string, string> $parts
     */
    private function visibleGame(array $parts): ?Game
    {
        $game = $this->services->play()->gameRepository()->find((int) ($parts['id'] ?? 0));
        $user = $this->services->authentication()->currentUser();
        if ($game === null || $user === null) {
            return null;
        }
        $rules = $this->services->play()->classRules();
        $isTheirTeacher = $rules->mayLookAfter($user, $game->whiteId) || $rules->mayLookAfter($user, $game->blackId);
        $mayWatch = $game->isPlayer($user->id) || $isTheirTeacher || $this->services->switches()->isOn('player_list_visibility');

        return $mayWatch ? $game : null;
    }

    private function currentUserId(): int
    {
        return $this->services->authentication()->currentUser()->id ?? 0;
    }
}
