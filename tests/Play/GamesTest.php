<?php

declare(strict_types=1);

namespace PhpChess\Tests\Play;

use PhpChess\Domain\Chess\Position;
use PhpChess\Domain\Play\Game;
use PhpChess\Domain\Play\Games;
use PhpChess\Domain\Play\PlayRefused;
use PhpChess\Infra\Jobs\GameClockJob;
use PhpChess\Kernel\PlayParts;
use PhpChess\Tests\Support\InstalledSite;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests the move function and the game-end function (G-02, G-03, G-09,
 * K2, K3, K4, R-05, A33): every way a game ends goes through end() once,
 * a repeated or stale move is harmless, ratings are applied once, and
 * nobody can act in someone else's game (S24).
 */
final class GamesTest extends TestCase
{
    private const int NOW = 1_800_000_000;

    private InstalledSite $site;
    private PlayParts $play;
    private int $whiteId;
    private int $blackId;

    protected function setUp(): void
    {
        $this->site = new InstalledSite($this);
        $this->play = new PlayParts($this->site->database, $this->site->settings());
        $this->whiteId = $this->site->createUser('whiteplayer');
        $this->blackId = $this->site->createUser('blackplayer');
    }

    protected function tearDown(): void
    {
        if (isset($this->site)) {
            $this->site->remove();
        }
    }

    // --- Every ending goes through end() exactly once ---

    /**
     * @return array<string, array{string, list<string>, string, string}>
     */
    public static function endingsByMoves(): array
    {
        return [
            'checkmate' => [Position::START_FEN, ['f2f3', 'e7e5', 'g2g4', 'd8h4'], Game::RESULT_BLACK_WON, 'checkmate'],
            'stalemate' => ['7k/8/6K1/8/8/8/8/5Q2 w - - 0 1', ['f1f7'], Game::RESULT_DRAW, 'stalemate'],
            'insufficient material' => ['8/8/4k3/8/8/3K4/3r4/8 w - - 0 1', ['d3d2'], Game::RESULT_DRAW, 'insufficient_material'],
            'threefold repetition' => [Position::START_FEN, ['g1f3', 'g8f6', 'f3g1', 'f6g8', 'g1f3', 'g8f6', 'f3g1', 'f6g8'], Game::RESULT_DRAW, 'threefold_repetition'],
            'fifty moves' => ['8/8/4k3/8/8/R2K4/8/8 w - - 99 80', ['a3a4'], Game::RESULT_DRAW, 'fifty_moves'],
        ];
    }

    /**
     * @param list<string> $moves
     */
    #[DataProvider('endingsByMoves')]
    public function testAnEndingOnTheBoardEndsTheGameOnce(string $fen, array $moves, string $result, string $reason): void
    {
        $gameId = $this->play->games()->start($this->whiteId, $this->blackId, 3, true, $fen, self::NOW);
        $this->playMoves($gameId, $moves);

        $this->assertEndedOnce($gameId, $result, $reason);
    }

    public function testResignationEndsTheGameOnce(): void
    {
        $gameId = $this->startGame();
        $this->play->games()->resign($gameId, $this->whiteId, self::NOW);

        $this->assertEndedOnce($gameId, Game::RESULT_BLACK_WON, Games::REASON_RESIGNATION);
    }

    public function testAnAgreedDrawEndsTheGameOnce(): void
    {
        $gameId = $this->startGame();
        $this->play->games()->offerDraw($gameId, $this->whiteId, self::NOW);
        $this->play->games()->acceptDraw($gameId, $this->blackId, self::NOW);

        $this->assertEndedOnce($gameId, Game::RESULT_DRAW, Games::REASON_DRAW_AGREED);
    }

    public function testAMoveDeclinesADrawOffer(): void
    {
        $gameId = $this->startGame();
        $this->play->games()->offerDraw($gameId, $this->blackId, self::NOW);
        $this->play->games()->move($gameId, $this->whiteId, 0, 'e2e4', self::NOW);

        $this->expectException(PlayRefused::class);
        $this->play->games()->acceptDraw($gameId, $this->whiteId, self::NOW);
    }

    public function testAnAbortIsRecordedAndNeverRated(): void
    {
        $gameId = $this->startGame();
        $this->play->games()->abort($gameId, $this->whiteId, self::NOW);

        $this->assertEndedOnce($gameId, Game::RESULT_ABORTED, Games::REASON_ABORTED, false);
    }

