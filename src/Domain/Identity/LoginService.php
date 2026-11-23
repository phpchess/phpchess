<?php

declare(strict_types=1);

namespace PhpChess\Domain\Identity;

use PhpChess\Infra\Identity\LoginAttemptRepository;
use PhpChess\Infra\Identity\TokenRepository;
use PhpChess\Infra\Identity\UserRepository;
use PhpChess\Infra\System\RateLimiter;

/**
 * Checks a login and handles remember-me tokens (ID-04, ID-05).
 *
 * Called by the Authentication class in the Kernel, which keeps the logged-in
 * account in the session. This class only decides whether a name and
 * password are right, and whether a remember-me token is valid.
 *
 * Throttling (ID-04): at most 5 tries per account name and 20 per address in
 * 15 minutes. Every try is recorded in login_attempts.
 */
final class LoginService
{
    private const int TRIES_PER_NAME = 5;
    private const int TRIES_PER_ADDRESS = 20;
    private const int TRY_WINDOW_SECONDS = 900;

    /** Remember-me lasts 30 days, and a new token is issued every time it is used. */
    public const int REMEMBER_ME_SECONDS = 2592000;

    public function __construct(
        private readonly UserRepository $users,
        private readonly TokenRepository $tokens,
        private readonly LoginAttemptRepository $attempts,
        private readonly RateLimiter $rateLimiter,
    ) {
    }

    /**
     * Checks a user name (or email) and password. Returns the account when
     * they are right and the account may log in. Throws AccountRefused for:
     * too many tries, a wrong name or password (one message for both), and
     * an account that is pending, disabled or archived.
     */
    public function checkLogin(string $nameOrEmail, string $password, string $ipAddress, int $now): User
    {
        $nameBucket = 'login:name:' . mb_strtolower($nameOrEmail);
        $addressBucket = 'login:ip:' . $ipAddress;
        $nameAllowed = $this->rateLimiter->hit($nameBucket, self::TRIES_PER_NAME, self::TRY_WINDOW_SECONDS, $now);
        $addressAllowed = $this->rateLimiter->hit($addressBucket, self::TRIES_PER_ADDRESS, self::TRY_WINDOW_SECONDS, $now);
        if (!$nameAllowed || !$addressAllowed) {
            throw new AccountRefused('login.too_many_tries', ['minutes' => intdiv(self::TRY_WINDOW_SECONDS, 60)]);
        }

        $user = $this->users->findByUsernameOrEmail($nameOrEmail);
        // For an unknown name a fresh hash of a random password is checked, so a
        // wrong name takes as long as a wrong password and can't be told apart.
        $hash = $user === null ? PasswordRules::hash(bin2hex(random_bytes(8))) : $this->users->passwordHashFor($user->id);
        $passwordIsRight = password_verify($password, $hash) && $user !== null;

        $this->attempts->record($nameOrEmail, $user?->id, $ipAddress, $passwordIsRight);
        if (!$passwordIsRight) {
            throw new AccountRefused('login.wrong_name_or_password');
        }

        if (in_array($user->state, [User::STATE_DISABLED, User::STATE_ARCHIVED, User::STATE_DELETED], true)) {
            throw new AccountRefused('login.account_blocked');
        }
        if ($user->state === User::STATE_PENDING) {
            throw new AccountRefused('login.account_pending');
        }

        if (PasswordRules::needsRehash($hash)) {
            $this->users->setPasswordHash($user->id, PasswordRules::hash($password));
        }
        $this->users->markLoggedIn($user->id);

        return $user;
    }

    // --- Remember me (ID-05) ---

    /** Creates a remember-me token for the cookie. */
    public function createRememberMeToken(int $userId): string
    {
        // Several devices may each keep their own token, so older ones stay.
        return $this->tokens->createToken($userId, TokenRepository::TYPE_REMEMBER_ME, self::REMEMBER_ME_SECONDS, false);
    }

    /**
     * Uses a remember-me token from the cookie. Returns the active account and
     * a new token to put in the cookie (the old one is used up: rotation), or
     * null when the token is not valid or the account may not log in.
     *
     * @return array{user: User, newToken: string}|null
     */
    public function useRememberMeToken(string $token): ?array
    {
        $userId = $this->tokens->useToken($token, TokenRepository::TYPE_REMEMBER_ME);
        if ($userId === null) {
            return null;
        }

        $user = $this->users->findById($userId);
        if ($user === null || !$user->isActive()) {
            return null;
        }
        $this->users->markLoggedIn($user->id);

        return ['user' => $user, 'newToken' => $this->createRememberMeToken($user->id)];
    }

    /** Ends every remember-me login of an account (logout, password change). */
    public function forgetAllDevices(int $userId): void
    {
        $this->tokens->deleteAllOfType($userId, TokenRepository::TYPE_REMEMBER_ME);
    }

    /** True when the password is this account's password (admin re-authentication, ID-08). */
    public function isPasswordRight(int $userId, string $password): bool
    {
        return password_verify($password, $this->users->passwordHashFor($userId));
    }
}
