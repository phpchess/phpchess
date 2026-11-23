<?php

declare(strict_types=1);

namespace PhpChess\Tests\Play;

use PhpChess\Domain\Play\Game;
use PhpChess\Domain\Play\Games;
use PhpChess\Domain\Play\PlayRefused;
use PhpChess\Domain\Play\TimeControl;
use PhpChess\Infra\Jobs\GameClockJob;
use PhpChess\Kernel\PlayParts;
use PhpChess\Tests\Support\InstalledSite;
use PhpChess\Tests\Support\TestDatabase;
use PHPUnit\Framework\TestCase;

/**
 * Tests timed play with the database (C3-1: G-14 to G-18, G-23, K2, K3,
 * K12, A46): the clocks inside the move function, a flag decided inside
 * the move lock, premoves, changing pace, the limits, and the poll.
 */
final class TimedPlayTest extends TestCase
{
    private const int NOW = 1_800_000_000;
    private const int NOW_MS = self::NOW * 1000;

    private InstalledSite $site;
    private PlayParts $play;
    private int $whiteId;
    private int $blackId;

    protected function setUp(): void
    {
        $this->site = new InstalledSite($this);
        $this->play = new PlayParts($this->site->database, $this->site->settings());
        $this->whiteId = $this->site->createUser('whitetimed');
        $this->blackId = $this->site->createUser('blacktimed');
    }

    protected function tearDown(): void
    {
        if (isset($this->site)) {
            $this->site->remove();
        }
    }

    // --- Starting ---

    public function testATimedGameStartsWithFullClocksAndItsSpeed(): void
    {
        $game = $this->game($this->startTimed('5+3'));

        self::assertTrue($game->isTimed());
        self::assertSame(TimeControl::SPEED_BLITZ, $game->speed);
        self::assertSame(300_000, $game->whiteMsLeft);
        self::assertSame(300_000, $game->blackMsLeft);
        self::assertSame(self::NOW_MS, $game->turnSavedMs);
        self::assertNull($game->daysPerMove);
    }

    // --- The clock inside the move function (K2, K12) ---

    public function testEachMoveCostsTheTimeUsedAfterDeliveryAndAddsTheIncrement(): void
    {
        $gameId = $this->startTimed('5+3');
        $games = $this->play->games();

        // White never polled: the turn counts from 2 s after the start. 5 s later: 5000 - 2000 - 300 = 2700 used.
        $games->move($gameId, $this->whiteId, 0, 'e2e4', self::NOW + 5, self::NOW_MS + 5000);
        $game = $this->game($gameId);
        self::assertSame(300_000 - 2700 + 3000, $game->whiteMsLeft);
        self::assertSame(self::NOW_MS + 5000, $game->turnSavedMs);

        // Black's page received the move 500 ms later and moved 10 s after that: 9700 used.
        $this->play->gameRepository()->markDelivered($gameId, 1, self::NOW_MS + 5500);
        $games->move($gameId, $this->blackId, 1, 'e7e5', self::NOW + 15, self::NOW_MS + 15_500);
        self::assertSame(300_000 - 9700 + 3000, $this->game($gameId)->blackMsLeft);
    }

    public function testOnlyTheFirstDeliveryOfTheCurrentTurnCounts(): void
    {
        $gameId = $this->startTimed('5+3');
        $repository = $this->play->gameRepository();
        $repository->markDelivered($gameId, 0, self::NOW_MS + 300);
        $repository->markDelivered($gameId, 0, self::NOW_MS + 900);
        self::assertSame(self::NOW_MS + 300, $this->game($gameId)->turnDeliveredMs);

        // A poll that read the game before the next move can not mark the new turn.
        $this->play->games()->move($gameId, $this->whiteId, 0, 'e2e4', self::NOW + 1, self::NOW_MS + 1000);
        $repository->markDelivered($gameId, 0, self::NOW_MS + 1100);
        self::assertNull($this->game($gameId)->turnDeliveredMs);
    }

    // --- The flag (K3, A46) ---

