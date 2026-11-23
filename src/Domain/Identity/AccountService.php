<?php

declare(strict_types=1);

namespace PhpChess\Domain\Identity;

use PhpChess\Domain\Language\Translator;
use PhpChess\Domain\System\FeatureSwitches;
use PhpChess\Infra\Identity\TokenRepository;
use PhpChess\Infra\Identity\UserRepository;
use PhpChess\Infra\Mail\Email;
use PhpChess\Infra\Mail\MailFailed;
use PhpChess\Infra\Mail\Mailer;
use PhpChess\Infra\System\RateLimiter;
use PhpChess\Infra\System\SettingsRepository;

/**
 * Registration, email verification and approval (ID-01, ID-02, ID-03, ID-07).
 *
 * Called by the AccountController (visitors) and the admin's UsersController.
 *
 * A new account is "pending" until it has what this server asks for:
 * - a confirmed email address, when the "email requirement" switch is on (ID-02);
 * - an admin's approval, when the setting accounts.approval_required is on (ID-03).
 * When both are in place (or not asked for), it becomes "active" (activateIfReady).
 */
final class AccountService
{
    /** Email verification links work for two days. */
    private const int VERIFICATION_LIFETIME_SECONDS = 172800;

    /** Registrations allowed per address per hour. */
    private const int REGISTRATIONS_PER_HOUR = 5;

    public function __construct(
        private readonly UserRepository $users,
        private readonly TokenRepository $tokens,
        private readonly SettingsRepository $settings,
        private readonly FeatureSwitches $switches,
        private readonly RateLimiter $rateLimiter,
        private readonly Mailer $mailer,
        private readonly Translator $translator,
    ) {
    }

    // --- Registration (ID-01) ---

    /**
     * Creates a player account. Returns the new account. Throws AccountRefused
     * for a rule broken, a name or email already used, or too many tries.
     *
     * @param string $siteUrl  the site's address, for the link in the email
     */
    public function register(string $username, string $email, string $password, string $passwordAgain, string $ipAddress, string $siteUrl, int $now): User
    {
        PasswordRules::checkUsername($username);
        PasswordRules::checkEmail($email);
        PasswordRules::checkNewPassword($password, $passwordAgain, $username);

        if (!$this->rateLimiter->hit('register:ip:' . $ipAddress, self::REGISTRATIONS_PER_HOUR, 3600, $now)) {
            throw new AccountRefused('account.too_many_tries');
        }
        if ($this->users->usernameExists($username)) {
            throw new AccountRefused('register.username_taken');
        }
        if ($this->users->emailExists($email)) {
            throw new AccountRefused('register.email_taken');
        }

        $userId = $this->users->create($username, $email, PasswordRules::hash($password), User::ROLE_PLAYER, User::STATE_PENDING);

        $this->activateIfReady($userId);

        if ($this->switches->isOn(FeatureSwitches::EMAIL_REQUIREMENT)) {
            try {
                $this->sendVerificationEmail($userId, $siteUrl);
            } catch (MailFailed) {
                // The account exists; the link can be sent again from the login page.
                throw new AccountRefused('register.email_failed');
            }
        }

        $user = $this->users->findById($userId);
        if ($user === null) {
            throw new AccountRefused('account.not_found');
        }

        return $user;
    }

    // --- Email verification (ID-02) ---

    /** Sends (again) the email with the verification link. */
    public function sendVerificationEmail(int $userId, string $siteUrl): void
    {
        $user = $this->users->findById($userId);
        if ($user === null || $user->isEmailVerified) {
            return;
        }

        $token = $this->tokens->createToken($userId, TokenRepository::TYPE_VERIFY_EMAIL, self::VERIFICATION_LIFETIME_SECONDS);
        $this->mailer->send(new Email(
            $user->email,
            $user->username,
            $this->translator->text('email.verify.subject', ['site' => $this->siteName()]),
            $this->translator->text('email.verify.text', [
                'name' => $user->username,
                'site' => $this->siteName(),
                'link' => $siteUrl . '/verify-email?token=' . $token,
            ]),
        ));
    }

    /**
     * Confirms an email address with the token from the link. Returns the
     * account. Throws AccountRefused for an unknown, expired or used link.
     */
    public function verifyEmail(string $token): User
    {
        $userId = $this->tokens->useToken($token, TokenRepository::TYPE_VERIFY_EMAIL);
        if ($userId === null) {
            throw new AccountRefused('verify.link_invalid');
        }

        $this->users->markEmailVerified($userId);
        $this->activateIfReady($userId);

        $user = $this->users->findById($userId);
        if ($user === null) {
            throw new AccountRefused('account.not_found');
        }

        return $user;
    }

    // --- Approval (ID-03) ---

    /** True when new accounts need an admin's approval (setting). */
    public function isApprovalRequired(): bool
    {
        return $this->settings->getBool('accounts.approval_required', false);
    }

    /** Approves an account (admin). It becomes active once its email is confirmed, if that is asked for. */
    public function approve(int $userId): void
    {
        $this->users->markApproved($userId);
        $this->activateIfReady($userId);
    }

    /**
     * Makes a pending account active once it has everything this server asks
     * for. Accounts in other states are left alone.
     */
    public function activateIfReady(int $userId): void
    {
        $user = $this->users->findById($userId);
        if ($user === null || $user->state !== User::STATE_PENDING) {
            return;
        }

        $emailIsFine = $user->isEmailVerified || !$this->switches->isOn(FeatureSwitches::EMAIL_REQUIREMENT);
        $approvalIsFine = $user->isApproved || !$this->isApprovalRequired();

        if ($emailIsFine && $approvalIsFine) {
            $this->users->setState($userId, User::STATE_ACTIVE);
        }
    }

    /** Why a pending account can't log in yet: a language key. */
    public function whyPending(User $user): string
    {
        $needsEmail = !$user->isEmailVerified && $this->switches->isOn(FeatureSwitches::EMAIL_REQUIREMENT);

        return $needsEmail ? 'login.email_not_verified' : 'login.waiting_for_approval';
    }

    private function siteName(): string
    {
        return $this->settings->getString('site.name', 'phpChess');
    }
}
