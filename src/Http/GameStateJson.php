<?php

declare(strict_types=1);

namespace PhpChess\Http;

use PhpChess\Domain\Chess\Position;
use PhpChess\Domain\Play\Game;
use PhpChess\Domain\Play\Games;

/**
 * The state of one game as JSON data (G-04, G-10, G-14, API-02, API-03).
 *
 * The same shape answers the board's polling (/games/{id}/state) and the API
 * (/api/v1/games/{id}), so the board script and an app read the same thing:
 *
 *   { "id": 12, "version": "3f9a0c1d2e4b", "ply": 5, "fen": "...", "side_to_move": "b",
 *     "state": "running", "result": null, "end_reason": null, "white": {"id": 1, "name": "ann"},
 *     "black": {...}, "your_colour": "w", "draw_offered_by": null,
 *     "rematch_offered_by": null, "rematch_game_id": null,
 *     "move_deadline_at": "2027-01-05T10:00:00Z",
 *     "time_control": "5+3",
 *     "clock": {"white_ms": 281000, "black_ms": 297400, "running": "b"},
 *     "premove": "e7e5", "pace_offer": {"by": 1, "pace": "3d"},
 *     "moves": [{"ply": 5, "uci": "e7e5", "san": "e5"}] }
 *
 * - "moves" holds only the moves after the ply the caller already has (since=).
 * - "clock" is null in a correspondence game. The running clock already has
 *   the time used so far taken off; the page counts it down from there.
 * - "premove" is shown to its owner only.
 * - "version" changes whenever anything above changes, except the running
 *   clock; a poll that sends it back gets a short "unchanged" answer.
 */
final class GameStateJson
{
    /**
     * @param list<array{ply: int, uci: string, san: string, fen_after: string}> $moves  the moves after "since"
     * @param array<int, string> $usernames  id => name, for both players
     * @return array<string, mixed>
     */
    public static function of(Game $game, array $moves, array $usernames, ?int $viewerId, ?int $nowMs = null): array
    {
        $moveList = [];
        foreach ($moves as $move) {
            $moveList[] = ['ply' => $move['ply'], 'uci' => $move['uci'], 'san' => $move['san']];
        }

        return [
            'id' => $game->id,
            'version' => self::version($game),
            'ply' => $game->ply,
            'fen' => $game->currentFen,
            'start_fen' => $game->startFen,
            'side_to_move' => $game->sideToMove(),
            'state' => $game->state,
            'result' => $game->result,
            'end_reason' => $game->endReason,
            'is_rated' => $game->isRated,
            'days_per_move' => $game->daysPerMove,
            'white' => ['id' => $game->whiteId, 'name' => $usernames[$game->whiteId] ?? ''],
            'black' => ['id' => $game->blackId, 'name' => $usernames[$game->blackId] ?? ''],
            'your_colour' => $viewerId === null ? null : $game->colourOf($viewerId),
            'draw_offered_by' => $game->drawOfferedBy,
            'rematch_offered_by' => $game->rematchOfferedBy,
            'rematch_game_id' => $game->rematchGameId,
            'move_deadline_at' => $game->moveDeadlineAt === null || $game->isTimed() ? null : gmdate('Y-m-d\TH:i:s\Z', $game->moveDeadlineAt),
            'time_control' => $game->timeControl,
            'clock' => self::clock($game, $nowMs ?? time() * 1000),
            'premove' => $viewerId !== null && $game->premoveBy === $viewerId ? $game->premoveUci : null,
            'pace_offer' => $game->paceOfferedBy === null ? null : ['by' => $game->paceOfferedBy, 'pace' => $game->paceOffer],
            'moves' => $moveList,
        ];
    }

    /**
     * Both clocks of a timed game in ms, the running one as it is now; null
     * for correspondence.
     *
     * @return array{white_ms: int, black_ms: int, running: string|null}|null
     */
    public static function clock(Game $game, int $nowMs): ?array
    {
        if (!$game->isTimed()) {
            return null;
        }
        $white = $game->msLeftAtTurnStart(Position::WHITE);
        $black = $game->msLeftAtTurnStart(Position::BLACK);
        $runningLeft = Games::runningClockLeft($game, $nowMs);
        if ($runningLeft !== null) {
            if ($game->sideToMove() === Position::WHITE) {
                $white = max(0, $runningLeft);
            } else {
                $black = max(0, $runningLeft);
            }
        }

        return ['white_ms' => $white, 'black_ms' => $black, 'running' => $game->isRunning() ? $game->sideToMove() : null];
    }

    /** A short fingerprint of everything a poll needs to know about (not the running clock). */
    public static function version(Game $game): string
    {
        $parts = [
            $game->ply, $game->state, $game->drawOfferedBy, $game->rematchOfferedBy, $game->rematchGameId,
            $game->timeControl, $game->daysPerMove, $game->premoveBy, $game->premoveUci, $game->paceOfferedBy, $game->paceOffer,
            $game->whiteMsLeft, $game->blackMsLeft,
        ];

        return substr(hash('sha256', implode('|', array_map(fn (mixed $part): string => (string) $part, $parts))), 0, 12);
    }
}
