<?php

declare(strict_types=1);

namespace PhpChess\Tests\Play;

use PhpChess\Domain\Computer\ComputerGames;
use PhpChess\Domain\Computer\ComputerPlayers;
use PhpChess\Domain\Identity\User;
use PhpChess\Domain\Play\Game;
use PhpChess\Domain\Play\PlayRefused;
use PhpChess\Kernel\PlayParts;
use PhpChess\Tests\Support\InstalledSite;
use PHPUnit\Framework\TestCase;

/**
 * Games against the computer (C3-2: AI-03, AI-06, AI-07, K1, A53): the
 * computer accounts, unrated games, the computer's moves going through the
 * one move function, nobody else sending them, no loss on time for the
 * computer, the pages and the admin's engine page.
 */
final class ComputerPlayTest extends TestCase
{
    // Player 13 in computer-players.json: Bernard Moreau, 600, Trader.
    private const int PLAYER_ID = 13;

    private InstalledSite $site;
    private PlayParts $play;
    private int $annaId;

    protected function setUp(): void
    {
        $this->site = new InstalledSite($this);
        $this->play = new PlayParts($this->site->database, $this->site->settings());
        $this->annaId = $this->site->createUser('anna');
        $this->site->createUser('ben');
    }

    protected function tearDown(): void
    {
        if (isset($this->site)) {
            $this->site->remove();
        }
    }

    // --- Accounts and games ---

    public function testAGameIsUnratedAndTheComputerAccountIsMadeOnce(): void
    {
        $computer = $this->play->computerGames();
        $first = $computer->start($this->annaId, self::PLAYER_ID, 'w', 1_800_000_000);
        $second = $computer->start($this->annaId, self::PLAYER_ID, 'b', 1_800_000_000);

        $game = $this->play->gameRepository()->find($first);
        self::assertNotNull($game);
        self::assertFalse($game->isRated);
        self::assertSame($this->annaId, $game->whiteId);
        $rematch = $this->play->gameRepository()->find($second);
        self::assertNotNull($rematch);
        self::assertSame($game->blackId, $rematch->whiteId, 'the same account both times');

        $account = $this->site->users()->findById($game->blackId);
        self::assertNotNull($account);
        self::assertSame(User::STATE_COMPUTER, $account->state);
        self::assertSame('Bernard Moreau (computer)', $account->username);
    }

    public function testAnUnknownComputerPlayerIsRefused(): void
    {
        $this->expectException(PlayRefused::class);
        $this->play->computerGames()->start($this->annaId, 9999, 'w', 1_800_000_000);
    }

    // --- The computer's move (K1) ---

    public function testTheHumanOpponentSendsTheComputersMoveThroughTheMoveFunction(): void
    {
        $computer = $this->play->computerGames();
        $gameId = $computer->start($this->annaId, self::PLAYER_ID, 'b', 1_800_000_000);

        // Correspondence (A86): the reply is held, then played by the job within 1/4 to 3/4 of 14 days.
        $held = $computer->playComputerMove($gameId, $this->annaId, 0, 'e2e4', 1_800_000_010);
        $replyAt = $computer->heldReplyAt($gameId);
        $tooEarly = $computer->playDueReplies(1_800_000_000 + 3 * 86_400, 10);
        $played = $computer->playDueReplies(1_800_000_000 + 11 * 86_400, 10);
        $game = $this->play->gameRepository()->find($gameId);

        self::assertSame(0, $held->ply);
        self::assertNotNull($replyAt);
        self::assertGreaterThanOrEqual(1_800_000_000 + (int) (3.5 * 86_400), $replyAt);
        self::assertLessThanOrEqual(1_800_000_000 + (int) (10.5 * 86_400), $replyAt);
        self::assertSame(0, $tooEarly);
        self::assertSame(1, $played);
        self::assertSame(1, $game?->ply);
        self::assertSame('b', $game->sideToMove());
        self::assertNull($computer->heldReplyAt($gameId));
    }

    public function testATimedComputerGameIsAnsweredAtOnce(): void
    {
        $controls = $this->play->games()->allowedTimeControls();
        if ($controls === []) {
            self::markTestSkipped('No time control allowed on this test site.');
        }
        $computer = $this->play->computerGames();
        $gameId = $computer->start($this->annaId, self::PLAYER_ID, 'b', 1_800_000_000, 3, $controls[0], 1_800_000_000_000);

        $game = $computer->playComputerMove($gameId, $this->annaId, 0, 'e2e4', 1_800_000_001, 1_800_000_001_000);

        self::assertSame(1, $game->ply);
    }

    public function testTheComputerOutOfTimeBecauseThePlayerLeftMeansThePlayerLoses(): void
    {
        $controls = $this->play->games()->allowedTimeControls();
        if ($controls === []) {
            self::markTestSkipped('No time control allowed on this test site.');
        }
        $computer = $this->play->computerGames();
        // Anna plays Black; the computer (White) is to move and Anna never opens the board.
        $gameId = $computer->start($this->annaId, self::PLAYER_ID, 'b', 1_800_000_000, 3, $controls[0], 1_800_000_000_000);
        $muchLater = 1_800_000_000 + 7_200;

        self::assertTrue($this->play->games()->endIfOverdue($gameId, $muchLater, $muchLater * 1000));
        $game = $this->play->gameRepository()->find($gameId);
        self::assertSame(Game::RESULT_WHITE_WON, $game?->result, 'the computer wins');
        self::assertSame('left_board', $game->endReason);
    }

