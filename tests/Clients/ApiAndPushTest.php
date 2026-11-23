<?php

declare(strict_types=1);

namespace PhpChess\Tests\Clients;

use PhpChess\Domain\Notify\NotificationChoices;
use PhpChess\Domain\System\FeatureSwitches;
use PhpChess\Infra\Identity\PreferenceRepository;
use PhpChess\Infra\Notify\PushSubscriptionRepository;
use PhpChess\Kernel\Kernel;
use PhpChess\Kernel\PlayParts;
use PhpChess\Tests\Support\InstalledSite;
use PhpChess\Tests\Support\Values;
use PhpChess\Tests\Support\TestBrowser;
use PHPUnit\Framework\TestCase;

/**
 * The clients (C2-2): API v1 with access tokens (API-01, ID-10), Web Push
 * with quiet hours (N-03, N-04) and the installable app (UI-05, UI-08).
 *
 * The push tests use a sender that only records (RecordingPushSender):
 * with the switch off, not even that one is called, so the server makes
 * no outgoing connection (K9).
 */
final class ApiAndPushTest extends TestCase
{
    private const string ENDPOINT = 'https://fcm.googleapis.com/fcm/send/abc123';

    private InstalledSite $site;
    private PlayParts $play;

    protected function setUp(): void
    {
        $this->site = new InstalledSite($this);
        $this->play = new PlayParts($this->site->database, $this->site->settings());
        $this->site->createUser('anna');
        $this->site->createUser('ben');
    }

    protected function tearDown(): void
    {
        if (isset($this->site)) {
            $this->site->remove();
        }
    }

    // --- The API token life cycle (ID-10) ---

    public function testAnApiTokenWorksUntilItIsRevoked(): void
    {
        $anna = $this->site->loggedInBrowser('anna');
        $token = $this->createToken($anna, 'My phone');
        self::assertStringStartsWith('pc6_', $token);

        // Shown once: the list afterwards has the device but never the token.
        $list = $anna->get('/account/devices')->body;
        self::assertStringContainsString('My phone', $list);
        self::assertStringNotContainsString($token, $list);

        // An app: no cookies, no session, no CSRF token; reads and changes work.
        $app = $this->site->browser();
        $me = $app->apiWithToken('GET', '/api/v1/me', $token);
        self::assertSame(200, $me->status);
        self::assertSame('anna', Values::jsonObject($me->body)['username'] ?? null);
        $sent = $app->apiWithToken('POST', '/api/v1/messages/ben', $token, ['body' => 'Sent from the app']);
        self::assertSame(201, $sent->status);
        self::assertStringContainsString('last used', $anna->get('/account/devices')->body);

        // Revoked: it stops working at once.
        $tokenId = Values::int($this->site->database->fetchValue('SELECT id FROM ' . $this->site->database->table('api_tokens')));
        $anna->post("/account/devices/{$tokenId}/revoke");
        self::assertSame(401, $app->apiWithToken('GET', '/api/v1/me', $token)->status);
    }

    public function testAWrongTokenOrSomeoneElsesRevokeDoesNothing(): void
    {
        $token = $this->createToken($this->site->loggedInBrowser('anna'), 'Tablet');
        $app = $this->site->browser();
        self::assertSame(401, $app->apiWithToken('GET', '/api/v1/me', 'pc6_' . str_repeat('0', 48))->status);
        self::assertSame(401, $app->apiWithToken('GET', '/api/v1/me', 'not-a-token')->status);

        $tokenId = Values::int($this->site->database->fetchValue('SELECT id FROM ' . $this->site->database->table('api_tokens')));
        $ben = $this->site->loggedInBrowser('ben');
        $ben->get('/account/devices');
        $ben->post("/account/devices/{$tokenId}/revoke");
        self::assertSame(200, $app->apiWithToken('GET', '/api/v1/me', $token)->status);
    }

    public function testTheWebsiteStillNeedsItsCsrfTokenWithoutAnApiToken(): void
    {
        $anna = $this->site->loggedInBrowser('anna');
        self::assertSame(419, $anna->post('/api/v1/messages/ben', ['body' => 'x'], false)->status);
    }

