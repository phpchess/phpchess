<?php

declare(strict_types=1);

namespace PhpChess\Tests\Play;

use PhpChess\Domain\Play\Challenge;
use PhpChess\Kernel\PlayParts;
use PhpChess\Tests\Support\InstalledSite;
use PhpChess\Tests\Support\TestBrowser;
use PHPUnit\Framework\TestCase;

/**
 * The site-wide news poll (B21, A63): GET /news tells a member about a
 * timed challenge for them, an accepted challenge of theirs, and unread
 * messages; the pop-up and the poll show on ordinary pages only.
 */
final class SiteNewsTest extends TestCase
{
    private InstalledSite $site;
    private PlayParts $play;
    private int $annaId;
    private int $benId;

    protected function setUp(): void
    {
        $this->site = new InstalledSite($this);
        $this->play = new PlayParts($this->site->database, $this->site->settings());
        $this->annaId = $this->site->createUser('annanews');
        $this->benId = $this->site->createUser('bennews');
    }

    protected function tearDown(): void
    {
        if (isset($this->site)) {
            $this->site->remove();
        }
    }

    public function testATimedChallengeForMeComesWithTheNews(): void
    {
        $anna = $this->site->loggedInBrowser('annanews');
        $challengeId = $this->play->challenges()->createDirect($this->benId, $this->annaId, Challenge::COLOUR_WHITE, 3, true, '', time(), '5+3');
        $news = $this->news($anna);

        self::assertIsArray($news['challenge']);
        self::assertSame($challengeId, $news['challenge']['id']);
        self::assertSame('bennews', $news['challenge']['from']);
        self::assertSame('5+3', $news['challenge']['time_control']);
        self::assertSame(15000, $news['next_poll_ms']);
    }

    public function testACorrespondenceChallengeDoesNotPopUp(): void
    {
        $this->play->challenges()->createDirect($this->benId, $this->annaId, Challenge::COLOUR_RANDOM, 3, true, '', time());

        self::assertNull($this->news($this->site->loggedInBrowser('annanews'))['challenge']);
    }

    public function testTheAdminSetsTheIntervalWithinLimits(): void
    {
        $this->site->settings()->set('site_poll.seconds', 1);

        self::assertSame(5000, $this->news($this->site->loggedInBrowser('annanews'))['next_poll_ms']);
    }

    public function testThePollRunsOnOrdinaryPagesButNotInAGame(): void
    {
        $anna = $this->site->loggedInBrowser('annanews');
        $gameId = $this->play->games()->start($this->annaId, $this->benId, 3, true, '', time());

        self::assertStringContainsString('site-news.js', $anna->get('/')->body);
        self::assertStringNotContainsString('site-news.js', $anna->get("/games/{$gameId}")->body);
        self::assertSame(303, $this->site->browser()->get('/news')->status);
    }

    // --- Helpers ---

    /** @return array<string, mixed> */
    private function news(TestBrowser $browser): array
    {
        $response = $browser->get('/news');
        self::assertSame(200, $response->status);
        $answer = json_decode($response->body, true);
        self::assertIsArray($answer);

        /** @var array<string, mixed> $answer */
        return $answer;
    }
}
