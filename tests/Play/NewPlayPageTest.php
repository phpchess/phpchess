<?php

declare(strict_types=1);

namespace PhpChess\Tests\Play;

use PhpChess\Domain\Play\Game;
use PhpChess\Kernel\PlayParts;
use PhpChess\Tests\Support\InstalledSite;
use PhpChess\Tests\Support\Values;
use PHPUnit\Framework\TestCase;

/**
 * The new Play page of F2 (A69, A70, A73, A83) through the Kernel, as a
 * browser uses it: the tiles and the new game pop-up, starting games from
 * the pop-up, the live boxes, and the empty slot on the login page.
 */
final class NewPlayPageTest extends TestCase
{
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

    // --- The page ---

    public function testThePageHasTilesAndAPopUpWithoutTextFields(): void
    {
        $anna = $this->site->loggedInBrowser('anna');
        $page = $anna->get('/play', ['new' => 'live'])->body;

        self::assertStringContainsString('data-new-game="computer"', $page);
        self::assertStringContainsString('data-new-game="correspondence"', $page);
        // Opened by the address, so it works without JavaScript.
        self::assertMatchesRegularExpression('/<dialog[^>]*id="new-game"[^>]*open/', $page);
        self::assertStringContainsString('name="opponent" value="human" checked', $page);
        // Every choice is a button: no text field anywhere on the page (A70).
        self::assertStringNotContainsString('type="text"', $page);
        self::assertStringNotContainsString('type="number"', $page);
        self::assertStringContainsString('id="play-boxes"', $page);
    }

    // --- Starting games from the pop-up ---

    public function testTheComputerTileStartsAGameInTheChosenBand(): void
    {
        $anna = $this->site->loggedInBrowser('anna');
        $started = $anna->submit('/play', '/play/start', ['opponent' => 'computer', 'band' => 'beginner', 'colour' => 'white']);

        self::assertStringContainsString('/chess/games/', $started->header('Location'));
        $game = $this->play->gameRepository()->listForPlayer($this->annaId, Game::STATE_RUNNING)[0];
        self::assertSame($this->annaId, $game->whiteId);
        $computer = $this->play->computerGames()->playerOf($game->blackId);
        self::assertNotNull($computer);
        self::assertLessThanOrEqual(800, $computer->elo);
        self::assertFalse($game->isRated);
    }

    public function testAPickedComputerPlayerComesBackChosenInThePopUp(): void
    {
        $anna = $this->site->loggedInBrowser('anna');
        $list = $anna->get('/play/computer')->body;
        $popUp = $anna->get('/play', ['new' => 'computer', 'player' => '13'])->body;

        self::assertStringContainsString('/chess/play?new=computer&amp;player=13', $list);
        self::assertStringContainsString('name="band" value="player:13" checked', $popUp);
    }

    public function testAChosenComputerPlayerGetsTheChosenPace(): void
    {
        $anna = $this->site->loggedInBrowser('anna');
        $anna->submit('/play', '/play/start', ['opponent' => 'computer', 'band' => 'player:13', 'pace' => 'days:7', 'colour' => 'white']);

        $game = $this->play->gameRepository()->listForPlayer($this->annaId, Game::STATE_RUNNING)[0];
        self::assertSame(7, $game->daysPerMove);
        self::assertSame(13, $this->play->computerGames()->playerOf($game->blackId)?->id);
    }

    public function testAComputerGameCanHaveAClock(): void
    {
        $controls = $this->play->games()->allowedTimeControls();
        if ($controls === []) {
            self::markTestSkipped('No time control allowed on this test site.');
        }
        $anna = $this->site->loggedInBrowser('anna');
        $anna->submit('/play', '/play/start', ['opponent' => 'computer', 'band' => 'beginner', 'pace' => 'live:' . $controls[0], 'colour' => 'black']);

        $game = $this->play->gameRepository()->listForPlayer($this->annaId, Game::STATE_RUNNING)[0];
        self::assertSame($controls[0], $game->timeControl);
    }

    public function testAnUnknownBandIsRefused(): void
    {
        $anna = $this->site->loggedInBrowser('anna');
        $refused = $anna->submit('/play', '/play/start', ['opponent' => 'computer', 'band' => 'grandmaster', 'colour' => 'random']);

        self::assertSame('/chess/play', $refused->header('Location'));
        self::assertSame([], $this->play->gameRepository()->listForPlayer($this->annaId, Game::STATE_RUNNING));
    }

    public function testTwoPeopleWhoWantDifferentColoursArePaired(): void
    {
        $anna = $this->site->loggedInBrowser('anna');
        $anna->submit('/play', '/play/start', ['opponent' => 'human', 'pace' => 'days:3', 'colour' => 'white', 'rated' => 'yes', 'rating_range' => '0']);
        $ben = $this->site->loggedInBrowser('ben');
        $paired = $ben->submit('/play', '/play/start', ['opponent' => 'human', 'pace' => 'days:3', 'colour' => 'black', 'rated' => 'yes', 'rating_range' => '200']);

        self::assertStringContainsString('/chess/games/', $paired->header('Location'));
        $game = $this->play->gameRepository()->listAll(Game::STATE_RUNNING)[0];
        self::assertSame($this->annaId, $game->whiteId);
        self::assertSame(3, $game->daysPerMove);
    }

    public function testTwoPeopleWhoWantTheSameColourBothWait(): void
    {
        $anna = $this->site->loggedInBrowser('anna');
        $anna->submit('/play', '/play/start', ['opponent' => 'human', 'pace' => 'days:3', 'colour' => 'white', 'rated' => 'yes']);
        $ben = $this->site->loggedInBrowser('ben');
        $waiting = $ben->submit('/play', '/play/start', ['opponent' => 'human', 'pace' => 'days:3', 'colour' => 'white', 'rated' => 'yes']);

        self::assertSame('/chess/play', $waiting->header('Location'));
        self::assertCount(2, $this->play->challengeRepository()->waitingOpen(time()));
    }

    // --- The live boxes (A69) ---

    public function testTheBoxesVersionChangesWhenAChallengeArrives(): void
    {
        $ben = $this->site->loggedInBrowser('ben');
        $ben->get('/play');
        $before = Values::jsonObject($ben->get('/play/news')->body)['boxes_version'] ?? '';

        $anna = $this->site->loggedInBrowser('anna');
        $anna->submit('/play', '/play/start', ['opponent' => 'human', 'pace' => 'days:3', 'colour' => 'random', 'rated' => 'yes']);
        $after = Values::jsonObject($ben->get('/play/news')->body)['boxes_version'] ?? '';
        $boxes = $ben->get('/play/boxes')->body;

        self::assertNotSame($before, $after);
        self::assertStringContainsString('id="play-boxes"', $boxes);
        self::assertStringContainsString('anna', $boxes);
        // Only the part, never the whole page with its layout.
        self::assertStringNotContainsString('<html', $boxes);
    }

    // --- The login page (A73) ---

    public function testTheLoginPageHasAnEmptySlotForOtherWaysToLogIn(): void
    {
        $page = $this->site->browser()->get('/login')->body;

        self::assertStringContainsString('<div class="login-other-ways" id="login-other-ways"></div>', $page);
        self::assertStringNotContainsString('Lichess', $page);
    }
}
