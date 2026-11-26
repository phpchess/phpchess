<?php

declare(strict_types=1);

namespace PhpChess\Tests\Lichess;

use PhpChess\Domain\Community\WordFilter;
use PhpChess\Domain\Lichess\ContentScanner;
use PhpChess\Domain\System\FeatureSwitches;
use PhpChess\Http\UploadedFile;
use PhpChess\Infra\Lichess\LichessClient;
use PhpChess\Infra\Lichess\StudyRepository;
use PhpChess\Infra\System\AuditLogRepository;
use PhpChess\Tests\Support\InstalledSite;
use PHPUnit\Framework\TestCase;

/**
 * The Learn pages and the admin's Lichess page through the Kernel, as a
 * browser uses them (C5-1: L-01, L-03; K8, A74, A80, A81, A88).
 *
 * Nothing here reaches lichess.org: with the switch off the Lichess routes
 * answer 404 before any code could ask, and the switch can't be turned on
 * without a passed connection test.
 */
final class LearnPagesTest extends TestCase
{
    private InstalledSite $site;
    private int $annaId;

    /** @var list<string> files made by a test, removed afterwards */
    private array $temporaryFiles = [];

    protected function setUp(): void
    {
        $this->site = new InstalledSite($this);
        $this->annaId = $this->site->createUser('anna');
        $this->site->createUser('ben');
    }

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        if (isset($this->site)) {
            $this->site->remove();
        }
    }

    // --- The switch: off by default, and then the Lichess routes don't exist (K8) ---

    public function testWithTheSwitchOffTheLichessRoutesAreNotFound(): void
    {
        $anna = $this->site->loggedInBrowser('anna');
        $anna->get('/learn');

        self::assertSame(404, $anna->get('/learn/tv')->status);
        self::assertSame(404, $anna->get('/learn/tv/now')->status);
        self::assertSame(404, $anna->post('/learn/import', ['link' => 'https://lichess.org/abcdEFGH'])->status);

        $admin = $this->site->loggedInBrowser(InstalledSite::ADMIN_NAME, true);
        $admin->get('/admin/lichess');
        self::assertSame(404, $admin->post('/admin/lichess/import-game', ['link' => 'abcdEFGH', 'theme_id' => '1'])->status);
        self::assertSame(404, $admin->post('/admin/lichess/import-player', ['username' => 'anna', 'theme_id' => '1'])->status);
    }

    public function testTheLocalPartsWorkWithTheSwitchOff(): void
    {
        $anna = $this->site->loggedInBrowser('anna');

        $hub = $anna->get('/learn');
        self::assertSame(200, $hub->status);
        self::assertStringNotContainsString('/learn/tv', $hub->body);
        self::assertSame(200, $anna->get('/learn/puzzles')->status);
    }

    public function testTheSwitchStaysOffUntilTheConnectionTestPassed(): void
    {
        $admin = $this->site->loggedInBrowser(InstalledSite::ADMIN_NAME, true);
        $admin->get('/admin/switches');
        $admin->post('/admin/switches', ['switch_' . FeatureSwitches::EXTERNAL_CONTENT => 'on']);

        self::assertFalse((new FeatureSwitches($this->site->settings()))->isOn(FeatureSwitches::EXTERNAL_CONTENT));
        self::assertStringContainsString('Test connection', $admin->get('/admin/switches')->body);

        // After a passed test (stored as the client stores it), the admin can switch it on.
        $this->site->settings()->set(LichessClient::TEST_PASSED_AT_SETTING, 1000000);
        $admin->get('/admin/switches');
        $admin->post('/admin/switches', ['switch_' . FeatureSwitches::EXTERNAL_CONTENT => 'on']);
        self::assertTrue((new FeatureSwitches($this->site->settings()))->isOn(FeatureSwitches::EXTERNAL_CONTENT));
    }

    // --- Puzzles (L-01) ---

    public function testThePuzzleTrainerGivesAPuzzleThatFits(): void
    {
        $anna = $this->site->loggedInBrowser('anna');
        $anna->get('/learn/puzzles');

        $response = $anna->postJson('/learn/puzzles/next', ['motif' => 'fork', 'level' => 'club']);

        self::assertSame(200, $response->status);
        $answer = json_decode($response->body, true);
        self::assertIsArray($answer);
        $puzzle = $answer['puzzle'];
        self::assertIsArray($puzzle);
        self::assertGreaterThanOrEqual(1400, $puzzle['rating']);
        self::assertLessThanOrEqual(1799, $puzzle['rating']);
        self::assertNotEmpty($puzzle['moves']);
    }

    public function testAnUnknownMotifIsRefused(): void
    {
        $anna = $this->site->loggedInBrowser('anna');
        $anna->get('/learn/puzzles');

        self::assertSame(400, $anna->postJson('/learn/puzzles/next', ['motif' => '<script>', 'level' => 'club'])->status);
    }

    // --- Study themes and uploads (L-03, A81) ---

    public function testAnAdminUploadIsScannedAndOnlyCleanGamesAreKept(): void
    {
        $this->site->settings()->set(WordFilter::SETTING, "badword\n");
        $themeId = (new StudyRepository($this->site->database))->createTheme('Openings', 1);
        $pgn = "[White \"Anna\"]\n[Black \"Ben\"]\n[Result \"1-0\"]\n\n1. e4 e5 2. Bc4 Nc6 3. Qh5 Nf6 4. Qxf7# 1-0\n\n"
            . "[White \"badword\"]\n[Black \"Ben\"]\n[Result \"*\"]\n\n1. d4 *\n\n"
            . "[White \"Cleo\"]\n[Black \"www.example.com\"]\n[Result \"*\"]\n\n1. c4 *\n";

        $admin = $this->site->loggedInBrowser(InstalledSite::ADMIN_NAME, true);
        $admin->get('/admin/lichess');
        $admin->postWithFiles('/admin/lichess/upload', ['theme_id' => (string) $themeId], ['pgn_file' => $this->fileWith($pgn)]);

        $games = (new StudyRepository($this->site->database))->gamesOfTheme($themeId);
        self::assertCount(1, $games);
        self::assertSame('Anna', $games[0]['white']);

        $rejections = array_filter(
            (new AuditLogRepository($this->site->database))->newest(),
            static fn (array $entry): bool => $entry['action'] === 'study.rejected',
        );
        self::assertCount(2, $rejections);

        // Members see the theme and replay the game.
        $anna = $this->site->loggedInBrowser('anna');
        self::assertStringContainsString('Openings', $anna->get('/learn')->body);
        $gamePage = $anna->get('/learn/games/' . $games[0]['id']);
        self::assertSame(200, $gamePage->status);
        self::assertStringContainsString('Qxf7#', $gamePage->body);
    }

    public function testAnUploadLargerThanOneMegabyteIsRefused(): void
    {
        $themeId = (new StudyRepository($this->site->database))->createTheme('Big', 1);
        $admin = $this->site->loggedInBrowser(InstalledSite::ADMIN_NAME, true);
        $admin->get('/admin/lichess');
        $admin->postWithFiles('/admin/lichess/upload', ['theme_id' => (string) $themeId], ['pgn_file' => $this->fileWith(str_repeat(' ', ContentScanner::MAX_BYTES + 1))]);

        self::assertSame([], (new StudyRepository($this->site->database))->gamesOfTheme($themeId));
    }

    public function testAPlayersOwnImportIsSeenByThatPlayerOnly(): void
    {
        $studies = new StudyRepository($this->site->database);
        $game = (new ContentScanner(new WordFilter([])))->scan("[White \"Anna\"]\n[Result \"*\"]\n\n1. e4 *\n")->accepted[0];
        $annaId = $this->annaId;
        $gameId = $studies->addGame($game, null, $annaId, 'lichess', 'abcdEFGH', $annaId);

        self::assertSame(200, $this->site->loggedInBrowser('anna')->get('/learn/games/' . $gameId)->status);
        $ben = $this->site->loggedInBrowser('ben');
        self::assertSame(404, $ben->get('/learn/games/' . $gameId)->status);

        // Nor can ben remove it.
        $ben->get('/learn');
        $ben->post('/learn/games/' . $gameId . '/remove');
        self::assertNotNull($studies->findGame($gameId));
    }

    public function testVisitorsAreSentToTheLogin(): void
    {
        self::assertSame(303, $this->site->browser()->get('/learn')->status);
    }

    // --- Helpers ---

    private function fileWith(string $contents): UploadedFile
    {
        $path = (string) tempnam(sys_get_temp_dir(), 'pgn');
        file_put_contents($path, $contents);
        $this->temporaryFiles[] = $path;

        return new UploadedFile($path, strlen($contents));
    }
}
