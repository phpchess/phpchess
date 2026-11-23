<?php

declare(strict_types=1);

namespace PhpChess\Tests\Play;

use PhpChess\Domain\Notify\MoveEmails;
use PhpChess\Domain\Play\Game;
use PhpChess\Infra\System\AuditLogRepository;
use PhpChess\Kernel\Kernel;
use PhpChess\Kernel\PlayParts;
use PhpChess\Tests\Support\InstalledSite;
use PHPUnit\Framework\TestCase;

/**
 * The play pages through the Kernel, as a browser uses them (G-02 to G-22,
 * N-01, N-02, API-02, ADM-04): challenges, invite links, the board, moves,
 * notices and emails, the API and the admin's games page.
 */
final class PlayPagesTest extends TestCase
{
    private InstalledSite $site;
    private PlayParts $play;

    protected function setUp(): void
    {
        $this->site = new InstalledSite($this);
        $this->play = new PlayParts($this->site->database, $this->site->settings());
        $this->site->createUser('anna');
        $this->site->createUser('ben');
        $this->site->createUser('cleo');
    }

    protected function tearDown(): void
    {
        if (isset($this->site)) {
            $this->site->remove();
        }
    }

    // --- Challenges (G-05, G-20) ---

    public function testAnOpenChallengeCanBeAcceptedOnlyOnce(): void
    {
        $anna = $this->site->loggedInBrowser('anna');
        $anna->submit('/play', '/challenges', ['kind' => 'open', 'colour' => 'white', 'days_per_move' => '3', 'rated' => 'yes']);
        $challengeId = $this->play->challengeRepository()->waitingOpen(time())[0]->id;

        $ben = $this->site->loggedInBrowser('ben');
        $cleo = $this->site->loggedInBrowser('cleo');
        $ben->get('/play');
        $cleo->get('/play');
        $first = $ben->post("/challenges/{$challengeId}/accept");
        $second = $cleo->post("/challenges/{$challengeId}/accept");

        self::assertStringContainsString('/chess/games/', $first->header('Location'));
        self::assertSame('/chess/play', $second->header('Location'));
        self::assertCount(1, $this->play->gameRepository()->listAll(Game::STATE_RUNNING));
    }

    public function testThePlayButtonPairsTwoWaitingPlayers(): void
    {
        $anna = $this->site->loggedInBrowser('anna');
        $anna->submit('/play', '/play/quick', ['days_per_move' => '5', 'rated' => 'yes']);
        $ben = $this->site->loggedInBrowser('ben');
        $response = $ben->submit('/play', '/play/quick', ['days_per_move' => '5', 'rated' => 'yes']);

        self::assertStringContainsString('/chess/games/', $response->header('Location'));
        $game = $this->play->gameRepository()->listAll(Game::STATE_RUNNING)[0];
        self::assertSame(5, $game->daysPerMove);
    }

    public function testADirectChallengeTellsTheOpponent(): void
    {
        $anna = $this->site->loggedInBrowser('anna');
        $anna->submit('/play', '/challenges', ['kind' => 'direct', 'opponent' => 'ben', 'colour' => 'random', 'days_per_move' => '3']);

        $ben = $this->site->loggedInBrowser('ben');
        self::assertStringContainsString('anna challenges you', $ben->get('/notices')->body);
        self::assertStringContainsString('class="badge"', $ben->get('/play')->body);
    }

    // --- Invite links (G-21) ---

    public function testAnInviteLinkWorksOnceWithAQrCode(): void
    {
        $anna = $this->site->loggedInBrowser('anna');
        $created = $anna->submit('/play', '/challenges', ['kind' => 'invite', 'colour' => 'random', 'days_per_move' => '3']);
        $invitePage = $anna->get(substr($created->header('Location'), strlen('/chess')));
        self::assertStringContainsString('<svg', $invitePage->body);
        preg_match('#/invite/([0-9a-f]{32})#', $invitePage->body, $found);
        $invitePath = '/invite/' . ($found[1] ?? 'missing');

        self::assertStringContainsString('anna invites you', $this->site->browser()->get($invitePath)->body);
        $ben = $this->site->loggedInBrowser('ben');
        $accepted = $ben->submit($invitePath, $invitePath);
        self::assertStringContainsString('/chess/games/', $accepted->header('Location'));

        $cleo = $this->site->loggedInBrowser('cleo');
        self::assertSame(404, $cleo->get($invitePath)->status);
    }