    public function testAnAbortCanCountAsALoss(): void
    {
        $this->site->settings()->set('play.abort_rule', Games::ABORT_COUNTS_AS_LOSS);
        $gameId = $this->startGame();
        $this->play->games()->abort($gameId, $this->blackId, self::NOW);

        $this->assertEndedOnce($gameId, Game::RESULT_WHITE_WON, Games::REASON_ABORT_COUNTED_AS_LOSS);
    }

    public function testAbortCanBeLeftToTheAdmin(): void
    {
        $this->site->settings()->set('play.abort_rule', Games::ABORT_ASK_ADMIN);
        $gameId = $this->startGame();

        $this->expectException(PlayRefused::class);
        $this->play->games()->abort($gameId, $this->whiteId, self::NOW);
    }

    public function testAbortIsRefusedAfterTheFirstMove(): void
    {
        $gameId = $this->startGame();
        $this->playMoves($gameId, ['e2e4']);

        $this->expectException(PlayRefused::class);
        $this->play->games()->abort($gameId, $this->blackId, self::NOW);
    }

    public function testATimeoutIsWonByTheOpponent(): void
    {
        $gameId = $this->startGame();
        $this->runClockJob(self::NOW + 4 * 86400);

        $this->assertEndedOnce($gameId, Game::RESULT_BLACK_WON, Games::REASON_TIMEOUT);
    }

    public function testATimeoutIsADrawWhenTheOpponentCanNotMate(): void
    {
        $gameId = $this->play->games()->start($this->whiteId, $this->blackId, 3, true, '8/8/4k3/8/8/3K4/8/7r b - - 0 1', self::NOW);
        $this->runClockJob(self::NOW + 4 * 86400);

        $this->assertEndedOnce($gameId, Game::RESULT_DRAW, Games::REASON_TIMEOUT_DRAW);
    }

    public function testVacationPausesTheClock(): void
    {
        $gameId = $this->startGame();
        $this->play->vacations()->take($this->whiteId, gmdate('Y-m-d', self::NOW + 86400), 5, self::NOW);
        $this->runClockJob(self::NOW + 4 * 86400);

        self::assertTrue($this->game($gameId)->isRunning());
        $this->runClockJob(self::NOW + 9 * 86400);
        self::assertFalse($this->game($gameId)->isRunning());
    }

    public function testAdjudicationEndsTheGameOnce(): void
    {
        $gameId = $this->startGame();
        $this->play->games()->adjudicate($gameId, Game::RESULT_WHITE_WON, 'fair_play', self::NOW);

        $this->assertEndedOnce($gameId, Game::RESULT_WHITE_WON, Games::REASON_ADJUDICATION_PREFIX . 'fair_play');
    }

    // --- The move function (K2) ---

    public function testADoubleSubmitStoresTheMoveOnce(): void
    {
        $gameId = $this->startGame();
        $this->play->games()->move($gameId, $this->whiteId, 0, 'e2e4', self::NOW);
        $again = $this->play->games()->move($gameId, $this->whiteId, 0, 'e2e4', self::NOW);

        self::assertSame(1, $again->ply);
        self::assertCount(1, $this->play->gameRepository()->moves($gameId));
    }

    public function testAMoveFromAnOutOfDateBoardIsRefused(): void
    {
        $gameId = $this->startGame();
        $this->play->games()->move($gameId, $this->whiteId, 0, 'e2e4', self::NOW);
        $this->play->games()->move($gameId, $this->blackId, 1, 'e7e5', self::NOW);

        // A second tab still showing ply 1 sends another black move.
        $this->expectException(PlayRefused::class);
        $this->play->games()->move($gameId, $this->blackId, 1, 'd7d5', self::NOW);
    }

    public function testIllegalMovesAndWrongTurnsAreRefused(): void
    {
        $gameId = $this->startGame();
        foreach ([[$this->whiteId, 'e2e5'], [$this->blackId, 'e7e5'], [$this->whiteId, 'nonsense']] as [$userId, $move]) {
            try {
                $this->play->games()->move($gameId, $userId, 0, $move, self::NOW);
                self::fail("The move {$move} should be refused.");
            } catch (PlayRefused) {
                self::assertSame(0, $this->game($gameId)->ply);
            }
        }
    }

    public function testSanIsAcceptedAsWellAsUci(): void
    {
        $gameId = $this->startGame();
        $this->play->games()->move($gameId, $this->whiteId, 0, 'Nf3', self::NOW);

        self::assertSame('g1f3', $this->play->gameRepository()->moves($gameId)[0]['uci']);
    }

