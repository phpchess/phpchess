<?php

declare(strict_types=1);

namespace PhpChess\Tests\Lichess;

use PhpChess\Domain\ActionRefused;
use PhpChess\Domain\Lichess\StudyImport;
use PhpChess\Domain\System\FeatureSwitches;
use PhpChess\Infra\Lichess\LichessClient;
use PhpChess\Infra\System\SettingsRepository;
use PhpChess\Tests\Support\InstalledSite;
use PHPUnit\Framework\TestCase;

/**
 * The one door to lichess.org (C5-1, K9, A74, A79, A80).
 *
 * A fake transport stands in for curl and counts what would have been
 * sent, so nothing leaves the machine. The key promise: with the
 * "external content" switch off, not a single request goes out.
 */
final class LichessClientTest extends TestCase
{
    private InstalledSite $site;
    private SettingsRepository $settings;
    private string $lockFile;

    /** @var list<string> the addresses the fake transport was asked for */
    private array $sent = [];

    /** What the fake transport answers next. */
    private int $nextStatus = 200;
    private string $nextBody = '{"Top Rated": {"gameId": "abcdEFGH"}}';

    protected function setUp(): void
    {
        $this->site = new InstalledSite($this);
        $this->settings = $this->site->settings();
        $this->lockFile = (string) tempnam(sys_get_temp_dir(), 'lichess-lock');
    }

    protected function tearDown(): void
    {
        if (is_file($this->lockFile)) {
            unlink($this->lockFile);
        }
        if (isset($this->site)) {
            $this->site->remove();
        }
    }

    private function client(int $now = 1000000): LichessClient
    {
        return new LichessClient(
            new FeatureSwitches($this->settings),
            $this->settings,
            $this->lockFile,
            $now,
            function (string $url, string $accept): array {
                $this->sent[] = $url;

                return ['status' => $this->nextStatus, 'body' => $this->nextBody];
            },
        );
    }

    private function switchOn(): void
    {
        (new FeatureSwitches($this->settings))->set(FeatureSwitches::EXTERNAL_CONTENT, true);
    }

    // --- The switch (K9) ---

    public function testWithTheSwitchOffNothingIsSent(): void
    {
        $client = $this->client();
        foreach ([fn () => $client->gamePgn('abcdEFGH'), fn () => $client->userGamesPgn('anna', 5), fn () => $client->tvChannels()] as $call) {
            try {
                $call();
                self::fail('A request was allowed with the switch off.');
            } catch (ActionRefused $refusal) {
                self::assertSame('lichess.switched_off', $refusal->languageKey);
            }
        }

        self::assertSame([], $this->sent);
    }

    public function testTheSwitchIsOffByDefault(): void
    {
        self::assertFalse((new FeatureSwitches($this->settings))->isOn(FeatureSwitches::EXTERNAL_CONTENT));
    }

    public function testWithTheSwitchOnOnlyLichessIsAsked(): void
    {
        $this->switchOn();
        $this->nextBody = '[Result "*"]';

        $this->client()->gamePgn('abcdEFGH');
        $this->client()->userGamesPgn('anna', 5);

        self::assertSame([
            'https://lichess.org/game/export/abcdEFGH?clocks=false&evals=false&literate=false',
            'https://lichess.org/api/games/user/anna?max=5&clocks=false&evals=false&literate=false',
        ], $this->sent);
    }

    // --- The self-test (A80) ---

    public function testTheSelfTestWorksWithTheSwitchOffAndIsRemembered(): void
    {
        $client = $this->client();
        self::assertFalse($client->hasPassedSelfTest());

        self::assertTrue($client->selfTest());

        self::assertTrue($client->hasPassedSelfTest());
        self::assertSame(['https://lichess.org/api/tv/channels'], $this->sent);
    }

    public function testAFailedSelfTestIsRemembered(): void
    {
        $this->nextStatus = 500;

        self::assertFalse($this->client()->selfTest());
        self::assertFalse($this->client()->hasPassedSelfTest());
        self::assertSame('HTTP 500', $this->settings->getString(LichessClient::TEST_RESULT_SETTING));
    }

    // --- Lichess's rules (A79) ---

    public function testAfter429TheClientRestsAFullMinute(): void
    {
        $this->switchOn();
        $this->nextStatus = 429;
        try {
            $this->client(1000000)->tvChannels();
        } catch (ActionRefused) {
        }
        $this->nextStatus = 200;

        // 59 seconds later: still resting, nothing sent.
        try {
            $this->client(1000059)->tvChannels();
            self::fail('The client did not rest.');
        } catch (ActionRefused $refusal) {
            self::assertSame('lichess.resting', $refusal->languageKey);
        }
        self::assertCount(1, $this->sent);

        // A minute later: asking again is fine.
        $this->client(1000060)->tvChannels();
        self::assertCount(2, $this->sent);
    }

    public function testANotFoundAnswerIsRefusedPolitely(): void
    {
        $this->switchOn();
        $this->nextStatus = 404;

        $this->expectException(ActionRefused::class);
        $this->client()->gamePgn('abcdEFGH');
    }

    // --- Reading links (only lichess.org) ---

    public function testOnlyLichessGameLinksAreAccepted(): void
    {
        self::assertSame('abcdEFGH', StudyImport::gameIdFrom('abcdEFGH'));
        self::assertSame('abcdEFGH', StudyImport::gameIdFrom('https://lichess.org/abcdEFGH'));
        self::assertSame('abcdEFGH', StudyImport::gameIdFrom(' https://lichess.org/abcdEFGH/black#12 '));
        self::assertSame('abcdEFGH', StudyImport::gameIdFrom('https://lichess.org/abcdEFGHijkl'));

        self::assertNull(StudyImport::gameIdFrom('http://lichess.org/abcdEFGH'));
        self::assertNull(StudyImport::gameIdFrom('https://lichess.org.evil.example/abcdEFGH'));
        self::assertNull(StudyImport::gameIdFrom('https://evil.example/abcdEFGH'));
        self::assertNull(StudyImport::gameIdFrom('https://lichess.org/abcd/../../x'));
        self::assertNull(StudyImport::gameIdFrom(''));
    }
}
