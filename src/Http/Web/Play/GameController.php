<?php

declare(strict_types=1);

namespace PhpChess\Http\Web\Play;

use PhpChess\Domain\ActionRefused;
use PhpChess\Domain\Chess\Pgn;
use PhpChess\Domain\Community\GameChat;
use PhpChess\Domain\System\FeatureSwitches;
use PhpChess\Domain\Play\Game;
use PhpChess\Domain\Play\Games;
use PhpChess\Domain\Play\PlayRefused;
use PhpChess\Domain\Rating\Ratings;
use PhpChess\Http\GamePoll;
use PhpChess\Http\GameStateJson;
use PhpChess\Http\Request;
use PhpChess\Http\Response;
use PhpChess\Kernel\Services;

/**
 * The game pages: the board, its live updates, moves and the game buttons,
 * the archive, watching, and PGN export (G-02, G-04, G-09 to G-11, G-13, G-22, A36).
 *
 * Addresses (Routes.php), members only:
 *   GET  /games                       my running and finished games (the archive, G-11)
 *   GET  /games/watch                 running games of everyone (G-13; switch "player list visibility")
 *   GET  /games/{id}                  the board, move list, clock and buttons (A36)
 *   GET  /games/{id}/state?since=N&v=V the game as JSON for the board's polling (G-10, API-03, GamePoll)
 *   GET  /games/{id}/pgn              the game as a PGN file (G-11)
 *   POST /games/{id}/move             one move: fields ply and move (UCI or SAN)
 *   POST /games/{id}/resign | draw/offer | draw/accept | draw/decline | abort | abort/request | rematch
 *   POST /games/{id}/premove          a premove while the opponent thinks (G-17): fields ply and move
 *   POST /games/{id}/premove/clear    takes the premove back
 *   POST /games/{id}/pace/offer       proposes a new pace (G-23): field pace, "10+5" or "3d"
 *   POST /games/{id}/pace/accept | pace/decline
 *
 * A POST from the board script asks for JSON (header Accept: application/json)
 * and gets the new state; a plain form (no script, or the keyboard field) is
 * sent back to the board page. The services check "is this your game"
 * again (K14); this controller only chooses the answer.
 */
final class GameController
{
    public function __construct(
        private readonly Services $services,
        private readonly PlayPages $pages,
    ) {
    }

    // --- Lists ---

    /** My games: running first, then the archive of finished ones. */
    public function myGames(Request $request): Response
    {
        $user = $this->pages->user();
        $games = $this->services->play()->gameRepository();
        $running = $games->listForPlayer($user->id, Game::STATE_RUNNING);
        $finished = $games->listForPlayer($user->id, Game::STATE_FINISHED);

        return $this->services->pages()->render($request, 'play/games', $this->pages->text('games.title'), [
            'runningGames' => $running,
            'finishedGames' => $finished,
            'usernames' => $this->usernamesFor([...$running, ...$finished]),
            'viewerId' => $user->id,
        ]);
    }

    /** Running games of everyone, to watch (G-13). */
    public function watchList(Request $request): Response
    {
        $games = $this->services->play()->gameRepository()->listAll(Game::STATE_RUNNING);

        return $this->services->pages()->render($request, 'play/watch', $this->pages->text('watch.title'), [
            'games' => $games,
            'usernames' => $this->usernamesFor($games),
        ]);
    }

    // --- The board (A36) ---

