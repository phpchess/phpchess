<?php

declare(strict_types=1);

namespace PhpChess\Domain\Identity;

use PhpChess\Domain\Language\Translator;
use PhpChess\Infra\Identity\TokenRepository;
use PhpChess\Infra\Identity\UserRepository;
use PhpChess\Infra\Mail\Email;
use PhpChess\Infra\Mail\Mailer;
use PhpChess\Infra\System\RateLimiter;
use PhpChess\Infra\System\SettingsRepository;

/**
 * Password reset by a single-use, expiring link (ID-06).
 *
 * 1. The visitor enters their email address. Whether or not an account has
 *    it, the page says the same ("if an account has this address, an email
 *    is on its way"), so nobody can find out which addresses are registered.
 * 2. The email holds a link with a token, valid for one hour, usable once.
 * 3. The new password is set; every remember-me login of the account ends.
 */
final class PasswordResetService
{
    private const int LINK_LIFETIME_SECONDS = 3600;
    private const int REQUESTS_PER_HOUR = 5;

    public function __construct(
        private readonly UserRepository $users,
        private readonly TokenRepository $tokens,
        private readonly LoginService $logins,
        private readonly RateLimiter $rateLimiter,
        private readonly Mailer $mailer,
        private readonly Translator $translator,
        private readonly SettingsRepository $settings,
    ) {
    }

    /**
     * Sends a reset link when an account has this address. Throws
     * AccountRefused only for too many requests; never says whether the
     * address is known.
     */
    public function requestReset(string $email, string $ipAddress, string $siteUrl, int $now): void
    {
        $addressAllowed = $this->rateLimiter->hit('reset:ip:' . $ipAddress, self::REQUESTS_PER_HOUR, 3600, $now);
        $emailAllowed = $this->rateLimiter->hit('reset:email:' . mb_strtolower($email), self::REQUESTS_PER_HOUR, 3600, $now);
        if (!$addressAllowed || !$emailAllowed) {
            throw new AccountRefused('account.too_many_tries');
        }

        $user = $this->users->findByUsernameOrEmail($email);
        if ($user === null || strcasecmp($user->email, $email) !== 0) {
            return;
        }
        if ($user->state === User::STATE_DISABLED || $user->state === User::STATE_ARCHIVED) {
            return;
        }

        $token = $this->tokens->createToken($user->id, TokenRepository::TYPE_RESET_PASSWORD, self::LINK_LIFETIME_SECONDS);
        $siteName = $this->settings->getString('site.name', 'phpChess');
        $this->mailer->send(new Email(
            $user->email,
            $user->username,
            $this->translator->text('email.reset.subject', ['site' => $siteName]),
            $this->translator->text('email.reset.text', [
                'name' => $user->username,
                'site' => $siteName,
                'link' => $siteUrl . '/reset-password?token=' . $token,
            ]),
        ));
    }

    /** True while the link is still usable (to show the form or an "expired" notice). */
    public function isLinkValid(string $token): bool
    {
        return $this->tokens->isValid($token, TokenRepository::TYPE_RESET_PASSWORD);
    }

    /**
     * Sets the new password with the token from the link. Throws AccountRefused
     * for a broken rule (the link stays usable) or an invalid link.
     */
    public function resetPassword(string $token, string $password, string $passwordAgain): void
    {
        if (!$this->isLinkValid($token)) {
            throw new AccountRefused('reset.link_invalid');
        }
        // The rules are checked before the token is used, so a typo doesn't waste the link.
        PasswordRules::checkNewPassword($password, $passwordAgain, '');

        $userId = $this->tokens->useToken($token, TokenRepository::TYPE_RESET_PASSWORD);
        if ($userId === null) {
            throw new AccountRefused('reset.link_invalid');
        }

        $this->users->setPasswordHash($userId, PasswordRules::hash($password));
        $this->logins->forgetAllDevices($userId);
    }
}