    // --- The outsider tests (S24, K14) ---

    public function testAnOutsiderCanNotResignOrMoveInSomeoneElsesGame(): void
    {
        $outsiderId = $this->site->createUser('outsider');
        $gameId = $this->startGame();

        foreach ([
            fn () => $this->play->games()->resign($gameId, $outsiderId, self::NOW),
            fn () => $this->play->games()->move($gameId, $outsiderId, 0, 'e2e4', self::NOW),
            fn () => $this->play->games()->offerDraw($gameId, $outsiderId, self::NOW),
            fn () => $this->play->games()->abort($gameId, $outsiderId, self::NOW),
        ] as $action) {
            try {
                $action();
                self::fail('An outsider was allowed to act in the game.');
            } catch (PlayRefused $refusal) {
                self::assertSame('play.not_your_game', $refusal->languageKey);
            }
        }
        self::assertTrue($this->game($gameId)->isRunning());
    }

    // --- Ratings (K4, R-05) ---

    public function testARatingIsNeverAppliedTwice(): void
    {
        $gameId = $this->startGame();
        $this->play->games()->resign($gameId, $this->blackId, self::NOW);
        $ratingAfterGame = $this->play->ratings()->shownRating($this->whiteId, Game::SPEED_CORRESPONDENCE)['rating'];

        // Ending again, and applying the rating again by hand, change nothing.
        self::assertFalse($this->play->games()->end($gameId, Game::RESULT_BLACK_WON, 'again', self::NOW));
        $this->play->ratings()->applyGameResult($this->game($gameId), Game::RESULT_WHITE_WON, self::NOW);

        self::assertSame($ratingAfterGame, $this->play->ratings()->shownRating($this->whiteId, Game::SPEED_CORRESPONDENCE)['rating']);
        self::assertSame(620, $ratingAfterGame);
        self::assertSame(2, $this->play->ratingRepository()->countHistoryForGame($gameId));
    }

    public function testAnUnratedGameNeverChangesARating(): void
    {
        $gameId = $this->play->games()->start($this->whiteId, $this->blackId, 3, false, '', self::NOW);
        $this->play->games()->resign($gameId, $this->blackId, self::NOW);

        self::assertSame(0, $this->play->ratingRepository()->countHistoryForGame($gameId));
    }

    // --- Rematch (G-22) ---

    public function testARematchSwapsColours(): void
    {
        $gameId = $this->startGame();
        $this->play->games()->resign($gameId, $this->blackId, self::NOW);

        self::assertNull($this->play->games()->rematch($gameId, $this->blackId, self::NOW));
        $newGameId = $this->play->games()->rematch($gameId, $this->whiteId, self::NOW);

        self::assertNotNull($newGameId);
        self::assertSame($this->blackId, $this->game($newGameId)->whiteId);
    }

    // --- Helpers ---

    private function startGame(): int
    {
        return $this->play->games()->start($this->whiteId, $this->blackId, 3, true, '', self::NOW);
    }

    /**
     * @param list<string> $moves
     */
    private function playMoves(int $gameId, array $moves): void
    {
        foreach ($moves as $move) {
            $game = $this->game($gameId);
            $this->play->games()->move($gameId, $game->playerToMoveId(), $game->ply, $move, self::NOW);
        }
    }

    private function runClockJob(int $now): void
    {
        (new GameClockJob($this->play->games(), $this->play->gameRepository(), $this->play->challengeRepository(), $now))->run();
    }

    private function game(int $gameId): Game
    {
        $game = $this->play->gameRepository()->find($gameId);
        self::assertNotNull($game);

        return $game;
    }

    /** The game ended with this result and reason, end() can't end it again, and a rated game was rated once. */
    private function assertEndedOnce(int $gameId, string $result, string $reason, bool $isRatedEnding = true): void
    {
        $game = $this->game($gameId);
        self::assertFalse($game->isRunning());
        self::assertSame($result, $game->result);
        self::assertSame($reason, $game->endReason);

        self::assertFalse($this->play->games()->end($gameId, Game::RESULT_DRAW, 'second_try', self::NOW));
        self::assertSame($reason, $this->game($gameId)->endReason);
        self::assertSame($isRatedEnding ? 2 : 0, $this->play->ratingRepository()->countHistoryForGame($gameId));
    }
}
