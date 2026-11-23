<?php

declare(strict_types=1);

namespace PhpChess\Http;

use PhpChess\Domain\Play\Game;

/**
 * The state of one game as JSON data (G-04, G-10, API-02).
 *
 * The same shape answers the board's polling (/games/{id}/state) and the API
 * (/api/v1/games/{id}), so the board script and an app read the same thing:
 *
 *   { "id": 12, "ply": 5, "fen": "...", "side_to_move": "b", "state": "running",
 *     "result": null, "end_reason": null, "white": {"id": 1, "name": "ann"},
 *     "black": {...}, "your_colour": "w", "draw_offered_by": null,
 *     "rematch_offered_by": null, "rematch_game_id": null,
 *     "move_deadline_at": "2027-01-05T10:00:00Z", "moves": [{"ply": 5, "uci": "e7e5", "san": "e5"}] }
 *
 * "moves" holds only the moves after the ply the caller already has (since=).
 */
final class GameStateJson
{
    /**
     * @param list<array{ply: int, uci: string, san: string, fen_after: string}> $moves  the moves after "since"
     * @param array<int, string> $usernames  id => name, for both players
     * @return array<string, mixed>
     */
    public static function of(Game $game, array $moves, array $usernames, ?int $viewerId): array
    {
        $moveList = [];
        foreach ($moves as $move) {
            $moveList[] = ['ply' => $move['ply'], 'uci' => $move['uci'], 'san' => $move['san']];
        }

        return [
            'id' => $game->id,
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
            'move_deadline_at' => $game->moveDeadlineAt === null ? null : gmdate('Y-m-d\TH:i:s\Z', $game->moveDeadlineAt),
            'moves' => $moveList,
        ];
    }
}