    public function testTheComputersMoveIsRefusedOnTheHumansTurn(): void
    {
        $computer = $this->play->computerGames();
        $gameId = $computer->start($this->annaId, self::PLAYER_ID, 'w', 1_800_000_000);

        $this->expectException(PlayRefused::class);
        $computer->playComputerMove($gameId, $this->annaId, 0, 'e2e4', 1_800_000_010);
    }

    public function testOnlyThePlayerOfTheGameMaySendTheComputersMove(): void
    {
        $computer = $this->play->computerGames();
        $gameId = $computer->start($this->annaId, self::PLAYER_ID, 'b', 1_800_000_000);
        $benId = (int) $this->site->users()->findByUsername('ben')?->id;

        $this->expectException(PlayRefused::class);
        $computer->playComputerMove($gameId, $benId, 0, 'e2e4', 1_800_000_010);
    }

    public function testAnIllegalComputerMoveIsRefusedByTheServer(): void
    {
        $computer = $this->play->computerGames();
        $gameId = $computer->start($this->annaId, self::PLAYER_ID, 'b', 1_800_000_000);

        $this->expectException(PlayRefused::class);
        $computer->playComputerMove($gameId, $this->annaId, 0, 'e2e5', 1_800_000_010);
    }

    public function testTheComputerNeverLosesOnTime(): void
    {
        $computer = $this->play->computerGames();
        $gameId = $computer->start($this->annaId, self::PLAYER_ID, 'b', 1_800_000_000);
        $muchLater = 1_800_000_000 + 60 * 86_400;

        self::assertFalse($this->play->games()->endIfOverdue($gameId, $muchLater));
        self::assertSame(Game::STATE_RUNNING, $this->play->gameRepository()->find($gameId)?->state);
    }

    // --- The players file ---

    public function testTheListHasSixtyOnePlayersWeakestFirst(): void
    {
        $players = (new ComputerPlayers())->all();

        self::assertCount(61, $players);
        self::assertSame('Wood Pusher', $players[0]->name);
        self::assertTrue($players[60]->slowOnPhones);
    }

    // --- The pages ---

    public function testThePageListsThePlayersAndStartsAGame(): void
    {
        $anna = $this->site->loggedInBrowser('anna');
        $page = $anna->get('/play/computer');
        self::assertStringContainsString('Bernard Moreau', $page->body);

        $started = $anna->submit('/play/computer', '/play/computer', ['player' => (string) self::PLAYER_ID, 'colour' => 'w']);
        self::assertStringContainsString('/chess/games/', $started->header('Location'));

        $gamePage = $anna->get(substr($started->header('Location'), strlen('/chess')));
        self::assertStringContainsString('computer-opponent.js', $gamePage->body);
        self::assertStringContainsString('data-computer-colour="b"', $gamePage->body);
    }

    public function testTheComputerIsNotListedAmongTheMembers(): void
    {
        $this->play->computerGames()->start($this->annaId, self::PLAYER_ID, 'w', 1_800_000_000);

        $players = $this->site->loggedInBrowser('anna')->get('/players');
        self::assertStringNotContainsString('(computer)', $players->body);
    }

    public function testTheComputerMoveAddressSaysWhenTheHeldReplyComes(): void
    {
        $gameId = $this->play->computerGames()->start($this->annaId, self::PLAYER_ID, 'b', 1_800_000_000);
        $anna = $this->site->loggedInBrowser('anna');
        $anna->get('/games/' . $gameId);

        $answer = $anna->postJson('/games/' . $gameId . '/computer-move', ['ply' => '0', 'move' => 'e2e4']);

        self::assertSame(200, $answer->status);
        // Correspondence (A86): the reply is held; the answer says when it comes.
        self::assertStringContainsString('"computer_reply_text":"The computer answers by', $answer->body);
    }

    public function testTheAdminEnginePageShowsTheEngineAndSavesTheDepthCap(): void
    {
        $admin = $this->site->loggedInBrowser(InstalledSite::ADMIN_NAME, true);
        $page = $admin->get('/admin/engine');
        self::assertStringContainsString('GarboChess-JS', $page->body);
        self::assertStringContainsString('BSD-3-Clause', $page->body);

        $admin->submit('/admin/engine', '/admin/engine', ['depth_cap' => '5']);
        self::assertSame(5, $this->play->computerGames()->depthCap($this->site->settings()));

        $admin->submit('/admin/engine', '/admin/engine', ['depth_cap' => '99']);
        self::assertSame(ComputerGames::HIGHEST_DEPTH_CAP, $this->play->computerGames()->depthCap($this->site->settings()));
    }
}
