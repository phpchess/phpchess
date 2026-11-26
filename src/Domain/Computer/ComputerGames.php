<?php

declare(strict_types=1);

namespace PhpChess\Domain\Computer;

use PhpChess\Domain\Chess\Position;
use PhpChess\Domain\Play\Game;
use PhpChess\Domain\Play\Games;
use PhpChess\Domain\Play\PlayRefused;
use PhpChess\Infra\Computer\ComputerAccountRepository;
use PhpChess\Infra\Computer\HeldMoveRepository;
use PhpChess\Infra\Play\GameRepository;
use PhpChess\Infra\System\SettingsRepository;

/**
 * Games against the computer (AI-01, AI-06, K1, decisions A50 and A53).
 *
 * - Starting: a member picks a computer player and a colour; the game is an
 *   ordinary unrated correspondence game against that player's account
 *   (created the first time anyone plays it).
 * - The computer's moves: the engine runs in the member's browser
 *   (public/assets/js/engine/), which sends the move it decided here. The
 *   move then goes through the one move function, Games::move(), exactly like
 *   a person's move, so the server checks it (K1). Only the human player of
 *   that game may send the computer's move, and only on the computer's turn.
 * - The browser could send any legal move for the computer; that is accepted
 *   because these games are never rated (A4).
 * - Keeping the pace (F2 review, A86): in a correspondence game the reply is
 *   not played at once. It is held (HeldMoveRepository) and played by the job
 *   runner (ComputerMoveJob, cron or pseudo-cron) between a quarter and three
 *   quarters of the days per move after the player's move, even when nobody
 *   is online. Timed games are answered at once, as before.
 */
final class ComputerGames
{
    /** Days per move in a game against the computer: the member's own pace (the computer answers at once). */
    public const int DAYS_PER_MOVE = 14;

    // --- The admin's depth cap (A49) ---
    public const string DEPTH_CAP_SETTING = 'computer.depth_cap';
    public const int DEFAULT_DEPTH_CAP = 8;
    public const int LOWEST_DEPTH_CAP = 1;
    public const int HIGHEST_DEPTH_CAP = 10;

    /** Where tools/calibrate.mjs writes the latest result (A51). */
    public const string CALIBRATION_FILE = __DIR__ . '/calibration-result.json';

    public function __construct(
        private readonly ComputerPlayers $players,
        private readonly ComputerAccountRepository $accounts,
        private readonly GameRepository $gameRepository,
        private readonly Games $games,
        private readonly HeldMoveRepository $heldMoves,
    ) {
    }

    // --- The players ---

    /**
     * Every computer player, weakest first.
     *
     * @return list<ComputerPlayer>
     */
    public function allPlayers(): array
    {
        return $this->players->all();
    }

    /**
     * The strength bands of the new game pop-up (F2, A70, A83): name =>
     * [lowest rating, highest rating]. Choosing a band starts a game against
     * one computer player of that band, picked at random.
     */
    public const array STRENGTH_BANDS = [
        'beginner' => [0, 800],
        'improver' => [801, 1300],
        'club' => [1301, 1700],
        'strong' => [1701, 3000],
    ];

    /**
     * The players of one strength band, weakest first (empty for an unknown band).
     *
     * @return list<ComputerPlayer>
     */
    public function playersInBand(string $band): array
    {
        if (!isset(self::STRENGTH_BANDS[$band])) {
            return [];
        }
        [$lowest, $highest] = self::STRENGTH_BANDS[$band];
        $inBand = [];
        foreach ($this->players->all() as $player) {
            if ($player->elo >= $lowest && $player->elo <= $highest) {
                $inBand[] = $player;
            }
        }

        return $inBand;
    }

