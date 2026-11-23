<?php

declare(strict_types=1);

namespace PhpChess\Domain\Play;

use PhpChess\Domain\Chess\Move;
use PhpChess\Domain\Chess\MoveGenerator;
use PhpChess\Domain\Chess\Position;
use PhpChess\Domain\Chess\Rules;
use PhpChess\Domain\Chess\San;
use PhpChess\Domain\Notify\Notices;
use PhpChess\Domain\Rating\Ratings;
use PhpChess\Domain\School\ClassRules;
use PhpChess\Infra\Database;
use PhpChess\Infra\Play\GameRepository;
use PhpChess\Infra\Play\VacationRepository;
use PhpChess\Infra\System\SettingsRepository;

/**
 * The games service: starting a game, the one move function and the one
 * game-end function (G-02, G-03, G-07, G-09, G-22, ADM-04).
 *
 * Contracts kept here:
 * - K1: every move is checked by the Rules module.
 * - K2: move() runs in one transaction with the game row locked
 *       (SELECT ... FOR UPDATE). The caller sends the ply it saw; a repeated
 *       request for a move already played is answered as done and changes
 *       nothing, any other stale request is refused.
 * - K3: end() is the only way a game ends. Inside the lock it checks the
 *       game is still running, writes result and reason, then calls Ratings
 *       and Notices. Moves (mate, stalemate, draws by rule), resignation,
 *       draw agreement, abort, timeout and adjudication all come here.
 * - K12: the time is passed in ($now, Unix seconds UTC), never read here.
 *
 * Called by the GameController, the API, Challenges (start), the clock job
 * (timeouts) and the admin's games page (adjudication).
 */
final class Games
{
    // --- Reasons a game ends, besides the Rules endings ---
    public const string REASON_RESIGNATION = 'resignation';
    public const string REASON_DRAW_AGREED = 'draw_agreed';
    public const string REASON_ABORTED = 'aborted';
    public const string REASON_ABORT_COUNTED_AS_LOSS = 'abort_counted_as_loss';
    public const string REASON_TIMEOUT = 'timeout';
    public const string REASON_TIMEOUT_DRAW = 'timeout_insufficient_material';
    public const string REASON_ADJUDICATION_PREFIX = 'adjudicated_';

    // --- The admin's abort rule (A33) ---
    public const string ABORT_ALLOWED = 'allowed';
    public const string ABORT_COUNTS_AS_LOSS = 'loss';
    public const string ABORT_ASK_ADMIN = 'ask_admin';
    public const array ABORT_RULES = [self::ABORT_ALLOWED, self::ABORT_COUNTS_AS_LOSS, self::ABORT_ASK_ADMIN];

    /** A game can be aborted only before the first move (A33, changed in review: A39). */
    public const int ABORT_BEFORE_PLY = 1;

    public function __construct(
        private readonly Database $database,
        private readonly GameRepository $games,
        private readonly VacationRepository $vacations,
        private readonly Ratings $ratings,
        private readonly Notices $notices,
        private readonly SettingsRepository $settings,
        private readonly ?ClassRules $classRules = null,
    ) {
    }

    // --- Starting a game ---

    /**
     * Starts a correspondence game and returns its id. The start position is
     * checked by the Rules module (G-06); throws PlayRefused when it can't be
     * played (not a legal position, or already over), or when a student
     * limited to their class would play someone outside it (SCH-05).
     */
    public function start(int $whiteId, int $blackId, int $daysPerMove, bool $isRated, string $startFen, int $now): int
    {
        if ($whiteId === $blackId) {
            throw new PlayRefused('play.not_against_yourself');
        }
        $this->checkClassRule($whiteId, $blackId);
        $position = self::checkedStartPosition($startFen);
        $moverId = $position->sideToMove === Position::WHITE ? $whiteId : $blackId;
        $deadline = Clocks::deadline($now, $daysPerMove, $this->vacationDaysOf($moverId, $now));

        return $this->games->create($whiteId, $blackId, Game::SPEED_CORRESPONDENCE, $daysPerMove, $isRated, $position->toFen(), $now, $deadline);
    }