    public function testTheApiListsChallengesAndAcceptsOne(): void
    {
        $this->site->loggedInBrowser('ben')->submit('/play', '/challenges', ['kind' => 'open', 'colour' => 'white', 'days_per_move' => '3']);
        $token = $this->createToken($this->site->loggedInBrowser('anna'), 'Phone');
        $app = $this->site->browser();

        $challenges = Values::rows(Values::jsonObject($app->apiWithToken('GET', '/api/v1/challenges', $token)->body)['challenges'] ?? null);
        self::assertCount(1, $challenges);
        $accepted = $app->apiWithToken('POST', '/api/v1/challenges/' . Values::int($challenges[0]['id'] ?? null) . '/accept', $token);
        self::assertSame(200, $accepted->status);
        self::assertGreaterThan(0, Values::int(Values::jsonObject($accepted->body)['game_id'] ?? null));
    }

    // --- Web Push (N-03, N-04, K9) ---

    public function testWithThePushSwitchOffNothingIsSent(): void
    {
        $this->subscribeBenToPush();
        $this->switches()->set(FeatureSwitches::PUSH_NOTIFICATIONS, false);
        // Ben has a device and wants push, and Anna moves: with the switch off, still nothing goes out.
        $gameId = $this->play->games()->start($this->userId('anna'), $this->userId('ben'), 3, true, '', time());
        $this->play->games()->move($gameId, $this->userId('anna'), 0, 'e2e4', time());

        $lines = $this->runJobs(time() + 60);
        self::assertSame([], $this->site->pushSender->sent);
        self::assertStringContainsString('push is switched off', implode("\n", $lines));
        self::assertSame('', $this->site->settings()->getString('push.vapid_private'));
    }

    public function testAMoveIsPushedToTheOpponentsDevices(): void
    {
        $this->subscribeBenToPush();
        $gameId = $this->play->games()->start($this->userId('anna'), $this->userId('ben'), 3, true, '', time());
        $this->play->games()->move($gameId, $this->userId('anna'), 0, 'e2e4', time());

        $this->runJobs(time() + 60);
        self::assertCount(1, $this->site->pushSender->sent);
        $push = $this->site->pushSender->sent[0];
        self::assertSame(self::ENDPOINT, $push['endpoint']);
        self::assertStringContainsString('/games/' . $gameId, Values::string($push['payload']['url'] ?? ''));
        self::assertSame(1, $push['payload']['badge'] ?? null);

        // Pushed once only.
        $this->runJobs(time() + 180);
        self::assertCount(1, $this->site->pushSender->sent);
    }

    public function testQuietHoursHoldPushAndAGoneDeviceIsForgotten(): void
    {
        $this->subscribeBenToPush();
        $benId = $this->userId('ben');
        $preferences = new PreferenceRepository($this->site->database);
        $choices = $preferences->notificationChoices($benId);
        $hourNow = (int) gmdate('G');
        $choices['quiet_from'] = $hourNow;
        $choices['quiet_until'] = ($hourNow + 2) % 24;
        $choices['time_zone'] = 'UTC';
        $preferences->saveNotificationChoices($benId, $choices);

        $gameId = $this->play->games()->start($this->userId('anna'), $benId, 3, true, '', time());
        $this->play->games()->move($gameId, $this->userId('anna'), 0, 'e2e4', time());
        $this->runJobs(time() + 60);
        self::assertSame([], $this->site->pushSender->sent);

        // Quiet hours over: it goes out; the push service says the device is gone.
        $this->site->pushSender->answer = 'gone';
        $this->runJobs(time() + 3 * 3600);
        self::assertCount(1, $this->site->pushSender->sent);
        self::assertSame([], (new PushSubscriptionRepository($this->site->database))->listFor($benId));
    }