    /**
     * Starts a game against a computer player of the band, picked at random,
     * and returns the game's id. Refused for an unknown or empty band.
     * The pace is chosen as for start().
     */
    public function startInBand(int $memberId, string $band, string $colour, int $now, int $daysPerMove = self::DAYS_PER_MOVE, string $timeControl = '', ?int $nowMs = null): int
    {
        $players = $this->playersInBand($band);
        if ($players === []) {
            throw new PlayRefused('computer.player_not_found');
        }
        $player = $players[random_int(0, count($players) - 1)];

        return $this->start($memberId, $player->id, $colour, $now, $daysPerMove, $timeControl, $nowMs);
    }

    /** One computer player by its id, or null. */
    public function findPlayer(int $computerPlayerId): ?ComputerPlayer
    {
        return $this->players->find($computerPlayerId);
    }

    /** The highest search depth any computer player may use on this site (A49). */
    public function depthCap(SettingsRepository $settings): int
    {
        $cap = $settings->getInt(self::DEPTH_CAP_SETTING, self::DEFAULT_DEPTH_CAP);

        return max(self::LOWEST_DEPTH_CAP, min(self::HIGHEST_DEPTH_CAP, $cap));
    }

    /**
     * The latest calibration result, or null before the first run:
     * ['date' => '2026-11-25', 'games' => 7860,
     *  'players' => [id => ['elo' => 612, 'low' => 540, 'high' => 690]]].
     *
     * @return array{date: string, games: int, players: array<int, array{elo: int, low: int, high: int}>}|null
     */
    public function calibrationResult(): ?array
    {
        if (!is_file(self::CALIBRATION_FILE)) {
            return null;
        }
        $data = json_decode((string) file_get_contents(self::CALIBRATION_FILE), true);
        if (!is_array($data) || !is_array($data['players'] ?? null)) {
            return null;
        }
        $players = [];
        foreach ($data['players'] as $id => $rating) {
            if (is_array($rating)) {
                $players[(int) $id] = [
                    'elo' => self::whole($rating['elo'] ?? 0),
                    'low' => self::whole($rating['low'] ?? 0),
                    'high' => self::whole($rating['high'] ?? 0),
                ];
            }
        }

        return [
            'date' => is_string($data['date'] ?? null) ? $data['date'] : '',
            'games' => self::whole($data['games'] ?? 0),
            'players' => $players,
        ];
    }

    private static function whole(mixed $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }

    // --- Accounts ---

    /** The user id of a computer player's account, creating the account the first time. */
    public function accountFor(ComputerPlayer $player): int
    {
        return $this->accounts->userIdFor($player->id) ?? $this->accounts->create($player->id, $player->username());
    }

    /** The computer player behind an account, or null for a person. */
    public function playerOf(int $userId): ?ComputerPlayer
    {
        $playerId = $this->accounts->computerPlayerIdOf($userId);

        return $playerId === null ? null : $this->players->find($playerId);
    }

    /**
     * The computer side of a game: ['colour' => 'w' or 'b', 'player' => ...],
     * or null when two people play.
     *
     * @return array{colour: string, player: ComputerPlayer}|null
     */
    public function computerSideOf(Game $game): ?array
    {
        foreach ([Position::WHITE => $game->whiteId, Position::BLACK => $game->blackId] as $colour => $userId) {
            $player = $this->playerOf($userId);
            if ($player !== null) {
                return ['colour' => $colour, 'player' => $player];
            }
        }

        return null;
    }

    // --- Starting a game ---

    /**
     * Starts an unrated game between a member and a computer player and
     * returns its id. $colour is "w", "b" or "random" (decided by the time).
     *
     * The pace is chosen like for a game between people (F2 review, A85):
     * days per move, or a time control ("5+3", empty for none), checked
     * against the admin's list and the live game limits.
     */
    public function start(int $memberId, int $computerPlayerId, string $colour, int $now, int $daysPerMove = self::DAYS_PER_MOVE, string $timeControl = '', ?int $nowMs = null): int
    {
        $control = null;
        if (trim($timeControl) !== '') {
            $control = $this->games->checkedTimeControl($timeControl)->text();
            $this->games->checkLiveLimits([$memberId]);
        }
        if ($daysPerMove < 1) {
            $daysPerMove = self::DAYS_PER_MOVE;
        }
        $player = $this->players->find($computerPlayerId);
        if ($player === null) {
            throw new PlayRefused('computer.player_not_found');
        }
        $computerId = $this->accountFor($player);
        if ($colour !== Position::WHITE && $colour !== Position::BLACK) {
            $colour = $now % 2 === 0 ? Position::WHITE : Position::BLACK;
        }
        $whiteId = $colour === Position::WHITE ? $memberId : $computerId;
        $blackId = $colour === Position::WHITE ? $computerId : $memberId;

        return $this->games->start($whiteId, $blackId, $daysPerMove, false, Position::START_FEN, $now, $control, $nowMs);
    }