    public function testAMoveAfterTheFlagEndsTheGameOnTimeInsteadOfBeingPlayed(): void
    {
        $gameId = $this->startTimed('5+3');
        $late = 300_000 + 2000 + 300 + 1;

        $game = $this->play->games()->move($gameId, $this->whiteId, 0, 'e2e4', self::NOW + 303, self::NOW_MS + $late);

        self::assertFalse($game->isRunning());
        self::assertSame(Game::RESULT_BLACK_WON, $game->result);
        self::assertSame(Games::REASON_TIMEOUT, $game->endReason);
        self::assertSame(0, $game->ply);
        self::assertSame(0, $game->whiteMsLeft);
    }

    public function testAMoveJustBeforeTheFlagIsPlayed(): void
    {
        $gameId = $this->startTimed('5+3');
        $justInTime = 300_000 + 2000 + 300 - 1;

        $game = $this->play->games()->move($gameId, $this->whiteId, 0, 'e2e4', self::NOW + 302, self::NOW_MS + $justInTime);

        self::assertTrue($game->isRunning());
        self::assertSame(1 + 3000, $game->whiteMsLeft);
    }

    public function testTheFlagIsDecidedInsideTheMoveLock(): void
    {
        $gameId = $this->startTimed('5+3');
        $afterFlag = self::NOW_MS + 400_000;

        // Another request holds the game row (a move being played): the flag check must wait for it.
        $other = TestDatabase::secondConnection($this);
        $other->run('START TRANSACTION');
        $other->run('SELECT id FROM ' . $other->table('games') . ' WHERE id = ? FOR UPDATE', [$gameId]);
        $this->site->database->run('SET SESSION innodb_lock_wait_timeout = 1');
        try {
            $this->play->games()->endIfFlagged($gameId, self::NOW + 400, $afterFlag);
            self::fail('The flag check did not wait for the game lock.');
        } catch (\PDOException $lockWaitTimeout) {
            self::assertTrue($this->game($gameId)->isRunning());
        } finally {
            $other->run('ROLLBACK');
        }

        // With the lock free, the flag falls, once.
        self::assertTrue($this->play->games()->endIfFlagged($gameId, self::NOW + 400, $afterFlag));
        self::assertFalse($this->play->games()->endIfFlagged($gameId, self::NOW + 400, $afterFlag));
        self::assertSame(2, $this->play->ratingRepository()->countHistoryForGame($gameId));
        $this->expectException(PlayRefused::class);
        $this->play->games()->move($gameId, $this->whiteId, 0, 'e2e4', self::NOW + 400, $afterFlag);
    }

    public function testAFlagAgainstALoneKingIsADraw(): void
    {
        $gameId = $this->play->games()->start($this->whiteId, $this->blackId, 3, true, '8/8/4k3/8/8/3K4/3R4/8 b - - 0 1', self::NOW, '5+3', self::NOW_MS);

        self::assertTrue($this->play->games()->endIfFlagged($gameId, self::NOW + 400, self::NOW_MS + 400_000));
        $game = $this->game($gameId);
        self::assertSame(Game::RESULT_WHITE_WON, $game->result);

        $drawId = $this->play->games()->start($this->blackId, $this->whiteId, 3, false, '8/8/4k3/8/8/3K4/3R4/8 w - - 0 1', self::NOW, '10+0', self::NOW_MS);
        $this->site->settings()->set('play.max_live_games_per_player', 5);
        $this->play->games()->endIfFlagged($drawId, self::NOW + 700, self::NOW_MS + 700_000);
        self::assertSame(Games::REASON_TIMEOUT_DRAW, $this->game($drawId)->endReason);
    }

    public function testTheClockJobEndsAFlaggedGameNobodyPolls(): void
    {
        $gameId = $this->startTimed('5+3');
        (new GameClockJob($this->play->games(), $this->play->gameRepository(), $this->play->challengeRepository(), self::NOW + 400))->run();

        self::assertSame(Games::REASON_TIMEOUT, $this->game($gameId)->endReason);
    }

    // --- Premoves (G-17) ---

    public function testAPremoveIsPlayedAtOnceAndCostsNoTime(): void
    {
        $gameId = $this->startTimed('5+3');
        $games = $this->play->games();
        $games->premove($gameId, $this->blackId, 0, 'e7e5');

        $games->move($gameId, $this->whiteId, 0, 'e2e4', self::NOW + 3, self::NOW_MS + 3000);

        $game = $this->game($gameId);
        self::assertSame(2, $game->ply);
        self::assertSame(300_000 + 3000, $game->blackMsLeft);
        self::assertNull($game->premoveUci);
        self::assertSame(self::NOW_MS + 3000, $game->turnSavedMs);
    }

