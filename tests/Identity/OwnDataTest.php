<?php

declare(strict_types=1);

namespace PhpChess\Tests\Identity;

use PhpChess\Domain\Play\Game;
use PhpChess\Infra\Identity\OwnDataRepository;
use PhpChess\Infra\Notify\PushSubscriptionRepository;
use PhpChess\Kernel\PlayParts;
use PhpChess\Tests\Support\InstalledSite;
use PhpChess\Tests\Support\Values;
use PhpChess\Tests\Support\TestBrowser;
use PHPUnit\Framework\TestCase;

/**
 * The member's own data (ID-11): the export holds all of it, without
 * secrets, and deleting the account removes it, keeping the opponents'
 * games without a name.
 */
final class OwnDataTest extends TestCase
{
    private InstalledSite $site;
    private PlayParts $play;
    private int $annaId;
    private int $benId;

    protected function setUp(): void
    {
        $this->site = new InstalledSite($this);
        $this->play = new PlayParts($this->site->database, $this->site->settings());
        $this->annaId = $this->site->createUser('anna');
        $this->benId = $this->site->createUser('ben');
    }

    protected function tearDown(): void
    {
        if (isset($this->site)) {
            $this->site->remove();
        }
    }

    public function testTheExportContainsAllOfTheMembersData(): void
    {
        $anna = $this->fillAnnasData();
        $export = $anna->get('/account/data/export');
        self::assertSame(200, $export->status);
        self::assertStringContainsString('attachment', $export->header('Content-Disposition'));
        $data = Values::jsonObject($export->body);
        $rows = fn (string $table): array => Values::rows($data[$table] ?? null);

        self::assertSame('anna', $rows('account')[0]['username'] ?? null);
        self::assertSame('Ask me about endgames.', $rows('profiles')[0]['about'] ?? null);
        self::assertCount(1, $rows('buddies'));
        self::assertSame(['Hello Ben', 'Hello Anna'], array_column($rows('messages'), 'body'));
        self::assertCount(1, $rows('game_chat'));
        self::assertCount(2, $rows('games'));
        self::assertCount(1, $rows('game_moves'));
        self::assertCount(1, $rows('api_tokens'));
        self::assertCount(1, $rows('push_subscriptions'));
        self::assertCount(1, $rows('user_preferences'));

        // Secrets stay out, even hashed.
        self::assertStringNotContainsString('password_hash', $export->body);
        self::assertStringNotContainsString('token_hash', $export->body);
        self::assertStringNotContainsString('auth_secret', $export->body);
    }

    public function testDeletingTheAccountRemovesTheDataAndKeepsTheOpponentsGames(): void
    {
        $anna = $this->fillAnnasData();
        $ownData = new OwnDataRepository($this->site->database);
        self::assertGreaterThan(0, $ownData->countPersonalRows($this->annaId));

        $anna->get('/account/data');
        $anna->post('/account/delete', ['password' => 'wrong password 123', 'confirm' => 'yes']);
        self::assertGreaterThan(0, $ownData->countPersonalRows($this->annaId));

        $anna->get('/account/data');
        $anna->post('/account/delete', ['password' => InstalledSite::PASSWORD, 'confirm' => 'yes']);
        self::assertSame(0, $ownData->countPersonalRows($this->annaId));

        $account = $this->site->users()->findById($this->annaId);
        self::assertNotNull($account);
        self::assertSame('deleted-' . $this->annaId, $account->username);
        self::assertSame('', $account->email);
        self::assertFalse($this->site->users()->usernameExists('anna'));

        // Ben keeps both games; the running one was resigned, so he isn't left waiting.
        self::assertCount(2, $this->play->gameRepository()->listForPlayer($this->benId, Game::STATE_FINISHED));
        self::assertStringNotContainsString('Log out', $anna->get('/')->body);
        $again = $this->site->browser();
        $again->submit('/login', '/login', ['name_or_email' => 'deleted-' . $this->annaId, 'password' => InstalledSite::PASSWORD]);
        self::assertStringNotContainsString('Log out', $again->get('/')->body);
    }

    /** Anna with a profile, a buddy, messages, chat, two games, a token and a push device; returns her browser. */
    private function fillAnnasData(): TestBrowser
    {
        $anna = $this->site->loggedInBrowser('anna');
        $anna->submit('/account/profile', '/account/profile', ['about' => 'Ask me about endgames.']);
        $anna->get('/players/ben');
        $anna->post('/players/ben/buddy');
        $anna->submit('/messages/ben', '/messages/ben', ['body' => 'Hello Ben']);
        $this->site->loggedInBrowser('ben')->submit('/messages/anna', '/messages/anna', ['body' => 'Hello Anna']);

        $finished = $this->play->games()->start($this->annaId, $this->benId, 3, true, '', time());
        $this->play->games()->resign($finished, $this->benId, time());
        $running = $this->play->games()->start($this->annaId, $this->benId, 3, true, '', time());
        $this->play->games()->move($running, $this->annaId, 0, 'e2e4', time());
        $anna->submit("/games/{$running}", "/games/{$running}/chat", ['text' => 'hello']);

        $anna->submit('/account/devices', '/account/devices', ['device_name' => 'Phone']);
        $anna->submit('/account/notifications', '/account/notifications', ['notify_games' => 'none', 'notify_challenges' => 'email', 'notify_messages' => 'email']);
        (new PushSubscriptionRepository($this->site->database))->save($this->annaId, 'https://fcm.googleapis.com/fcm/send/a', str_repeat('A', 87), str_repeat('B', 22));

        return $anna;
    }
}