    // --- The computer's move (K1) ---

    /**
     * Plays the move the member's browser decided for the computer. Refused
     * unless the sender plays this game against a computer and it is the
     * computer's turn; the move itself is checked by Games::move().
     */
    public function playComputerMove(int $gameId, int $senderId, int $expectedPly, string $moveText, int $now, ?int $nowMs = null): Game
    {
        $game = $this->gameRepository->find($gameId);
        if ($game === null || !$game->isPlayer($senderId)) {
            throw new PlayRefused('play.game_not_found');
        }
        $computer = $this->computerSideOf($game);
        if ($computer === null || $computer['colour'] !== $game->sideToMove()) {
            throw new PlayRefused('computer.not_its_turn');
        }
        $computerId = $computer['colour'] === Position::WHITE ? $game->whiteId : $game->blackId;

        // A correspondence game: keep the reply for later, to the pace (A86).
        if (!$game->isTimed()) {
            if ($game->ply !== $expectedPly) {
                throw new PlayRefused('play.board_out_of_date');
            }
            // Checked now with the same rules as every move (K1), played later.
            if (Games::readMove($game->position(), $moveText) === null) {
                throw new PlayRefused('play.illegal_move');
            }
            $this->heldMoves->hold($gameId, $expectedPly, $moveText, $this->replyTime($game, $now));

            return $game;
        }

        return $this->games->move($gameId, $computerId, $expectedPly, $moveText, $now, $nowMs);
    }

    /**
     * When the computer's held reply is played: between a quarter and three
     * quarters of the days per move after the turn began (A86), but never
     * before now. With 1 day per move that is 6 to 18 hours.
     */
    private function replyTime(Game $game, int $now): int
    {
        $turnStartedAt = $game->lastMoveAt ?? $game->startedAt;
        $allowedSeconds = max(1, (int) $game->daysPerMove) * 86_400;
        $wait = random_int(intdiv($allowedSeconds, 4), intdiv($allowedSeconds * 3, 4));

        return max($now, $turnStartedAt + $wait);
    }

    /** When the computer's held reply in a game is played, or null when none waits. */
    public function heldReplyAt(int $gameId): ?int
    {
        return $this->heldMoves->find($gameId)['play_at'] ?? null;
    }

    /**
     * Plays the held replies whose time has come (ComputerMoveJob, A86).
     * A reply that no longer fits (the game ended, the move was refused) is
     * dropped; the player's page then decides a new one. Returns how many were played.
     */
    public function playDueReplies(int $now, int $limit): int
    {
        $played = 0;
        foreach ($this->heldMoves->due($now, $limit) as $held) {
            $this->heldMoves->remove($held['game_id']);
            $game = $this->gameRepository->find($held['game_id']);
            $computer = $game === null ? null : $this->computerSideOf($game);
            if ($game === null || $computer === null || !$game->isRunning() || $game->ply !== $held['ply']) {
                continue;
            }
            $computerId = $computer['colour'] === Position::WHITE ? $game->whiteId : $game->blackId;
            try {
                $this->games->move($game->id, $computerId, $held['ply'], $held['move_uci'], $now);
                $played++;
            } catch (PlayRefused) {
                // Not playable any more: dropped above.
            }
        }

        return $played;
    }
}