    /** Refuses a game between two users the school's class rule keeps apart (SCH-05). */
    public function checkClassRule(int $userId, int $otherUserId): void
    {
        if ($this->classRules !== null && !$this->classRules->mayContact($userId, $otherUserId)) {
            throw new PlayRefused('school.outside_class');
        }
    }

    /** Reads a start position (FEN) and refuses one that is broken or where the game would already be over (G-06). */
    public static function checkedStartPosition(string $fen): Position
    {
        try {
            $position = Position::fromFen($fen === '' ? Position::START_FEN : $fen);
        } catch (\PhpChess\Domain\Chess\InvalidPosition $problem) {
            throw new PlayRefused('play.fen_invalid', ['problem' => $problem->getMessage()]);
        }
        if (Rules::ending($position, [$position->repetitionKey()]) !== null) {
            throw new PlayRefused('play.fen_game_over');
        }

        return $position;
    }

    // --- The move function (K2) ---

    /**
     * Plays a move for a player. $expectedPly is the number of moves the
     * player's board showed; $moveText is UCI ("e2e4") or SAN ("Nf3").
     * Returns the game after the move. Throws PlayRefused when it is not
     * the player's game or turn, the game is over, the board was out of date
     * or the move is illegal.
     */
    public function move(int $gameId, int $userId, int $expectedPly, string $moveText, int $now): Game
    {
        $result = $this->database->transaction(function () use ($gameId, $userId, $expectedPly, $moveText, $now): Game {
            $game = $this->lockedPlayerGame($gameId, $userId);

            // A repeated request (double tap, a retry after a lost answer):
            // the move is already there, so answer as done and change nothing.
            if ($game->ply === $expectedPly + 1 && $this->lastMoveMatches($game, $moveText)) {
                return $game;
            }
            if (!$game->isRunning()) {
                throw new PlayRefused('play.game_over');
            }
            if ($game->ply !== $expectedPly) {
                throw new PlayRefused('play.board_out_of_date');
            }
            if ($game->playerToMoveId() !== $userId) {
                throw new PlayRefused('play.not_your_turn');
            }

            // --- Checking the move (K1) ---
            $position = $game->position();
            $move = self::readMove($position, $moveText);
            if ($move === null) {
                throw new PlayRefused('play.illegal_move');
            }

            // --- Playing it ---
            $san = San::of($position, $move);
            $after = $position->play($move);
            $newPly = $game->ply + 1;
            $nextMoverId = $game->opponentOf($userId);
            $deadline = $game->daysPerMove === null
                ? null
                : Clocks::deadline($now, $game->daysPerMove, $this->vacationDaysOf($nextMoverId, $now));
            $this->games->addMove($game->id, $newPly, $move->toUci(), $san, $after->toFen(), $now, $deadline);

            // --- Is the game over by the rules? ---
            $ending = Rules::ending($after, $this->repetitionKeys($game->id, $game->startFen));
            $movedGame = $this->games->findForUpdate($game->id) ?? $game;
            if ($ending !== null) {
                $winnerResult = $ending === Rules::ENDING_CHECKMATE
                    ? self::resultForWinner(Rules::winnerAfterCheckmate($after))
                    : Game::RESULT_DRAW;
                $this->finish($movedGame, $winnerResult, $ending, $now);
            } else {
                $this->notices->tellAboutGame($movedGame, $nextMoverId, Notices::YOUR_MOVE, $userId, $now);
            }

            return $this->games->find($game->id) ?? $movedGame;
        });

        return $result;
    }

