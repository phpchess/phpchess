<?php

declare(strict_types=1);

namespace PhpChess\Tests\Lichess;

use PhpChess\Domain\Lichess\LichessContent;
use PhpChess\Domain\System\FeatureSwitches;
use PhpChess\Infra\Lichess\LichessClient;
use PhpChess\Infra\Lichess\StudyRepository;
use PhpChess\Tests\Support\InstalledSite;
use PHPUnit\Framework\TestCase;

/**
 * The Lichess page and its parts (A93–A96), with a fake Lichess: the test
 * site answers from $site->lichessReplies and never leaves the machine.
 * The replies have the shapes the real API gave on 2026-11-26.
 */
final class LichessPagesTest extends TestCase
{
    private InstalledSite $site;

    private const string CHANNELS = '{"best":{"user":{"id":"ceshen","name":"Ceshen"},"rating":2179,"gameId":"TTpS2T8k","color":"white"},'
        . '"blitz":{"user":{"id":"x","name":"<script>"},"rating":1900,"gameId":"abcdEFGH","color":"black"}}';

    private const string BROADCASTS = '{"active":[{"tour":{"id":"qfkdt8mn","name":"World Championship 2026"},'
        . '"round":{"id":"fnmEB1kH","name":"Game 3","startsAt":1795701600000}},'
        . '{"tour":{"id":"badbad12","name":"A badword cup"},"round":{"id":"zzzzzzzz","name":"Round 1"}}],"upcoming":[],"past":{}}';

    private const string ROUND = "[Event \"World Championship\"]\n[White \"Sindarov, Javokhir\"]\n[Black \"Gukesh D\"]\n[Result \"*\"]\n"
        . "[BroadcastURL \"https://lichess.org/broadcast/x\"]\n\n1. e4 e5 2. Nf3 Nc6 3. Bb5 Nf6 *\n";

    protected function setUp(): void
    {
        $this->site = new InstalledSite($this);
        $this->site->createUser('anna');
        $this->site->settings()->set('community.word_filter', 'badword');
        $this->site->settings()->set(LichessClient::TEST_PASSED_AT_SETTING, 1000000);
        (new FeatureSwitches($this->site->settings()))->set(FeatureSwitches::EXTERNAL_CONTENT, true);
        $this->site->lichessReplies = [
            '/api/tv/channels' => self::CHANNELS,
            '/api/broadcast/top' => self::BROADCASTS,
            '/api/broadcast/round/fnmEB1kH.pgn' => self::ROUND,
        ];
    }

    protected function tearDown(): void
    {
        if (isset($this->site)) {
            $this->site->remove();
        }
    }

    public function testThePageListsLiveGamesAndBroadcasts(): void
    {
        $page = $this->site->loggedInBrowser('anna')->get('/learn/lichess');

        self::assertSame(200, $page->status);
        self::assertStringContainsString('Ceshen', $page->body);
        self::assertStringContainsString('/learn/tv?channel=best', $page->body);
        // A name that isn't a Lichess user name is not shown at all.
        self::assertStringNotContainsString('&lt;script', $page->body);
        self::assertStringContainsString('World Championship 2026', $page->body);
        // A broadcast with a blocked word is left out.
        self::assertStringNotContainsString('badword', $page->body);
    }

    public function testManyViewersShareOneRequest(): void
    {
        $anna = $this->site->loggedInBrowser('anna');
        $anna->get('/learn/lichess');
        $anna->get('/learn/lichess');

        $asked = array_filter($this->site->lichessAsked, static fn (string $url): bool => str_contains($url, '/api/tv/channels'));
        self::assertCount(1, $asked);
    }

    public function testABroadcastRoundShowsItsGamesOnOurBoard(): void
    {
        $anna = $this->site->loggedInBrowser('anna');
        $round = $anna->get('/learn/lichess/broadcast/fnmEB1kH');
        self::assertStringContainsString('Sindarov, Javokhir', $round->body);

        $game = $anna->get('/learn/lichess/broadcast/fnmEB1kH/1');
        self::assertSame(200, $game->status);
        self::assertStringContainsString('study-game', $game->body);
        // The scanner dropped the tag with an outside address.
        self::assertStringNotContainsString('BroadcastURL', $game->body);
        self::assertSame(404, $anna->get('/learn/lichess/broadcast/not-an-id')->status);
    }

    public function testTheDailyPuzzleStartsOneMoveEarlierInOurTrainer(): void
    {
        // The game's moves, and the puzzle's position after the last of them.
        $this->site->lichessReplies['/api/puzzle/daily'] = (string) json_encode([
            'game' => ['pgn' => 'e4 e5 Bc4 Nc6 Qh5 Nf6'],
            'puzzle' => ['id' => 'abc12', 'fen' => 'r1bqkb1r/pppp1ppp/2n2n2/4p2Q/2B1P3/8/PPPP1PPP/RNB1K1NR w KQkq - 4 4',
                'lastMove' => 'g8f6', 'solution' => ['h5f7'], 'rating' => 600],
        ]);

        $page = $this->site->loggedInBrowser('anna')->get('/learn/lichess/daily');

        self::assertSame(200, $page->status);
        // The trainer gets the position before Black's Nf6, and Nf6 as the first move.
        self::assertStringContainsString('r1bqkbnr/pppp1ppp/2n5/4p2Q/2B1P3/8/PPPP1PPP/RNB1K1NR b KQkq', str_replace('\/', '/', html_entity_decode($page->body)));
        self::assertStringContainsString('&quot;g8f6&quot;,&quot;h5f7&quot;', $page->body);
    }

