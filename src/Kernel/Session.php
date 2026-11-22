<?php

declare(strict_types=1);

namespace PhpChess\Kernel;

/**
 * The visitor's session: small values kept between requests (ARCHITECTURE.md §5).
 *
 * The cookie is HttpOnly (no JavaScript access), SameSite=Lax (not sent with
 * forms from other sites) and Secure on HTTPS. The cookie is limited to the
 * install's own path, so two installs on one domain never share a session.
 *
 * Tests create the session with startNative = false: the values are then kept
 * in this object only, and PHP's own session functions are never called.
 */
final class Session
{
    private const string COOKIE_NAME = 'phpchess_session';

    /** @var array<string, mixed> values when not using PHP's session (tests) */
    private array $memoryValues = [];

    private function __construct(private readonly bool $usesNativeSession)
    {
    }

    // --- Starting ---

    /** Starts PHP's session with safe cookie settings. */
    public static function start(string $cookiePath, bool $isHttps): self
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_name(self::COOKIE_NAME);
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => $cookiePath === '' ? '/' : $cookiePath . '/',
                'secure' => $isHttps,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            ini_set('session.use_strict_mode', '1');
            ini_set('session.use_only_cookies', '1');
            session_start();
        }

        return new self(true);
    }

    /** A session that lives in memory only, for tests and for the command line. */
    public static function inMemory(): self
    {
        return new self(false);
    }

    // --- Reading and writing values ---

    /** One value, or null when it is not set. */
    public function get(string $name): mixed
    {
        if ($this->usesNativeSession) {
            return $_SESSION[$name] ?? null;
        }

        return $this->memoryValues[$name] ?? null;
    }

    /** A value that must be a string; "" when missing or of another type. */
    public function getString(string $name): string
    {
        $value = $this->get($name);

        return is_string($value) ? $value : '';
    }

    /** Stores one value. */
    public function set(string $name, mixed $value): void
    {
        if ($this->usesNativeSession) {
            $_SESSION[$name] = $value;

            return;
        }
        $this->memoryValues[$name] = $value;
    }

    /** Removes one value. */
    public function remove(string $name): void
    {
        if ($this->usesNativeSession) {
            unset($_SESSION[$name]);

            return;
        }
        unset($this->memoryValues[$name]);
    }

    /**
     * Gives the session a new id, keeping its values. Called when someone's
     * rights change (installer start, later: login), so an old id is useless.
     */
    public function renewId(): void
    {
        if ($this->usesNativeSession && session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }
}