    /** The legal move for UCI or SAN text, or null. SAN is compared without its "+" or "#". */
    public static function readMove(Position $position, string $moveText): ?Move
    {
        $moveText = trim($moveText);
        $move = Rules::legalMoveFromUci($position, strtolower($moveText));
        if ($move !== null) {
            return $move;
        }
        $wanted = rtrim(str_replace('0', 'O', $moveText), '+#!?');
        foreach (MoveGenerator::legalMoves($position) as $candidate) {
            if (rtrim(San::of($position, $candidate), '+#') === $wanted) {
                return $candidate;
            }
        }

        return null;
    }

    // --- The game-end function (K3) ---

    /**
     * Ends a running game with a result and a reason. Returns true when this
     * call ended it, false when it had already ended (nothing changes then).
     */
    public function end(int $gameId, string $result, string $reason, int $now): bool
    {
        return $this->database->transaction(function () use ($gameId, $result, $reason, $now): bool {
            $game = $this->games->findForUpdate($gameId);
            if ($game === null || !$game->isRunning()) {
                return false;
            }
            $this->finish($game, $result, $reason, $now);

            return true;
        });
    }

    /**
     * The one place that writes a result (K3). The game row must be locked
     * and running; then the result is written, the ratings applied once
     * (K4) and both players told.
     */
    private function finish(Game $lockedGame, string $result, string $reason, int $now): void
    {
        $this->games->writeResult($lockedGame->id, $result, $reason, $now);
        $this->ratings->applyGameResult($lockedGame, $result, $now);
        foreach ([$lockedGame->whiteId, $lockedGame->blackId] as $playerId) {
            $this->notices->tellAboutGame($lockedGame, $playerId, Notices::GAME_ENDED, null, $now);
        }
    }

    // --- Resignation, draw offers and abort (G-09) ---

    /** The player gives up; the opponent wins. */
    public function resign(int $gameId, int $userId, int $now): void
    {
        $this->database->transaction(function () use ($gameId, $userId, $now): void {
            $game = $this->lockedRunningPlayerGame($gameId, $userId);
            $winner = Position::opposite((string) $game->colourOf($userId));
            $this->finish($game, self::resultForWinner($winner), self::REASON_RESIGNATION, $now);
        });
    }

    /** Offers a draw. When the opponent already offered one, the game is drawn at once. */
    public function offerDraw(int $gameId, int $userId, int $now): void
    {
        $this->database->transaction(function () use ($gameId, $userId, $now): void {
            $game = $this->lockedRunningPlayerGame($gameId, $userId);
            if ($game->drawOfferedBy === $game->opponentOf($userId)) {
                $this->finish($game, Game::RESULT_DRAW, self::REASON_DRAW_AGREED, $now);

                return;
            }
            $this->games->setDrawOffer($game->id, $userId);
            $this->notices->tellAboutGame($game, $game->opponentOf($userId), Notices::DRAW_OFFERED, $userId, $now);
        });
    }

    /** Accepts the opponent's draw offer. */
    public function acceptDraw(int $gameId, int $userId, int $now): void
    {
        $this->database->transaction(function () use ($gameId, $userId, $now): void {
            $game = $this->lockedRunningPlayerGame($gameId, $userId);
            if ($game->drawOfferedBy !== $game->opponentOf($userId)) {
                throw new PlayRefused('play.no_draw_offer');
            }
            $this->finish($game, Game::RESULT_DRAW, self::REASON_DRAW_AGREED, $now);
        });
    }

    /** Declines the opponent's draw offer. */
    public function declineDraw(int $gameId, int $userId): void
    {
        $this->database->transaction(function () use ($gameId, $userId): void {
            $game = $this->lockedRunningPlayerGame($gameId, $userId);
            if ($game->drawOfferedBy === $game->opponentOf($userId)) {
                $this->games->setDrawOffer($game->id, null);
            }
        });
    }

    /** True when the game is still early enough to abort (A33). */
    public static function canStillAbort(Game $game): bool
    {
        return $game->isRunning() && $game->ply < self::ABORT_BEFORE_PLY;
    }