    public function testQuietHoursWrapPastMidnight(): void
    {
        $tenPm = (new \DateTimeImmutable('2027-01-15 22:30 UTC'))->getTimestamp();
        $sixAm = (new \DateTimeImmutable('2027-01-15 06:30 UTC'))->getTimestamp();
        $noon = (new \DateTimeImmutable('2027-01-15 12:00 UTC'))->getTimestamp();
        self::assertTrue(NotificationChoices::isQuietHour(22, 7, 'UTC', $tenPm));
        self::assertTrue(NotificationChoices::isQuietHour(22, 7, 'UTC', $sixAm));
        self::assertFalse(NotificationChoices::isQuietHour(22, 7, 'UTC', $noon));
        self::assertFalse(NotificationChoices::isQuietHour(null, 7, 'UTC', $sixAm));
        // 06:30 UTC is 07:30 in Berlin in winter: no longer quiet there.
        self::assertFalse(NotificationChoices::isQuietHour(22, 7, 'Europe/Berlin', $sixAm));
    }

    public function testTheSubscribeRouteChecksTheSubscriptionAndTheSwitch(): void
    {
        $this->switches()->set(FeatureSwitches::PUSH_NOTIFICATIONS, true);
        $ben = $this->site->loggedInBrowser('ben');
        $ben->get('/account/notifications');

        self::assertSame(422, $ben->post('/account/push/subscribe', ['endpoint' => 'https://intranet.school.example/admin', 'p256dh' => str_repeat('A', 87), 'auth' => str_repeat('B', 22)])->status);
        self::assertSame(200, $ben->post('/account/push/subscribe', ['endpoint' => self::ENDPOINT, 'p256dh' => str_repeat('A', 87), 'auth' => str_repeat('B', 22)])->status);
        self::assertCount(1, (new PushSubscriptionRepository($this->site->database))->listFor($this->userId('ben')));

        $this->switches()->set(FeatureSwitches::PUSH_NOTIFICATIONS, false);
        self::assertSame(404, $ben->post('/account/push/subscribe', ['endpoint' => self::ENDPOINT, 'p256dh' => 'x', 'auth' => 'y'])->status);
    }

    // --- The installable app (UI-05, UI-08) ---

    public function testTheManifestAndServiceWorkerComeFromThisSite(): void
    {
        $browser = $this->site->browser();
        $manifest = json_decode($browser->get('/manifest.webmanifest')->body, true);
        self::assertIsArray($manifest);
        self::assertSame('/chess/', $manifest['start_url']);
        self::assertSame('standalone', $manifest['display']);

        $worker = $browser->get('/sw.js');
        self::assertSame(200, $worker->status);
        self::assertStringContainsString('text/javascript', $worker->header('Content-Type'));
        self::assertSame('/chess/', $worker->header('Service-Worker-Allowed'));
        self::assertDoesNotMatchRegularExpression('#https?://#', $worker->body);
        self::assertSame(200, $browser->get('/offline')->status);
        self::assertStringContainsString('rel="manifest"', $browser->get('/')->body);
    }

    // --- Helpers ---

    private function createToken(TestBrowser $browser, string $deviceName): string
    {
        $page = $browser->submit('/account/devices', '/account/devices', ['device_name' => $deviceName])->body;
        // The new token is shown on the answer page, once.
        return Values::firstMatch('/(pc6_[0-9a-f]{48})/', $page);
    }

    /** Push on, Ben has one device, and wants game news by push. */
    private function subscribeBenToPush(): void
    {
        $this->switches()->set(FeatureSwitches::PUSH_NOTIFICATIONS, true);
        $benId = $this->userId('ben');
        (new PushSubscriptionRepository($this->site->database))->save($benId, self::ENDPOINT, str_repeat('A', 87), str_repeat('B', 22));
        $preferences = new PreferenceRepository($this->site->database);
        $choices = $preferences->notificationChoices($benId);
        $choices['games'] = NotificationChoices::PUSH;
        $preferences->saveNotificationChoices($benId, $choices);
    }

    private function switches(): FeatureSwitches
    {
        return new FeatureSwitches($this->site->settings());
    }

    private function userId(string $username): int
    {
        return (int) $this->site->users()->findByUsernameOrEmail($username)?->id;
    }

    /**
     * @return list<string>
     */
    private function runJobs(int $now): array
    {
        $kernel = new Kernel($this->site->folder->path, now: $now, mailer: $this->site->mailer, pushSender: $this->site->pushSender);

        return $kernel->jobRunner()->runFromCron($now);
    }
}