    public function testAnIllegalPremoveIsDropped(): void
    {
        $gameId = $this->startTimed('5+3');
        $games = $this->play->games();
        $games->premove($gameId, $this->blackId, 0, 'e7e4');

        $games->move($gameId, $this->whiteId, 0, 'e2e4', self::NOW + 3, self::NOW_MS + 3000);

        $game = $this->game($gameId);
        self::assertSame(1, $game->ply);
        self::assertNull($game->premoveUci);
    }

    public function testAPremoveNeedsATimedGameAndTheOpponentToMove(): void
    {
        $gameId = $this->startTimed('5+3');
        try {
            $this->play->games()->premove($gameId, $this->whiteId, 0, 'e2e4');
            self::fail('A premove on your own turn was accepted.');
        } catch (PlayRefused $refusal) {
            self::assertSame('play.board_out_of_date', $refusal->languageKey);
        }

        $slowId = $this->play->games()->start($this->whiteId, $this->blackId, 3, true, '', self::NOW);
        $this->expectException(PlayRefused::class);
        $this->play->games()->premove($slowId, $this->blackId, 0, 'e7e5');
    }

    // --- Changing pace (G-23) ---

    public function testATimedGameBecomesCorrespondenceWhenBothAgree(): void
    {
        $gameId = $this->startTimed('5+3');
        $games = $this->play->games();
        $games->proposePace($gameId, $this->whiteId, '3d', self::NOW + 10, self::NOW_MS + 10_000);
        self::assertSame('3d', $this->game($gameId)->paceOffer);

        $games->acceptPace($gameId, $this->blackId, self::NOW + 20, self::NOW_MS + 20_000);

        $game = $this->game($gameId);
        self::assertFalse($game->isTimed());
        self::assertSame(3, $game->daysPerMove);
        self::assertNull($game->paceOffer);
        self::assertSame(TimeControl::SPEED_BLITZ, $game->speed, 'the rating category stays');
    }

    public function testTheRulesPageIsWrittenFromTheSettings(): void
    {
        $this->site->settings()->set('play.min_time_control', '10+0');
        $this->site->settings()->set('play.max_live_games', 12);
        $page = $this->site->loggedInBrowser('whitetimed')->get('/rules')->body;

        self::assertStringContainsString('10+0, 10+5', $page);
        self::assertStringNotContainsString('5+3,', $page);
        self::assertStringContainsString('At most 12 timed games', $page);
    }

    public function testACorrespondenceGameBecomesTimedOnlyWhileBothAreOnline(): void
    {
        $gameId = $this->play->games()->start($this->whiteId, $this->blackId, 3, true, '', self::NOW);
        $games = $this->play->games();
        $games->proposePace($gameId, $this->whiteId, '10+5', self::NOW, self::NOW_MS);
        try {
            $games->acceptPace($gameId, $this->blackId, self::NOW, self::NOW_MS);
            self::fail('The pace changed without both players online.');
        } catch (PlayRefused $refusal) {
            self::assertSame('play.pace_needs_both_online', $refusal->languageKey);
        }

        $games->markSeen($this->game($gameId), $this->whiteId, self::NOW + 5);
        $games->markSeen($this->game($gameId), $this->blackId, self::NOW + 5);
        $games->acceptPace($gameId, $this->blackId, self::NOW + 10, self::NOW_MS + 10_000);

        $game = $this->game($gameId);
        self::assertSame('10+5', $game->timeControl);
        self::assertSame(600_000, $game->whiteMsLeft);
        self::assertSame(600_000, $game->blackMsLeft);
        self::assertSame(Game::SPEED_CORRESPONDENCE, $game->speed);
    }

    // --- Limits (A12, G-18) ---

    public function testTheAdminFloorRefusesFasterControls(): void
    {
        $this->site->settings()->set('play.min_time_control', '10+0');
        $this->expectException(PlayRefused::class);
        $this->startTimed('5+3');
    }

