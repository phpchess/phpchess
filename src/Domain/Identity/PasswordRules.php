<?php

declare(strict_types=1);

namespace PhpChess\Domain\Identity;

/**
 * The rules for user names, email addresses and passwords, in one place (ID-01).
 *
 * Used by registration, the password reset, the installer's admin step and
 * the admin's rename. Each check throws AccountRefused with a language key.
 * Passwords are hashed with Argon2id when PHP has it, else bcrypt (§9).
 */
final class PasswordRules
{
    public const int MINIMUM_PASSWORD_LENGTH = 10;

    /** User names: 3 to 40 letters, digits, ".", "-" or "_". */
    public static function checkUsername(string $username): void
    {
        if (preg_match('/^[A-Za-z0-9_.-]{3,40}$/', $username) !== 1) {
            throw new AccountRefused('account.username_rules');
        }
    }

    public static function checkEmail(string $email): void
    {
        if (strlen($email) > 190 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new AccountRefused('account.email_invalid');
        }
    }

    /** At least 10 characters, typed the same twice, and not the user name. */
    public static function checkNewPassword(string $password, string $passwordAgain, string $username): void
    {
        if (mb_strlen($password) < self::MINIMUM_PASSWORD_LENGTH) {
            throw new AccountRefused('account.password_too_short', ['length' => self::MINIMUM_PASSWORD_LENGTH]);
        }
        if (strcasecmp($password, $username) === 0) {
            throw new AccountRefused('account.password_is_username');
        }
        if ($password !== $passwordAgain) {
            throw new AccountRefused('account.passwords_differ');
        }
    }

    public static function hash(string $password): string
    {
        return password_hash($password, self::algorithm());
    }

    /** True when a stored hash was made with older settings and should be redone at the next login. */
    public static function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, self::algorithm());
    }

    private static function algorithm(): string
    {
        return defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
    }
}