    /** The admin's abort rule (A33). */
    public function abortRule(): string
    {
        $rule = $this->settings->getString('play.abort_rule', self::ABORT_ALLOWED);

        return in_array($rule, self::ABORT_RULES, true) ? $rule : self::ABORT_ALLOWED;
    }

    /**
     * Aborts a game before each side has moved, as the admin's rule says (A33):
     * - allowed: the game ends "aborted", never rated, but kept on record;
     * - loss:    the player who aborts loses the game;
     * - ask_admin: refused here; the player asks an admin (requestAbort).
     */
    public function abort(int $gameId, int $userId, int $now): void
    {
        $rule = $this->abortRule();
        $this->database->transaction(function () use ($gameId, $userId, $now, $rule): void {
            $game = $this->lockedRunningPlayerGame($gameId, $userId);
            if (!self::canStillAbort($game)) {
                throw new PlayRefused('play.too_late_to_abort');
            }
            if ($rule === self::ABORT_ASK_ADMIN) {
                throw new PlayRefused('play.abort_ask_admin');
            }
            if ($rule === self::ABORT_COUNTS_AS_LOSS) {
                $winner = Position::opposite((string) $game->colourOf($userId));
                $this->finish($game, self::resultForWinner($winner), self::REASON_ABORT_COUNTED_AS_LOSS, $now);

                return;
            }
            $this->finish($game, Game::RESULT_ABORTED, self::REASON_ABORTED, $now);
        });
    }

    // --- Timeouts (the clock job) ---

    /**
     * Ends the game on time when the player to move has passed the deadline
     * (A33): the opponent wins, or it is a draw when the opponent could
     * never mate. Vacation days taken after the deadline was set push it
     * later instead (A29). Returns true when the game ended.
     */
    public function endIfOverdue(int $gameId, int $now): bool
    {
        return $this->database->transaction(function () use ($gameId, $now): bool {
            $game = $this->games->findForUpdate($gameId);
            if ($game === null || !$game->isRunning() || $game->moveDeadlineAt === null || $game->daysPerMove === null) {
                return false;
            }
            $moverId = $game->playerToMoveId();
            $turnStartedAt = $game->lastMoveAt ?? $game->startedAt;
            $deadline = Clocks::deadline($turnStartedAt, $game->daysPerMove, $this->vacationDaysOf($moverId, $turnStartedAt));
            if ($deadline > $now) {
                $this->games->setDeadline($game->id, $deadline);

                return false;
            }

            $winnerColour = Position::opposite($game->sideToMove());
            if (Rules::canEverMate($game->position(), $winnerColour)) {
                $this->finish($game, self::resultForWinner($winnerColour), self::REASON_TIMEOUT, $now);
            } else {
                $this->finish($game, Game::RESULT_DRAW, self::REASON_TIMEOUT_DRAW, $now);
            }

            return true;
        });
    }

    /** Recomputes the deadline of every running correspondence game of a player (after a vacation change, A29). */
    public function recomputeDeadlinesOf(int $userId, int $now): void
    {
        foreach ($this->games->runningCorrespondenceGamesOf($userId) as $game) {
            if ($game->playerToMoveId() !== $userId || $game->daysPerMove === null) {
                continue;
            }
            $turnStartedAt = $game->lastMoveAt ?? $game->startedAt;
            $this->games->setDeadline($game->id, Clocks::deadline($turnStartedAt, $game->daysPerMove, $this->vacationDaysOf($userId, $turnStartedAt)));
        }
    }

    // --- Adjudication (ADM-04, A37) ---

    /**
     * An admin sets the result of a running game, with a reason key from the
     * admin's list. Goes through end() like every other ending (K3).
     */
    public function adjudicate(int $gameId, string $result, string $reasonKey, int $now): bool
    {
        if (!in_array($result, [Game::RESULT_WHITE_WON, Game::RESULT_BLACK_WON, Game::RESULT_DRAW, Game::RESULT_ABORTED], true)) {
            throw new PlayRefused('play.result_invalid');
        }

        return $this->end($gameId, $result, self::REASON_ADJUDICATION_PREFIX . $reasonKey, $now);
    }

