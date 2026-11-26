<?php

declare(strict_types=1);

namespace PhpChess\Tests\Lichess;

use PhpChess\Domain\ActionRefused;
use PhpChess\Infra\Lichess\ProxySettings;
use PhpChess\Infra\System\SecretBox;
use PhpChess\Tests\Support\InstalledSite;
use PHPUnit\Framework\TestCase;

/**
 * The encrypted proxy login (A94): SecretBox and ProxySettings.
 */
final class ProxyAndSecretsTest extends TestCase
{
    private InstalledSite $site;
    private string $keyFile;

    protected function setUp(): void
    {
        $this->site = new InstalledSite($this);
        $this->keyFile = $this->site->folder->path . '/config/secret.key';
    }

    protected function tearDown(): void
    {
        if (isset($this->site)) {
            $this->site->remove();
        }
    }

    public function testASecretComesBackAndIsNotStoredAsItIs(): void
    {
        $box = new SecretBox($this->keyFile);
        $stored = $box->encrypt('my proxy password');

        self::assertStringNotContainsString('my proxy password', $stored);
        self::assertNotSame($stored, $box->encrypt('my proxy password'), 'a new nonce every time');
        self::assertSame('my proxy password', $box->decrypt($stored));
        self::assertFileExists($this->keyFile);
    }

    public function testAChangedTextOrALostKeyGivesNothing(): void
    {
        $box = new SecretBox($this->keyFile);
        $stored = $box->encrypt('secret');

        self::assertNull($box->decrypt(substr($stored, 0, -4) . 'AAAA'));
        unlink($this->keyFile);
        self::assertNull($box->decrypt($stored));
    }

    public function testTheProxyLoginIsSavedEncryptedAndUsedForCurl(): void
    {
        $proxy = new ProxySettings($this->site->settings(), new SecretBox($this->keyFile));
        $proxy->save(ProxySettings::THROUGH_PROXY, 'proxy.school.local', 3128, 'teacher', 'pa:ss word', false);

        // Nothing readable in the database.
        $stored = $this->site->settings()->getString(ProxySettings::PASSWORD_SETTING) . $this->site->settings()->getString(ProxySettings::USER_SETTING);
        self::assertStringNotContainsString('pa:ss', $stored);
        self::assertStringNotContainsString('teacher', $stored);

        self::assertSame(['address' => 'http://proxy.school.local:3128', 'login' => 'teacher:pa:ss word'], $proxy->forCurl());

        // An empty password field keeps the saved password.
        $proxy->save(ProxySettings::THROUGH_PROXY, 'proxy.school.local', 3128, 'teacher', '', false);
        self::assertSame('teacher:pa:ss word', $proxy->forCurl()['login'] ?? '');
    }

    public function testALostKeyIsSaidPlainly(): void
    {
        $proxy = new ProxySettings($this->site->settings(), new SecretBox($this->keyFile));
        $proxy->save(ProxySettings::THROUGH_PROXY, 'proxy.school.local', 3128, 'teacher', 'secret', false);
        unlink($this->keyFile);

        $this->expectException(ActionRefused::class);
        $this->expectExceptionMessage('lichess.proxy_key_lost');
        $proxy->forCurl();
    }

    public function testABadProxyAddressIsRefused(): void
    {
        $proxy = new ProxySettings($this->site->settings(), new SecretBox($this->keyFile));

        $this->expectException(ActionRefused::class);
        $proxy->save(ProxySettings::THROUGH_PROXY, 'http://evil/"', 8080, '', '', false);
    }

    public function testDirectNeedsNoProxy(): void
    {
        $proxy = new ProxySettings($this->site->settings(), new SecretBox($this->keyFile));
        $proxy->save(ProxySettings::DIRECT, '', 0, '', '', false);

        self::assertNull($proxy->forCurl());
    }

    public function testThePasswordIsNeverShownOnTheAdminPage(): void
    {
        $admin = $this->site->loggedInBrowser(InstalledSite::ADMIN_NAME, true);
        $admin->get('/admin/lichess');
        $admin->post('/admin/lichess/proxy', ['proxy_mode' => 'proxy', 'proxy_host' => 'proxy.school.local', 'proxy_port' => '3128', 'proxy_user' => 'teacher', 'proxy_password' => 'TopSecret9']);

        $page = $admin->get('/admin/lichess')->body;
        self::assertStringContainsString('proxy.school.local', $page);
        self::assertStringNotContainsString('TopSecret9', $page);
        self::assertStringNotContainsString('teacher', $page);
    }
}
