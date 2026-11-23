<?php

declare(strict_types=1);

namespace PhpChess\Infra\Notify;

use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\VAPID;
use Minishlink\WebPush\WebPush;
use Nyholm\Psr7\Factory\Psr17Factory;
use PhpChess\Domain\Notify\PushServices;
use PhpChess\Infra\System\SettingsRepository;
use RuntimeException;
use Throwable;

/**
 * Sends Web Push messages through the browser makers' push services (N-03, A18).
 *
 * The web-push library encrypts the message for the one browser it is
 * meant for, and signs the request with our VAPID key pair, so the push
 * service knows it comes from this site. The key pair is made once, the
 * first time it is needed, and kept in the settings ("push.vapid_public"
 * and "push.vapid_private"). The browser gets the public key when it
 * subscribes (PushController).
 */
final class WebPushSender implements PushSender
{
    private ?WebPush $webPush = null;

    public function __construct(private readonly SettingsRepository $settings)
    {
    }

    public function send(array $subscription, string $payloadJson): string
    {
        // Checked again here: only the browser makers' push services are ever contacted.
        if (!PushServices::isAllowed($subscription['endpoint'])) {
            return self::GONE;
        }
        try {
            $report = $this->webPush()->sendOneNotification(
                Subscription::create([
                    'endpoint' => $subscription['endpoint'],
                    'publicKey' => $subscription['public_key'],
                    'authToken' => $subscription['auth_secret'],
                    'contentEncoding' => 'aes128gcm',
                ]),
                $payloadJson,
            );
        } catch (Throwable) {
            return self::FAILED;
        }
        if ($report->isSuccess()) {
            return self::SENT;
        }

        return $report->isSubscriptionExpired() ? self::GONE : self::FAILED;
    }

    /** The site's public VAPID key, for the browser's subscribe call; made on first use. */
    public static function publicKey(SettingsRepository $settings): string
    {
        self::ensureKeys($settings);

        return $settings->getString('push.vapid_public');
    }

    private function webPush(): WebPush
    {
        if ($this->webPush !== null) {
            return $this->webPush;
        }
        self::ensureKeys($this->settings);
        $factory = new Psr17Factory();

        return $this->webPush = new WebPush(
            ['VAPID' => [
                'subject' => $this->subject(),
                'publicKey' => $this->settings->getString('push.vapid_public'),
                'privateKey' => $this->settings->getString('push.vapid_private'),
            ]],
            ['TTL' => 86400],
            new CurlHttpClient(),
            $factory,
            $factory,
        );
    }

    /** Who to contact about our pushes: the site's address, or its mail address. */
    private function subject(): string
    {
        $siteUrl = $this->settings->getString('site.base_url');
        if (str_starts_with($siteUrl, 'https://')) {
            return $siteUrl;
        }

        return 'mailto:' . $this->settings->getString('mail.from_address', 'admin@example.org');
    }

    /** Makes the VAPID key pair once and keeps it in the settings. */
    private static function ensureKeys(SettingsRepository $settings): void
    {
        self::findOpenSslConfigOnWindows();
        if ($settings->getString('push.vapid_public') !== '') {
            return;
        }
        $keys = VAPID::createVapidKeys();
        $publicKey = $keys['publicKey'] ?? null;
        $privateKey = $keys['privateKey'] ?? null;
        if (!is_string($publicKey) || !is_string($privateKey)) {
            throw new RuntimeException('The VAPID keys for push could not be made.');
        }
        $settings->set('push.vapid_public', $publicKey);
        $settings->set('push.vapid_private', $privateKey);
    }

    /**
     * On Windows (XAMPP), OpenSSL can only make keys when it finds its
     * config file. Linux hosts have it already, so this does nothing there.
     */
    private static function findOpenSslConfigOnWindows(): void
    {
        if (PHP_OS_FAMILY !== 'Windows' || getenv('OPENSSL_CONF') !== false) {
            return;
        }
        foreach ([dirname(PHP_BINARY), PHP_BINDIR, dirname(php_ini_loaded_file() ?: '')] as $phpFolder) {
            $config = $phpFolder . '/extras/ssl/openssl.cnf';
            if ($phpFolder !== '' && is_file($config)) {
                putenv('OPENSSL_CONF=' . $config);

                return;
            }
        }
    }
}