    public function testAPartTheAdminTurnedOffIsNotFound(): void
    {
        $content = new LichessContent(new FeatureSwitches($this->site->settings()), $this->site->settings());
        $content->choose(LichessContent::BROADCASTS, false);
        $content->choose(LichessContent::DAILY, false);

        $anna = $this->site->loggedInBrowser('anna');
        self::assertStringNotContainsString('World Championship', $anna->get('/learn/lichess')->body);
        self::assertSame(404, $anna->get('/learn/lichess/broadcast/fnmEB1kH')->status);
        self::assertSame(404, $anna->get('/learn/lichess/daily')->status);
    }

    public function testWithTheSwitchOffNothingIsAsked(): void
    {
        (new FeatureSwitches($this->site->settings()))->set(FeatureSwitches::EXTERNAL_CONTENT, false);
        $anna = $this->site->loggedInBrowser('anna');

        self::assertSame(404, $anna->get('/learn/lichess')->status);
        self::assertSame([], $this->site->lichessAsked);
    }

    public function testTheAdminImportsAStudyChapterByChapter(): void
    {
        $this->site->lichessReplies['/api/study/abcdEFGH.pgn'] = "[Event \"Rook endings: Lucena\"]\n[Result \"*\"]\n[SetUp \"1\"]\n"
            . "[FEN \"1K1k4/1P6/8/8/8/8/r7/2R5 w - - 0 1\"]\n\n{Build a bridge.} *\n\n"
            . "[Event \"Rook endings: Bad\"]\n[Result \"*\"]\n\n1. e4 {visit www.example.com} *\n";
        $themeId = (new StudyRepository($this->site->database))->createTheme('Basic rook endgames', 1, 'rook_endgames', 'basic');

        $admin = $this->site->loggedInBrowser(InstalledSite::ADMIN_NAME, true);
        $admin->get('/admin/lichess');
        $admin->post('/admin/lichess/import-study', ['theme_id' => (string) $themeId, 'link' => 'https://lichess.org/study/abcdEFGH']);

        $games = (new StudyRepository($this->site->database))->gamesOfTheme($themeId);
        self::assertCount(1, $games);
        $page = $this->site->loggedInBrowser('anna')->get('/learn/games/' . $games[0]['id'])->body;
        self::assertStringContainsString('Build a bridge.', $page);
        self::assertStringContainsString('https://lichess.org/study/abcdEFGH', $page);
    }

    public function testThemesArePickedFromTheLichessList(): void
    {
        $admin = $this->site->loggedInBrowser(InstalledSite::ADMIN_NAME, true);
        $admin->get('/admin/lichess');
        $admin->post('/admin/lichess/themes', ['topic' => 'fundamental_tactics:fork']);
        $admin->post('/admin/lichess/themes', ['topic' => 'openings:', 'title' => 'London System']);
        $admin->post('/admin/lichess/themes', ['topic' => 'nonsense:fork']);
        $admin->post('/admin/lichess/themes', ['topic' => 'openings:', 'title' => 'The badword gambit']);

        $themes = (new StudyRepository($this->site->database))->themes();
        self::assertSame(['The fork', 'London System'], array_column($themes, 'title'));
        self::assertSame(['fundamental_tactics', 'openings'], array_column($themes, 'section'));

        $hub = $this->site->loggedInBrowser('anna')->get('/learn')->body;
        self::assertStringContainsString('Fundamental tactics', $hub);
        self::assertStringContainsString('Openings', $hub);
    }

    public function testTheAdminTurnsEverythingOnFromTheLichessPage(): void
    {
        (new FeatureSwitches($this->site->settings()))->set(FeatureSwitches::EXTERNAL_CONTENT, false);
        $admin = $this->site->loggedInBrowser(InstalledSite::ADMIN_NAME, true);
        $admin->get('/admin/lichess');
        $admin->post('/admin/lichess/content', ['external_content' => 'on', 'show_live' => 'on', 'show_imports' => 'on']);

        $content = new LichessContent(new FeatureSwitches($this->site->settings()), $this->site->settings());
        self::assertTrue($content->isShown(LichessContent::LIVE));
        self::assertFalse($content->isShown(LichessContent::BROADCASTS));

        // Without a passed test it can't be turned on.
        $this->site->settings()->set(LichessClient::TEST_PASSED_AT_SETTING, 0);
        $admin->post('/admin/lichess/content', ['external_content' => '']);
        $admin->post('/admin/lichess/content', ['external_content' => 'on']);
        self::assertFalse((new FeatureSwitches($this->site->settings()))->isOn(FeatureSwitches::EXTERNAL_CONTENT));
    }
}
