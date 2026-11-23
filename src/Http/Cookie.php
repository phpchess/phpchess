<?php

declare(strict_types=1);

namespace PhpChess\Http;

/**
 * One cookie a Response sets (or removes), for example the remember-me cookie (ID-05).
 *
 * Always HttpOnly (JavaScript can't read it) and SameSite=Lax (not sent with
 * forms from other sites); Secure when the site runs on HTTPS. The path keeps
 * it to this install. The session cookie is not one of these: PHP sets it.
 */
final class Cookie
{
    /**
     * @param int $expiresAt  Unix time; 0 = until the browser closes; a time in the past removes it
     */
    public function __construct(
        public readonly string $name,
        public readonly string $value,
        public readonly int $expiresAt,
        public readonly string $path,
        public readonly bool $secure,
    ) {
    }

    /** A cookie that tells the browser to forget the cookie of this name. */
    public static function removal(string $name, string $path, bool $secure): self
    {
        return new self($name, '', 1, $path, $secure);
    }
}