    /**
     * @param array<string, string> $parts
     */
    public function show(Request $request, array $parts): Response
    {
        $user = $this->pages->user();
        $game = $this->services->play()->gameRepository()->find(PlayPages::idFrom($parts));
        if ($game === null || !$this->pages->mayWatch($game, $user)) {
            return $this->pages->notFound($request);
        }
        $now = $this->services->now;
        if ($game->isPlayer($user->id)) {
            $this->services->play()->games()->markSeen($game, $user->id, $now);
            $this->services->play()->noticeRepository()->markGameRead($user->id, $game->id, $now);
        }

        $moves = $this->services->play()->gameRepository()->moves($game->id);
        $usernames = $this->usernamesFor([$game]);
        $ratings = $this->services->play()->ratings();
        $state = GameStateJson::of($game, $moves, $usernames, $user->id, $this->services->nowMs());
        $play = $this->services->play()->games();

        return $this->services->pages()->render($request, 'play/game', $this->pages->text('game.title', [
            'white' => $usernames[$game->whiteId] ?? '?',
            'black' => $usernames[$game->blackId] ?? '?',
        ]), [
            'game' => $game,
            'moves' => $moves,
            'usernames' => $usernames,
            'viewerId' => $user->id,
            'stateJson' => json_encode($state, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            'canAbort' => $game->isPlayer($user->id) && Games::canStillAbort($game),
            'abortRule' => $this->services->play()->games()->abortRule(),
            'whiteRating' => $ratings->shownRating($game->whiteId, $game->speed),
            'blackRating' => $ratings->shownRating($game->blackId, $game->speed),
            'boardTheme' => $this->services->currentPreferences()->boardTheme,
            'planningBoardTheme' => $this->services->settings()->getString('play.planning_board_theme', 'blue'),
            'chat' => $this->chatFor($game),
            'paceChoices' => [...$play->allowedTimeControls(), ...array_map(fn (int $days): string => $days . Game::PACE_DAYS_SUFFIX, [1, 2, 3, 5, 7, 14])],
            'currentPace' => Games::paceOf($game),
        ]);
    }

    /**
     * The game chat for the page (G-12), or null when the switch "game chat" is off.
     *
     * @return array{lines: list<array{id: int, user_id: int, username: string, body: string, created_at: string}>, mode: string, phrases: list<string>, maxLength: int}|null
     */
    private function chatFor(Game $game): ?array
    {
        if (!$this->services->switches()->isOn(FeatureSwitches::GAME_CHAT)) {
            return null;
        }
        $chat = $this->services->gameChat();

        return [
            'lines' => $chat->lines($game->id),
            'mode' => $chat->mode(),
            'phrases' => GameChat::PHRASES,
            'maxLength' => GameChat::MAX_LENGTH,
        ];
    }

    /**
     * Adds a chat line (G-12): players only; phrases or free text as the admin chose.
     *
     * @param array<string, string> $parts
     */
    public function chat(Request $request, array $parts): Response
    {
        $user = $this->pages->user();
        $game = $this->services->play()->gameRepository()->find(PlayPages::idFrom($parts));
        if ($game === null || !$this->pages->mayWatch($game, $user)) {
            return $this->pages->notFound($request);
        }
        try {
            $this->services->gameChat()->say($game, $user->id, $request->formValue('text'), $this->services->now);
        } catch (ActionRefused $refusal) {
            return $this->services->pages()->redirectWithMessage($request, '/games/' . $game->id, 'error', $this->pages->text($refusal->languageKey, $refusal->values));
        }

        return Response::redirect($request->basePath . '/games/' . $game->id . '#game-chat-title');
    }

    /**
     * The game as JSON, with the moves after "since" (G-10, API-02).
     * A player's request also tells the server they are watching live.
     *
     * @param array<string, string> $parts
     */
    public function state(Request $request, array $parts): Response
    {
        $user = $this->pages->user();
        $gameRepository = $this->services->play()->gameRepository();
        $game = $gameRepository->find(PlayPages::idFrom($parts));
        if ($game === null || !$this->pages->mayWatch($game, $user)) {
            return Response::json(['error' => ['code' => 'not_found', 'message' => $this->pages->text('play.game_not_found')]], 404);
        }
        $since = max(0, (int) $request->queryValue('since', '0'));
        $answer = (new GamePoll($this->services))->answer($game, $user->id, $since, $request->queryValue('v', ''));

        return Response::json($answer)->withHeader('Cache-Control', 'no-store');
    }

    // --- Moves (K2) ---

    /**
     * @param array<string, string> $parts
     */
    public function move(Request $request, array $parts): Response
    {
        $gameId = PlayPages::idFrom($parts);
        $user = $this->pages->user();
        try {
            $this->services->play()->games()->move(
                $gameId,
                $user->id,
                (int) $request->formValue('ply', '-1'),
                $request->formValue('move'),
                $this->services->now,
                $this->services->nowMs(),
            );
        } catch (PlayRefused $refusal) {
            return $this->answer($request, $gameId, $refusal);
        }

        return $this->answer($request, $gameId, null);
    }

    // --- The game buttons (G-09, G-22) ---

    /**
     * @param array<string, string> $parts
     */
    public function resign(Request $request, array $parts): Response
    {
        return $this->runAction($request, $parts, fn (int $gameId, int $userId, int $now) => $this->services->play()->games()->resign($gameId, $userId, $now));
    }

    /**
     * @param array<string, string> $parts
     */
    public function offerDraw(Request $request, array $parts): Response
    {
        return $this->runAction($request, $parts, fn (int $gameId, int $userId, int $now) => $this->services->play()->games()->offerDraw($gameId, $userId, $now));
    }

    /**
     * @param array<string, string> $parts
     */
    public function acceptDraw(Request $request, array $parts): Response
    {
        return $this->runAction($request, $parts, fn (int $gameId, int $userId, int $now) => $this->services->play()->games()->acceptDraw($gameId, $userId, $now));
    }

    /**
     * @param array<string, string> $parts
     */
    public function declineDraw(Request $request, array $parts): Response
    {
        return $this->runAction($request, $parts, fn (int $gameId, int $userId, int $now) => $this->services->play()->games()->declineDraw($gameId, $userId));
    }

    /**
     * @param array<string, string> $parts
     */
    public function abort(Request $request, array $parts): Response
    {
        return $this->runAction($request, $parts, fn (int $gameId, int $userId, int $now) => $this->services->play()->games()->abort($gameId, $userId, $now));
    }

    // --- Premoves (G-17) and changing pace (G-23) ---

    /**
     * @param array<string, string> $parts
     */
    public function premove(Request $request, array $parts): Response
    {
        $ply = (int) $request->formValue('ply', '-1');
        $move = $request->formValue('move');

        return $this->runAction($request, $parts, fn (int $gameId, int $userId, int $now) => $this->services->play()->games()->premove($gameId, $userId, $ply, $move));
    }

    /**
     * @param array<string, string> $parts
     */
    public function clearPremove(Request $request, array $parts): Response
    {
        return $this->runAction($request, $parts, fn (int $gameId, int $userId, int $now) => $this->services->play()->games()->clearPremove($gameId, $userId));
    }

    /**
     * @param array<string, string> $parts
     */
    public function offerPace(Request $request, array $parts): Response
    {
        $pace = $request->formValue('pace');

        return $this->runAction($request, $parts, fn (int $gameId, int $userId, int $now) => $this->services->play()->games()->proposePace($gameId, $userId, $pace, $now, $this->services->nowMs()));
    }

    /**
     * @param array<string, string> $parts
     */
    public function acceptPace(Request $request, array $parts): Response
    {
        return $this->runAction($request, $parts, fn (int $gameId, int $userId, int $now) => $this->services->play()->games()->acceptPace($gameId, $userId, $now, $this->services->nowMs()));
    }

    /**
     * @param array<string, string> $parts
     */
    public function declinePace(Request $request, array $parts): Response
    {
        return $this->runAction($request, $parts, fn (int $gameId, int $userId, int $now) => $this->services->play()->games()->declinePace($gameId, $userId));
    }

    /**
     * Asks the admins to abort the game, with a reason (A33, rule "ask an admin").
     * The reason goes to the audit log; the admins get a notice.
     *
     * @param array<string, string> $parts
     */
    public function requestAbort(Request $request, array $parts): Response
    {
        $user = $this->pages->user();
        $game = $this->services->play()->gameRepository()->find(PlayPages::idFrom($parts));
        if ($game === null || !$game->isPlayer($user->id) || !Games::canStillAbort($game)) {
            return $this->pages->notFound($request);
        }
        $reason = mb_substr(trim($request->formValue('reason')), 0, 300);
        if ($reason === '') {
            return $this->pages->refused($request, '/games/' . $game->id, new PlayRefused('play.abort_reason_needed'));
        }
        $this->services->auditLog()->record($user->id, 'game.abort_request', "Game {$game->id}: {$reason}", $request->clientIp);
        $this->services->play()->notices()->tellAdmins($this->services->users()->activeAdminIds(), $game, $user->id, $this->services->now);

        return $this->pages->done($request, '/games/' . $game->id, 'play.abort_requested');
    }

    /**
     * Offers a rematch, or accepts the opponent's offer and goes to the new game.
     *
     * @param array<string, string> $parts
     */
    public function rematch(Request $request, array $parts): Response
    {
        $gameId = PlayPages::idFrom($parts);
        try {
            $newGameId = $this->services->play()->games()->rematch($gameId, $this->pages->user()->id, $this->services->now);
        } catch (PlayRefused $refusal) {
            return $this->answer($request, $gameId, $refusal);
        }
        if ($newGameId !== null) {
            return Response::redirect($request->basePath . '/games/' . $newGameId);
        }

        return $this->pages->done($request, '/games/' . $gameId, 'play.rematch_offered');
    }

    // --- PGN export (G-11) ---

    /**
     * @param array<string, string> $parts
     */
    public function pgn(Request $request, array $parts): Response
    {
        $user = $this->pages->user();
        $game = $this->services->play()->gameRepository()->find(PlayPages::idFrom($parts));
        if ($game === null || !$this->pages->mayWatch($game, $user)) {
            return $this->pages->notFound($request);
        }
        $usernames = $this->usernamesFor([$game]);
        $sanMoves = array_map(fn (array $move): string => $move['san'], $this->services->play()->gameRepository()->moves($game->id));
        $pgn = Pgn::write([
            'Event' => $this->services->settings()->getString('site.name', 'phpChess') . ' game ' . $game->id,
            'Site' => $request->baseUrl() . '/games/' . $game->id,
            'Date' => gmdate('Y.m.d', $game->startedAt),
            'Round' => '-',
            'White' => $usernames[$game->whiteId] ?? '?',
            'Black' => $usernames[$game->blackId] ?? '?',
            'Result' => $game->pgnResult(),
        ], $game->startFen, $sanMoves);

        return new Response(200, $pgn, [
            'Content-Type' => 'application/x-chess-pgn; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="game-' . $game->id . '.pgn"',
        ]);
    }

    // --- Helpers ---

    /**
     * Runs one game button for the logged-in player.
     *
     * @param array<string, string> $parts
     * @param callable(int, int, int): mixed $action  (game id, user id, now)
     */
    private function runAction(Request $request, array $parts, callable $action): Response
    {
        $gameId = PlayPages::idFrom($parts);
        try {
            $action($gameId, $this->pages->user()->id, $this->services->now);
        } catch (PlayRefused $refusal) {
            return $this->answer($request, $gameId, $refusal);
        }

        return $this->answer($request, $gameId, null);
    }

    /** The answer after a move or a button: JSON for the board script, else back to the board. */
    private function answer(Request $request, int $gameId, ?PlayRefused $refusal): Response
    {
        $wantsJson = str_contains($request->header('Accept'), 'application/json');
        if (!$wantsJson) {
            return $refusal === null
                ? Response::redirect($request->basePath . '/games/' . $gameId)
                : $this->pages->refused($request, '/games/' . $gameId, $refusal);
        }
        if ($refusal !== null) {
            return Response::json(['error' => [
                'code' => $refusal->languageKey,
                'message' => $this->pages->text($refusal->languageKey, $refusal->values),
            ]], 409);
        }
        $game = $this->services->play()->gameRepository()->find($gameId);
        if ($game === null) {
            return Response::json(['error' => ['code' => 'not_found', 'message' => $this->pages->text('play.game_not_found')]], 404);
        }
        $since = max(0, (int) $request->formValue('ply', '0'));

        return Response::json((new GamePoll($this->services))->answer($game, $this->pages->user()->id, $since, ''));
    }

    /**
     * The names of the players of some games.
     *
     * @param list<Game> $games
     * @return array<int, string>
     */
    private function usernamesFor(array $games): array
    {
        $ids = [];
        foreach ($games as $game) {
            $ids[] = $game->whiteId;
            $ids[] = $game->blackId;
        }

        return $this->services->users()->usernamesOf($ids);
    }
}
