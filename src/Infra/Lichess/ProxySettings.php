<?php

declare(strict_types=1);

namespace PhpChess\Infra\Lichess;

use PhpChess\Domain\ActionRefused;
use PhpChess\Infra\System\SecretBox;
use PhpChess\Infra\System\SettingsRepository;

/**
 * How this server reaches the internet for Lichess (A94).
 *
 * Most servers connect directly. In schools and companies the server often
 * has to go through the network's proxy: a gateway with an address, a port
 * and sometimes a login. The admin sets it on the Lichess page.
 *
 * The address and port are stored as they are; the user name and password
 * only encrypted (SecretBox). The password is never shown again: the page
 * only says that one is saved.
 */
final class ProxySettings
{
    public const string MODE_SETTING = 'lichess.proxy_mode';
    public const string HOST_SETTING = 'lichess.proxy_host';
    public const string PORT_SETTING = 'lichess.proxy_port';
    public const string USER_SETTING = 'lichess.proxy_user';
    public const string PASSWORD_SETTING = 'lichess.proxy_password';

    public const string DIRECT = 'direct';
    public const string THROUGH_PROXY = 'proxy';

    public function __construct(
        private readonly SettingsRepository $settings,
        private readonly SecretBox $secrets,
    ) {
    }

    public function usesProxy(): bool
    {
        return $this->settings->getString(self::MODE_SETTING, self::DIRECT) === self::THROUGH_PROXY;
    }

    public function host(): string
    {
        return $this->settings->getString(self::HOST_SETTING);
    }

    public function port(): int
    {
        return $this->settings->getInt(self::PORT_SETTING, 8080);
    }

    public function hasLogin(): bool
    {
        return $this->settings->getString(self::USER_SETTING) !== '';
    }

    /** "proxy.school.local:8080", for messages; '' when connecting directly. */
    public function description(): string
    {
        return $this->usesProxy() ? $this->host() . ':' . $this->port() : '';
    }

    /**
     * What curl needs: null for a direct connection, else the proxy address
     * and, if set, "user:password".
     *
     * @return array{address: string, login: string}|null
     * @throws ActionRefused 'lichess.proxy_key_lost' when the login can't be decrypted
     */
    public function forCurl(): ?array
    {
        if (!$this->usesProxy()) {
            return null;
        }
        $login = '';
        if ($this->hasLogin()) {
            $user = $this->secrets->decrypt($this->settings->getString(self::USER_SETTING));
            $storedPassword = $this->settings->getString(self::PASSWORD_SETTING);
            $password = $storedPassword === '' ? '' : $this->secrets->decrypt($storedPassword);
            if ($user === null || $password === null) {
                throw new ActionRefused('lichess.proxy_key_lost');
            }
            $login = $user . ':' . $password;
        }

        return ['address' => 'http://' . $this->host() . ':' . $this->port(), 'login' => $login];
    }

    /**
     * Saves the admin's choice. An empty password keeps the saved one;
     * $clearLogin removes user name and password.
     *
     * @throws ActionRefused 'lichess.proxy_bad' when the address or port can't be right
     */
    public function save(string $mode, string $host, int $port, string $user, string $password, bool $clearLogin): void
    {
        if ($mode !== self::THROUGH_PROXY) {
            $this->settings->set(self::MODE_SETTING, self::DIRECT);

            return;
        }
        // A host name or IP address only: letters, digits, dots and dashes.
        if (preg_match('/^[A-Za-z0-9.-]{1,253}$/', $host) !== 1 || $port < 1 || $port > 65535) {
            throw new ActionRefused('lichess.proxy_bad');
        }
        $this->settings->set(self::MODE_SETTING, self::THROUGH_PROXY);
        $this->settings->set(self::HOST_SETTING, $host);
        $this->settings->set(self::PORT_SETTING, $port);

        if ($clearLogin) {
            $this->settings->set(self::USER_SETTING, '');
            $this->settings->set(self::PASSWORD_SETTING, '');
        } elseif ($user !== '') {
            $this->settings->set(self::USER_SETTING, $this->secrets->encrypt($user));
            if ($password !== '') {
                $this->settings->set(self::PASSWORD_SETTING, $this->secrets->encrypt($password));
            }
        }
    }
}
