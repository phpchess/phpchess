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
 * - K12: the time is passed in ($now, Unix seconds UTC, and $nowMs, Unix
 *        milliseconds, for the chess clock), never read here. Clocks are
 *        updated inside the move transaction (TimedClock does the sums).
 *
 * Timed play (C3-1, A46): a game with a time control has two clocks. The
 * flag is checked inside the lock on every move and every poll
 * (endIfFlagged), and by the clock job. A player may leave one premove
 * while the opponent thinks; it is played at once, at no time, inside the
 * opponent's move transaction. The players can change the pace of a game
 * (timed <-> correspondence) when both agree (G-23).
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
    /** A timed game against the computer: the player left the board and the computer ran out of time (A86). */
    public const string REASON_LEFT_BOARD = 'left_board';
    public const string REASON_ADJUDICATION_PREFIX = 'adjudicated_';

    // --- The admin's abort rule (A33) ---
    public const string ABORT_ALLOWED = 'allowed';
    public const string ABORT_COUNTS_AS_LOSS = 'loss';
    public const string ABORT_ASK_ADMIN = 'ask_admin';
    public const array ABORT_RULES = [self::ABORT_ALLOWED, self::ABORT_COUNTS_AS_LOSS, self::ABORT_ASK_ADMIN];

    /** A game can be aborted only before the first move (A33, changed in review: A39). */
    public const int ABORT_BEFORE_PLY = 1;

    // --- Timed play: admin settings and their defaults (G-18, A46) ---
    public const int DEFAULT_MAX_LIVE_GAMES = 30;
    public const int DEFAULT_MAX_LIVE_GAMES_PER_PLAYER = 1;

    /** A player counts as online for a pace change to timed when their board polled this recently (A38). */
    public const int ONLINE_SECONDS = 30;

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
     * Starts a game and returns its id: correspondence with days per move,
     * or timed when a time control ("5+3") is given (G-07, G-14). The start
     * position is checked by the Rules module (G-06); throws PlayRefused
     * when it can't be played (not a legal position, or already over), when
     * a student limited to their class would play someone outside it
     * (SCH-05), or when the time control or the live-game limits refuse it.
     */
    public function start(int $whiteId, int $blackId, int $daysPerMove, bool $isRated, string $startFen, int $now, ?string $timeControl = null, ?int $nowMs = null): int
    {
        if ($whiteId === $blackId) {
            throw new PlayRefused('play.not_against_yourself');
        }
        $this->checkClassRule($whiteId, $blackId);
        $position = self::checkedStartPosition($startFen);

        // --- A timed game (G-14) ---
        if ($timeControl !== null && $timeControl !== '') {
            $control = $this->checkedTimeControl($timeControl);
            $this->checkLiveLimits([$whiteId, $blackId]);
            $nowMs ??= $now * 1000;

            return $this->games->createTimed(
                $whiteId,
                $blackId,
                $control->speed(),
                $control->text(),
                $control->baseMs(),
                $control->incrementMs(),
                $isRated,
                $position->toFen(),
                $nowMs,
                TimedClock::latestFlagMs($control->baseMs(), $nowMs),
            );
        }

        // --- A correspondence game ---
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
    public function move(int $gameId, int $userId, int $expectedPly, string $moveText, int $now, ?int $nowMs = null): Game
    {
        $nowMs ??= $now * 1000;
        $result = $this->database->transaction(function () use ($gameId, $userId, $expectedPly, $moveText, $now, $nowMs): Game {
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

            // --- The flag (timed games): a move after the time ran out ends the game on time ---
            $timeUsedMs = 0;
            if ($game->isTimed()) {
                $timeUsedMs = TimedClock::timeUsed((int) $game->turnSavedMs, $game->turnDeliveredMs, $nowMs);
                if ($game->msLeftAtTurnStart($game->sideToMove()) - $timeUsedMs <= 0) {
                    $this->finishOnTime($game, $now);

                    return $this->games->find($game->id) ?? $game;
                }
            }

            // --- Checking the move (K1) ---
            $move = self::readMove($game->position(), $moveText);
            if ($move === null) {
                throw new PlayRefused('play.illegal_move');
            }

            // --- Playing it, then any premove the opponent left (G-17) ---
            $movedGame = $this->playLocked($game, $userId, $move, $timeUsedMs, $now, $nowMs);
            $this->playWaitingPremove($movedGame, $now, $nowMs);

            return $this->games->find($game->id) ?? $movedGame;
        });

        return $result;
    }

    /**
     * Plays one checked move in the locked game: stores it, runs the clocks,
     * then ends the game when the rules say it is over, or tells the next
     * player it is their move. Returns the game after the move, still locked.
     */
    private function playLocked(Game $game, int $userId, Move $move, int $timeUsedMs, int $now, int $nowMs): Game
    {
        $position = $game->position();
        $san = San::of($position, $move);
        $after = $position->play($move);
        $newPly = $game->ply + 1;
        $nextMoverId = $game->opponentOf($userId);
        $deadline = $game->daysPerMove === null
            ? null
            : Clocks::deadline($now, $game->daysPerMove, $this->vacationDaysOf($nextMoverId, $now));
        $this->games->addMove($game->id, $newPly, $move->toUci(), $san, $after->toFen(), $now, $deadline);

        // --- The clocks (timed games, K12) ---
        if ($game->isTimed()) {
            $moverColour = $game->sideToMove();
            $moverLeft = TimedClock::timeLeftAfterMove($game->msLeftAtTurnStart($moverColour), $timeUsedMs, (int) $game->incrementMs);
            $whiteLeft = $moverColour === Position::WHITE ? $moverLeft : $game->msLeftAtTurnStart(Position::WHITE);
            $blackLeft = $moverColour === Position::BLACK ? $moverLeft : $game->msLeftAtTurnStart(Position::BLACK);
            $nextLeft = $moverColour === Position::WHITE ? $blackLeft : $whiteLeft;
            $this->games->setClocks($game->id, $whiteLeft, $blackLeft, $nowMs, TimedClock::latestFlagMs($nextLeft, $nowMs));
        }

        // --- Is the game over by the rules? ---
        $ending = Rules::ending($after, $this->repetitionKeys($game->id, $game->startFen));
        $movedGame = $this->games->findForUpdate($game->id) ?? $game;
        if ($ending !== null) {
            $winnerResult = $ending === Rules::ENDING_CHECKMATE
                ? self::resultForWinner(Rules::winnerAfterCheckmate($after))
                : Game::RESULT_DRAW;
            $this->finish($movedGame, $winnerResult, $ending, $now);

            return $this->games->findForUpdate($game->id) ?? $movedGame;
        }
        $this->notices->tellAboutGame($movedGame, $nextMoverId, Notices::YOUR_MOVE, $userId, $now);

        return $movedGame;
    }

    // --- Premoves (G-17) ---

    /**
     * Plays the premove the player to move left, if there is one for this
     * turn, at no time used (A46). An illegal premove is simply dropped; the
     * player sees it gone and moves normally. A premove can answer a
     * premove, so this repeats while there is one.
     */
    private function playWaitingPremove(Game $game, int $now, int $nowMs): void
    {
        while ($game->isRunning() && $game->premoveUci !== null) {
            $premoverId = (int) $game->premoveBy;
            $isForThisTurn = $game->premovePly === $game->ply && $premoverId === $game->playerToMoveId();
            $premove = $isForThisTurn ? Rules::legalMoveFromUci($game->position(), $game->premoveUci) : null;
            $this->games->setPremove($game->id, null, null, null);
            if ($premove === null) {
                return;
            }
            $cleared = $this->games->findForUpdate($game->id) ?? $game;
            $game = $this->playLocked($cleared, $premoverId, $premove, 0, $now, $nowMs);
        }
    }

    /**
     * Leaves a premove while the opponent is to move (timed games only).
     * $expectedPly is the ply the player's board showed: the premove is
     * played right after the opponent's next move. A new premove replaces
     * the old one.
     */
    public function premove(int $gameId, int $userId, int $expectedPly, string $uci): void
    {
        $uci = strtolower(trim($uci));
        if (preg_match('/^[a-h][1-8][a-h][1-8][qrbn]?$/', $uci) !== 1) {
            throw new PlayRefused('play.illegal_move');
        }
        $this->database->transaction(function () use ($gameId, $userId, $expectedPly, $uci): void {
            $game = $this->lockedRunningPlayerGame($gameId, $userId);
            if (!$game->isTimed()) {
                throw new PlayRefused('play.premove_timed_only');
            }
            if ($game->ply !== $expectedPly || $game->playerToMoveId() === $userId) {
                throw new PlayRefused('play.board_out_of_date');
            }
            $this->games->setPremove($game->id, $userId, $uci, $game->ply + 1);
        });
    }

    /** Takes the player's premove back. */
    public function clearPremove(int $gameId, int $userId): void
    {
        $this->database->transaction(function () use ($gameId, $userId): void {
            $game = $this->lockedPlayerGame($gameId, $userId);
            if ($game->premoveBy === $userId) {
                $this->games->setPremove($game->id, null, null, null);
            }
        });
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
    public function endIfOverdue(int $gameId, int $now, ?int $nowMs = null): bool
    {
        $game = $this->games->find($gameId);
        if ($game !== null && $game->isTimed()) {
            return $this->endIfFlagged($gameId, $now, $nowMs ?? $now * 1000);
        }

        return $this->database->transaction(function () use ($gameId, $now): bool {
            $game = $this->games->findForUpdate($gameId);
            if ($game === null || !$game->isRunning() || $game->moveDeadlineAt === null || $game->daysPerMove === null) {
                return false;
            }
            $moverId = $game->playerToMoveId();
            $turnStartedAt = $game->lastMoveAt ?? $game->startedAt;
            $deadline = Clocks::deadline($turnStartedAt, $game->daysPerMove, $this->vacationDaysOf($moverId, $turnStartedAt));
            // A computer player moves when its opponent's page is open (C3-2):
            // it never loses on time; its deadline simply moves on.
            if ($deadline <= $now && $this->games->isComputerAccount($moverId)) {
                $deadline = Clocks::deadline($now, $game->daysPerMove, []);
            }
            if ($deadline > $now) {
                $this->games->setDeadline($game->id, $deadline);

                return false;
            }

            $this->finishOnTime($game, $now);

            return true;
        });
    }

    /**
     * The time left on the running clock of a timed game now, in ms (zero or
     * below when the flag has fallen); null for a correspondence game or a
     * finished one.
     */
    public static function runningClockLeft(Game $game, int $nowMs): ?int
    {
        if (!$game->isTimed() || !$game->isRunning()) {
            return null;
        }

        return TimedClock::timeLeftNow($game->msLeftAtTurnStart($game->sideToMove()), (int) $game->turnSavedMs, $game->turnDeliveredMs, $nowMs);
    }

    /**
     * Ends a timed game on time when the running clock has reached zero
     * (A46). Checked inside the lock (K2, K3), so a move arriving at the same
     * moment is either before the flag or after it, never both. Called by
     * every poll of the board and by the clock job. Returns true when the
     * game ended.
     */
    public function endIfFlagged(int $gameId, int $now, int $nowMs): bool
    {
        return $this->database->transaction(function () use ($gameId, $now, $nowMs): bool {
            $game = $this->games->findForUpdate($gameId);
            if ($game === null) {
                return false;
            }
            $left = self::runningClockLeft($game, $nowMs);
            if ($left === null || $left > 0) {
                return false;
            }
            $this->finishOnTime($game, $now);

            return true;
        });
    }

    /**
     * Ends the locked game on time for the player to move: the opponent wins,
     * or it is a draw when the opponent could never mate (A33, A46). In a
     * timed game the fallen clock is set to zero first.
     */
    private function finishOnTime(Game $lockedGame, int $now): void
    {
        if ($lockedGame->isTimed()) {
            $moverIsWhite = $lockedGame->sideToMove() === Position::WHITE;
            $this->games->setClocks(
                $lockedGame->id,
                $moverIsWhite ? 0 : $lockedGame->msLeftAtTurnStart(Position::WHITE),
                $moverIsWhite ? $lockedGame->msLeftAtTurnStart(Position::BLACK) : 0,
                (int) $lockedGame->turnSavedMs,
                (int) $lockedGame->turnSavedMs,
            );
        }
        $winnerColour = Position::opposite($lockedGame->sideToMove());

        // A timed game against the computer (A86): the computer can only move
        // while its opponent has the board open. When its time runs out because
        // the player left the board, the player loses, as if they had resigned.
        $moverId = $lockedGame->playerToMoveId();
        if ($lockedGame->isTimed() && $this->games->isComputerAccount($moverId)) {
            $humanId = $moverId === $lockedGame->whiteId ? $lockedGame->blackId : $lockedGame->whiteId;
            $seenAt = $lockedGame->seenAtBy($humanId);
            if ($seenAt === null || $now - $seenAt > self::ONLINE_SECONDS) {
                $this->finish($lockedGame, self::resultForWinner($lockedGame->sideToMove()), self::REASON_LEFT_BOARD, $now);

                return;
            }
        }

        if (Rules::canEverMate($lockedGame->position(), $winnerColour)) {
            $this->finish($lockedGame, self::resultForWinner($winnerColour), self::REASON_TIMEOUT, $now);
        } else {
            $this->finish($lockedGame, Game::RESULT_DRAW, self::REASON_TIMEOUT_DRAW, $now);
        }
    }

    // --- Changing pace (G-23, A46) ---

    /**
     * Proposes a new pace: a time control ("10+5") or days per move ("3d").
     * When the opponent already proposed the same, it is agreed at once.
     */
    public function proposePace(int $gameId, int $userId, string $offer, int $now, int $nowMs): void
    {
        $offer = $this->checkedPaceOffer($offer);
        $this->database->transaction(function () use ($gameId, $userId, $offer, $now, $nowMs): void {
            $game = $this->lockedRunningPlayerGame($gameId, $userId);
            if ($offer === self::paceOf($game)) {
                throw new PlayRefused('play.pace_unchanged');
            }
            if ($game->paceOfferedBy === $game->opponentOf($userId) && $game->paceOffer === $offer) {
                $this->changePace($game, $offer, $now, $nowMs);

                return;
            }
            $this->games->setPaceOffer($game->id, $userId, $offer);
        });
    }

    /** Accepts the opponent's pace proposal: the game changes pace now. */
    public function acceptPace(int $gameId, int $userId, int $now, int $nowMs): void
    {
        $this->database->transaction(function () use ($gameId, $userId, $now, $nowMs): void {
            $game = $this->lockedRunningPlayerGame($gameId, $userId);
            if ($game->paceOfferedBy !== $game->opponentOf($userId) || $game->paceOffer === null) {
                throw new PlayRefused('play.no_pace_offer');
            }
            $this->changePace($game, $game->paceOffer, $now, $nowMs);
        });
    }

    /** Declines the opponent's proposal, or takes back one's own. */
    public function declinePace(int $gameId, int $userId): void
    {
        $this->database->transaction(function () use ($gameId, $userId): void {
            $game = $this->lockedRunningPlayerGame($gameId, $userId);
            if ($game->paceOfferedBy !== null) {
                $this->games->setPaceOffer($game->id, null, null);
            }
        });
    }

    /**
     * Changes the pace of the locked game. To timed: both players must be
     * online and within the live-game limits; both clocks restart at the
     * base time. To correspondence: the days per move start now. The rating
     * category (speed) stays the one the game started with (A46).
     */
    private function changePace(Game $game, string $offer, int $now, int $nowMs): void
    {
        $control = TimeControl::fromText($offer);
        if ($control !== null) {
            foreach ([$game->whiteId, $game->blackId] as $playerId) {
                $seenAt = $game->seenAtBy($playerId);
                if ($seenAt === null || $now - $seenAt > self::ONLINE_SECONDS) {
                    throw new PlayRefused('play.pace_needs_both_online');
                }
            }
            if (!$game->isTimed()) {
                $this->checkLiveLimits([$game->whiteId, $game->blackId]);
            }
            $this->games->switchToTimed($game->id, $control->text(), $control->baseMs(), $control->incrementMs(), $nowMs, TimedClock::latestFlagMs($control->baseMs(), $nowMs));

            return;
        }
        $days = (int) rtrim($offer, Game::PACE_DAYS_SUFFIX);
        $deadline = Clocks::deadline($now, $days, $this->vacationDaysOf($game->playerToMoveId(), $now));
        $this->games->switchToCorrespondence($game->id, $days, $deadline);
    }

    /** The pace of a game as a pace offer writes it: "10+5" or "3d". */
    public static function paceOf(Game $game): string
    {
        return $game->timeControl ?? ($game->daysPerMove ?? 0) . Game::PACE_DAYS_SUFFIX;
    }

    private function checkedPaceOffer(string $offer): string
    {
        $offer = trim($offer);
        if (preg_match('/^(\d{1,2})d$/', $offer, $match) === 1) {
            $days = (int) $match[1];
            if ($days < 1 || $days > $this->maximumDays()) {
                throw new PlayRefused('challenge.days_out_of_range', ['max' => $this->maximumDays()]);
            }

            return $days . Game::PACE_DAYS_SUFFIX;
        }

        return $this->checkedTimeControl($offer)->text();
    }

    // --- Timed play: the admin's settings (A12, G-18, A46) ---

    /**
     * The time controls players may choose now, fastest first (the admin's floor, A12).
     *
     * @return list<string>
     */
    public function allowedTimeControls(): array
    {
        return TimeControl::allowed($this->settings->getString('play.min_time_control', TimeControl::DEFAULT_FLOOR));
    }

    /** The most timed games running at once on the site (G-18). */
    public function maxLiveGames(): int
    {
        return max(1, $this->settings->getInt('play.max_live_games', self::DEFAULT_MAX_LIVE_GAMES));
    }

    /** The most timed games one player may have running at once (G-18; raise it for simuls, B19). */
    public function maxLiveGamesPerPlayer(): int
    {
        return max(1, $this->settings->getInt('play.max_live_games_per_player', self::DEFAULT_MAX_LIVE_GAMES_PER_PLAYER));
    }

    /** The admin's highest days per move (A28). */
    public function maximumDays(): int
    {
        return Clocks::limitDays($this->settings->getInt('play.max_days_per_move', Clocks::DEFAULT_MAX_DAYS), Clocks::HIGHEST_ALLOWED_DAYS);
    }

    /** A time control players may choose now, or PlayRefused. */
    public function checkedTimeControl(string $text): TimeControl
    {
        $control = TimeControl::fromText(trim($text));
        if ($control === null || !in_array($control->text(), $this->allowedTimeControls(), true)) {
            throw new PlayRefused('play.time_control_not_allowed');
        }

        return $control;
    }

    /**
     * Refuses a new timed game when the site or one of the players is at the
     * admin's limit (G-18).
     *
     * @param list<int> $playerIds
     */
    public function checkLiveLimits(array $playerIds): void
    {
        if ($this->games->countRunningTimed() >= $this->maxLiveGames()) {
            throw new PlayRefused('play.live_games_full', ['max' => $this->maxLiveGames()]);
        }
        foreach ($playerIds as $playerId) {
            if ($this->games->countRunningTimedOf($playerId) >= $this->maxLiveGamesPerPlayer()) {
                throw new PlayRefused('play.live_game_limit', ['max' => $this->maxLiveGamesPerPlayer()]);
            }
        }
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
                $newGameId = $this->start($game->blackId, $game->whiteId, $game->daysPerMove ?? 3, $game->isRated, $game->startFen, $now, $game->timeControl);
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