    // --- The board and moves (G-02, G-04, G-10, K2) ---

    public function testAGameIsPlayedThroughTheBoardPage(): void
    {
        $gameId = $this->startGame();
        $anna = $this->site->loggedInBrowser('anna');
        $ben = $this->site->loggedInBrowser('ben');

        self::assertStringContainsString('data-state=', $anna->get("/games/{$gameId}")->body);
        $anna->post("/games/{$gameId}/move", ['ply' => '0', 'move' => 'e2e4']);
        $ben->get("/games/{$gameId}");
        $ben->post("/games/{$gameId}/move", ['ply' => '1', 'move' => 'e5']);

        $state = json_decode($anna->get("/games/{$gameId}/state", ['since' => '1'])->body, true);
        self::assertIsArray($state);
        self::assertSame(2, $state['ply']);
        self::assertSame([['ply' => 2, 'uci' => 'e7e5', 'san' => 'e5']], $state['moves']);

        $pgn = $anna->get("/games/{$gameId}/pgn");
        self::assertStringContainsString('1. e4 e5 *', $pgn->body);
        self::assertStringContainsString('[White "anna"]', $pgn->body);
    }

    public function testTheBoardScriptGetsJsonAnswers(): void
    {
        $gameId = $this->startGame();
        $anna = $this->site->loggedInBrowser('anna');
        $anna->get("/games/{$gameId}");

        $refused = $anna->postJson("/games/{$gameId}/move", ['ply' => '0', 'move' => 'e2e5']);
        self::assertSame(409, $refused->status);
        self::assertStringContainsString('play.illegal_move', $refused->body);

        $played = $anna->postJson("/games/{$gameId}/move", ['ply' => '0', 'move' => 'e2e4']);
        self::assertSame(200, $played->status);
        self::assertStringContainsString('"ply":1', $played->body);
    }

    public function testAnOutsiderCanNotResignThroughThePage(): void
    {
        $gameId = $this->startGame();
        $cleo = $this->site->loggedInBrowser('cleo');
        $cleo->get('/play');
        $cleo->post("/games/{$gameId}/resign");

        self::assertTrue($this->play->gameRepository()->find($gameId)?->isRunning());
    }

    public function testWatchingNeedsThePlayerListSwitch(): void
    {
        $gameId = $this->startGame();
        $cleo = $this->site->loggedInBrowser('cleo');
        self::assertSame(200, $cleo->get("/games/{$gameId}")->status);

        $this->site->settings()->set('switch.player_list_visibility', false);
        self::assertSame(404, $cleo->get("/games/{$gameId}")->status);
        self::assertSame(404, $cleo->get('/games/watch')->status);
    }

    // --- Notices and emails (G-10, N-01, N-02, A34) ---

    public function testAMoveWhileTheOpponentWatchesLiveMakesNoNotice(): void
    {
        $gameId = $this->startGame();
        $ben = $this->site->loggedInBrowser('ben');
        $ben->get("/games/{$gameId}/state");
        $anna = $this->site->loggedInBrowser('anna');
        $anna->submit("/games/{$gameId}", "/games/{$gameId}/move", ['ply' => '0', 'move' => 'e2e4']);

        self::assertSame(0, $this->play->noticeRepository()->countUnread($this->userId('ben')));
    }

    public function testAnAwayPlayerGetsOneNoticeAndOneEmail(): void
    {
        $this->site->settings()->set('mail.move_email_default', MoveEmails::EACH_MOVE);
        $gameId = $this->startGame();
        $anna = $this->site->loggedInBrowser('anna');
        $anna->submit("/games/{$gameId}", "/games/{$gameId}/move", ['ply' => '0', 'move' => 'e2e4']);
        $benId = $this->userId('ben');
        self::assertSame(1, $this->play->noticeRepository()->countUnread($benId));

        $this->runJobs(time() + 120);
        $this->runJobs(time() + 240);

        $emailsToBen = array_filter($this->site->mailer->sent, fn ($email): bool => $email->toAddress === 'ben@example.test');
        self::assertCount(1, $emailsToBen);
        self::assertStringContainsString('/games/' . $gameId, array_values($emailsToBen)[0]->text);
    }