    // --- Rematch (G-22) ---

    /**
     * Offers a rematch of a finished game, or accepts the opponent's offer:
     * then a new game starts with the colours swapped, the same days per
     * move and rated setting, and its id is returned. Returns null while the
     * offer waits for the opponent.
     */
    public function rematch(int $gameId, int $userId, int $now): ?int
    {
        return $this->database->transaction(function () use ($gameId, $userId, $now): ?int {
            $game = $this->lockedPlayerGame($gameId, $userId);
            if ($game->isRunning()) {
                throw new PlayRefused('play.game_not_over');
            }
            if ($game->rematchGameId !== null) {
                return $game->rematchGameId;
            }
            if ($game->rematchOfferedBy === $game->opponentOf($userId)) {
                $newGameId = $this->start($game->blackId, $game->whiteId, $game->daysPerMove ?? 3, $game->isRated, $game->startFen, $now);
                $this->games->setRematchGame($game->id, $newGameId);
                $newGame = $this->games->find($newGameId);
                if ($newGame !== null) {
                    $this->notices->tellAboutGame($newGame, $game->opponentOf($userId), Notices::YOUR_MOVE, $userId, $now);
                }

                return $newGameId;
            }
            $this->games->setRematchOffer($game->id, $userId);
            $this->notices->tellAboutGame($game, $game->opponentOf($userId), Notices::REMATCH_OFFERED, $userId, $now);

            return null;
        });
    }

    // --- Presence on the game page (G-10) ---

    /** Notes that a player has the board open now, so their opponent's moves arrive live. */
    public function markSeen(Game $game, int $userId, int $now): void
    {
        $colour = $game->colourOf($userId);
        if ($colour !== null) {
            $this->games->markSeen($game->id, $colour, $now);
        }
    }

    // --- Helpers ---

    /** The game, locked, for one of its players; refuses everyone else (K14, S24). */
    private function lockedPlayerGame(int $gameId, int $userId): Game
    {
        $game = $this->games->findForUpdate($gameId);
        if ($game === null || !$game->isPlayer($userId)) {
            throw new PlayRefused('play.not_your_game');
        }

        return $game;
    }

    private function lockedRunningPlayerGame(int $gameId, int $userId): Game
    {
        $game = $this->lockedPlayerGame($gameId, $userId);
        if (!$game->isRunning()) {
            throw new PlayRefused('play.game_over');
        }

        return $game;
    }

    /** True when the game's last move is this move text (UCI or SAN). */
    private function lastMoveMatches(Game $game, string $moveText): bool
    {
        $moves = $this->games->moves($game->id, $game->ply - 1);
        if ($moves === []) {
            return false;
        }
        $last = $moves[0];
        $text = trim($moveText);

        return strtolower($text) === $last['uci'] || rtrim($text, '+#') === rtrim($last['san'], '+#');
    }

    /**
     * The repetition keys of every position of the game, the start included.
     *
     * @return list<string>
     */
    private function repetitionKeys(int $gameId, string $startFen): array
    {
        $keys = [Position::fromFen($startFen)->repetitionKey()];
        foreach ($this->games->moves($gameId) as $move) {
            $keys[] = Position::fromFen($move['fen_after'])->repetitionKey();
        }

        return $keys;
    }

    /**
     * The player's vacation days from the given time on.
     *
     * @return list<string>
     */
    private function vacationDaysOf(int $userId, int $from): array
    {
        return $this->vacations->daysFrom($userId, gmdate('Y-m-d', $from));
    }

    private static function resultForWinner(string $colour): string
    {
        return $colour === Position::WHITE ? Game::RESULT_WHITE_WON : Game::RESULT_BLACK_WON;
    }
}