    public function testAPlayerHasOneTimedGameAtOnceByDefault(): void
    {
        $this->startTimed('5+3');
        $thirdId = $this->site->createUser('thirdtimed');
        try {
            $this->play->games()->start($this->whiteId, $thirdId, 3, true, '', self::NOW, '5+3', self::NOW_MS);
            self::fail('A second timed game was started.');
        } catch (PlayRefused $refusal) {
            self::assertSame('play.live_game_limit', $refusal->languageKey);
        }

        // Raised for simuls (B19): then the site limit counts.
        $this->site->settings()->set('play.max_live_games_per_player', 5);
        $this->site->settings()->set('play.max_live_games', 1);
        $this->play = new PlayParts($this->site->database, $this->site->settings());
        try {
            $this->play->games()->start($this->whiteId, $thirdId, 3, true, '', self::NOW, '5+3', self::NOW_MS);
            self::fail('The site limit was passed.');
        } catch (PlayRefused $refusal) {
            self::assertSame('play.live_games_full', $refusal->languageKey);
        }
    }

    // --- Lobby (G-16) ---

    public function testAnOpenTimedChallengeShowsOnlyWhileItsPosterIsThere(): void
    {
        $challenges = $this->play->challenges();
        $challengeId = $challenges->createOpen($this->whiteId, 'random', 3, true, self::NOW, '5+3');
        $repository = $this->play->challengeRepository();
        self::assertCount(1, $repository->waitingOpen(self::NOW + 20));
        self::assertCount(0, $repository->waitingOpen(self::NOW + 40));

        $repository->markPosterSeen($this->whiteId, self::NOW + 40);
        self::assertCount(1, $repository->waitingOpen(self::NOW + 41));

        $gameId = $challenges->accept($challengeId, $this->blackId, self::NOW + 41, self::NOW_MS + 41_000);
        self::assertSame('5+3', $this->game($gameId)->timeControl);
        self::assertSame($gameId, $this->play->gameRepository()->newTimedGameNotSeenBy($this->whiteId));
    }

    public function testThePlayButtonPairsOnlyTheSameTimeControl(): void
    {
        $challenges = $this->play->challenges();
        $challenges->createOpen($this->whiteId, 'random', 3, true, self::NOW, '10+0');

        $other = $challenges->quickPlay($this->blackId, 3, true, self::NOW, '5+3', self::NOW_MS);
        self::assertNull($other['game_id']);

        $thirdId = $this->site->createUser('thirdtimed');
        $match = $challenges->quickPlay($thirdId, 3, true, self::NOW, '10+0', self::NOW_MS);
        self::assertNotNull($match['game_id']);
    }

    // --- The poll (API-03) ---

    public function testThePollMarksDeliveryAndAnswersShortWhenNothingChanged(): void
    {
        $gameId = $this->startTimedNow();
        $white = $this->site->loggedInBrowser('whitetimed');

        $first = json_decode($white->get("/games/{$gameId}/state", ['since' => '0'])->body, true);
        self::assertIsArray($first);
        self::assertSame('5+3', $first['time_control']);
        self::assertSame(3000, $first['next_poll_ms'], 'own move: the slower interval');
        self::assertIsArray($first['clock']);
        self::assertSame('w', $first['clock']['running']);
        self::assertNotNull($this->game($gameId)->turnDeliveredMs);
        self::assertIsString($first['version']);

        $second = json_decode($white->get("/games/{$gameId}/state", ['since' => '0', 'v' => $first['version']])->body, true);
        self::assertIsArray($second);
        self::assertTrue($second['unchanged']);
        self::assertArrayNotHasKey('moves', $second);
        self::assertSame(3000, $second['next_poll_ms'], 'own move: the slower interval');
    }

    // --- Helpers ---

    private function startTimed(string $timeControl): int
    {
        return $this->play->games()->start($this->whiteId, $this->blackId, 3, true, '', self::NOW, $timeControl, self::NOW_MS);
    }

    /** A timed game starting at the real time, for the kernel tests (the kernel uses the real clock). */
    private function startTimedNow(): int
    {
        return $this->play->games()->start($this->whiteId, $this->blackId, 3, true, '', time(), '5+3', (int) floor(microtime(true) * 1000));
    }

    private function game(int $gameId): Game
    {
        $game = $this->play->gameRepository()->find($gameId);
        self::assertNotNull($game);

        return $game;
    }
}