    // --- API (API-02) ---

    public function testTheGamesListUsesAnEtag(): void
    {
        $gameId = $this->startGame();
        $anna = $this->site->loggedInBrowser('anna');

        $first = $anna->get('/api/v1/games');
        $etag = $first->header('ETag');
        self::assertNotSame('', $etag);
        self::assertSame(304, $anna->getWithHeaders('/api/v1/games', ['If-None-Match' => $etag])->status);

        $anna->get("/games/{$gameId}");
        $anna->post("/games/{$gameId}/move", ['ply' => '0', 'move' => 'd2d4']);
        self::assertSame(200, $anna->getWithHeaders('/api/v1/games', ['If-None-Match' => $etag])->status);

        $state = json_decode($anna->get("/api/v1/games/{$gameId}", ['since' => '0'])->body, true);
        self::assertIsArray($state);
        self::assertSame([['ply' => 1, 'uci' => 'd2d4', 'san' => 'd4']], $state['moves']);
    }

    // --- Admin (ADM-04, A37) ---

    public function testAnAdminAdjudicatesWithAReasonInTheAuditLog(): void
    {
        $gameId = $this->startGame();
        $admin = $this->site->loggedInBrowser(InstalledSite::ADMIN_NAME, true);
        $admin->submit('/admin/games', "/admin/games/{$gameId}/adjudicate", ['result' => Game::RESULT_DRAW, 'reason' => 'player_left']);

        $game = $this->play->gameRepository()->find($gameId);
        self::assertSame(Game::RESULT_DRAW, $game?->result);
        self::assertSame('adjudicated_player_left', $game->endReason);
        self::assertSame('game.adjudicate', (new AuditLogRepository($this->site->database))->newest()[0]['action']);
    }

    public function testAnAdminSetsUpAGameFromAFen(): void
    {
        $admin = $this->site->loggedInBrowser(InstalledSite::ADMIN_NAME, true);
        $admin->submit('/admin/games', '/admin/games/new', [
            'white_id' => (string) $this->userId('anna'),
            'black_id' => (string) $this->userId('ben'),
            'days_per_move' => '3',
            'start_fen' => '4k3/8/8/8/8/8/4P3/4K3 w - - 0 1',
        ]);

        $game = $this->play->gameRepository()->listAll(Game::STATE_RUNNING)[0];
        self::assertSame('4k3/8/8/8/8/8/4P3/4K3 w - - 0 1', $game->startFen);
    }

    public function testThePlaySettingsAreSaved(): void
    {
        $admin = $this->site->loggedInBrowser(InstalledSite::ADMIN_NAME, true);
        $admin->submit('/admin/play', '/admin/play', [
            'max_days_per_move' => '10', 'rating_start' => '800', 'abort_rule' => 'loss',
            'open_days' => '3', 'direct_days' => '5', 'invite_days' => '2',
            'email_mode_digest' => 'yes', 'email_mode_off' => 'yes', 'default_email_mode' => 'digest',
            'email_delay_minutes' => '30', 'adjudication_reasons' => 'player_left, other',
        ]);

        self::assertSame(10, $this->play->challenges()->maximumDays());
        self::assertSame(800, $this->play->ratings()->startValue());
        self::assertSame('loss', $this->play->games()->abortRule());
        self::assertFalse($this->site->settings()->getBool('play.fen_in_direct', true));
    }

    // --- Helpers ---

    /** Anna (White) against Ben (Black), started directly. */
    private function startGame(): int
    {
        return $this->play->games()->start($this->userId('anna'), $this->userId('ben'), 3, true, '', time());
    }

    private function userId(string $username): int
    {
        return (int) $this->site->users()->findByUsernameOrEmail($username)?->id;
    }

    private function runJobs(int $now): void
    {
        $kernel = new Kernel($this->site->folder->path, now: $now, mailer: $this->site->mailer);
        $kernel->jobRunner()->runFromCron($now);
    }
}
