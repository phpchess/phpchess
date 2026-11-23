<?php

declare(strict_types=1);

namespace PhpChess\Http\Web;

use PhpChess\Domain\Identity\AccountRefused;
use PhpChess\Domain\Identity\User;
use PhpChess\Http\PageRenderer;
use PhpChess\Http\Request;
use PhpChess\Http\Response;
use PhpChess\Infra\Mail\MailFailed;
use PhpChess\Kernel\Services;

/**
 * The account pages for visitors: register, verify email, log in and out, reset a password
 * (ID-01 to ID-06).
 *
 * Addresses (Routes.php):
 *   GET/POST /register          registration (switch "public registration")
 *   GET/POST /verify-email      the link from the email shows a button; the button confirms
 *   POST     /verify-email/send sends the link again
 *   GET/POST /login, POST /logout
 *   GET/POST /forgot-password   asks for the email address
 *   GET/POST /reset-password    the link from the email: set a new password
 *
 * Every refusal comes from the services as AccountRefused with a language
 * key; this controller shows it on the same form, with what was typed.
 * A link from an email only shows a page (GET); the change happens when the
 * button is pressed (POST), so mail scanners that open links change nothing (K7).
 */
final class AccountController
{
    public function __construct(private readonly Services $services)
    {
    }

    // --- Registration (ID-01) ---

    public function showRegistration(Request $request): Response
    {
        return $this->registrationForm($request, '', '', '');
    }

    public function register(Request $request): Response
    {
        $username = trim($request->formValue('username'));
        $email = trim($request->formValue('email'));

        try {
            $user = $this->services->accountService()->register(
                $username,
                $email,
                $request->formValue('password'),
                $request->formValue('password_again'),
                $request->clientIp,
                $request->baseUrl(),
                $this->services->now,
            );
        } catch (AccountRefused $refusal) {
            if ($refusal->languageKey === 'register.email_failed') {
                return $this->pages()->redirectWithMessage($request, '/login', 'error', $this->text($refusal->languageKey));
            }

            return $this->registrationForm($request, $username, $email, $this->text($refusal->languageKey, $refusal->values), 422);
        }

        // The player's own idea of their strength sets the start rating (A30).
        $this->services->play()->ratings()->setStartFromBand($user->id, $request->formValue('strength', 'unknown'));

        $messageKey = match (true) {
            $user->isActive() => 'register.done_active',
            $this->services->accountService()->whyPending($user) === 'login.email_not_verified' => 'register.done_check_email',
            default => 'register.done_wait_for_approval',
        };

        return $this->pages()->redirectWithMessage($request, '/login', 'success', $this->text($messageKey));
    }

    private function registrationForm(Request $request, string $username, string $email, string $error, int $status = 200): Response
    {
        return $this->pages()->render($request, 'account/register', $this->text('register.title'), [
            'username' => $username,
            'email' => $email,
            'error' => $error,
        ], $status);
    }

    // --- Email verification (ID-02) ---

    public function showVerification(Request $request): Response
    {
        return $this->pages()->render($request, 'account/verify', $this->text('verify.title'), [
            'token' => $request->queryValue('token'),
        ]);
    }

    public function verify(Request $request): Response
    {
        try {
            $user = $this->services->accountService()->verifyEmail($request->formValue('token'));
        } catch (AccountRefused $refusal) {
            return $this->pages()->redirectWithMessage($request, '/login', 'error', $this->text($refusal->languageKey));
        }

        $messageKey = $user->isActive() ? 'verify.done_active' : 'verify.done_wait_for_approval';

        return $this->pages()->redirectWithMessage($request, '/login', 'success', $this->text($messageKey));
    }

    /** Sends the verification link again. Says the same whether or not the account exists. */
    public function resendVerification(Request $request): Response
    {
        $nameOrEmail = trim($request->formValue('name_or_email'));
        $allowed = $this->services->rateLimiter()->hit('verify-resend:ip:' . $request->clientIp, 5, 3600, $this->services->now);
        if (!$allowed) {
            return $this->pages()->redirectWithMessage($request, '/login', 'error', $this->text('account.too_many_tries'));
        }

        $user = $this->services->users()->findByUsernameOrEmail($nameOrEmail);
        if ($user !== null && $user->state === User::STATE_PENDING && !$user->isEmailVerified) {
            try {
                $this->services->accountService()->sendVerificationEmail($user->id, $request->baseUrl());
            } catch (MailFailed $error) {
                error_log('phpChess: verification email failed: ' . $error->getMessage());
            }
        }

        return $this->pages()->redirectWithMessage($request, '/login', 'success', $this->text('verify.sent_again'));
    }

    // --- Login and logout (ID-04, ID-05) ---

    public function showLogin(Request $request): Response
    {
        if ($this->services->authentication()->currentUser() !== null) {
            return Response::redirect($request->basePath . '/');
        }

        return $this->loginForm($request, '', '', false);
    }

    public function logIn(Request $request): Response
    {
        $nameOrEmail = trim($request->formValue('name_or_email'));

        try {
            $user = $this->services->loginService()->checkLogin(
                $nameOrEmail,
                $request->formValue('password'),
                $request->clientIp,
                $this->services->now,
            );
        } catch (AccountRefused $refusal) {
            $showResend = false;
            $message = $this->text($refusal->languageKey, $refusal->values);
            if ($refusal->languageKey === 'login.account_pending') {
                $pendingUser = $this->services->users()->findByUsernameOrEmail($nameOrEmail);
                $reasonKey = $pendingUser === null ? 'login.account_pending' : $this->services->accountService()->whyPending($pendingUser);
                $message = $this->text($reasonKey);
                $showResend = $reasonKey === 'login.email_not_verified';
            }

            return $this->loginForm($request, $nameOrEmail, $message, $showResend, 422);
        }

        $this->services->authentication()->logIn($user, $request->formValue('remember_me') === 'yes');

        return Response::redirect($request->basePath . self::safeNextPath($request->formValue('next')));
    }

    public function logOut(Request $request): Response
    {
        $this->services->authentication()->logOut();

        return $this->pages()->redirectWithMessage($request, '/', 'success', $this->text('logout.done'));
    }

    private function loginForm(Request $request, string $nameOrEmail, string $error, bool $showResend, int $status = 200): Response
    {
        return $this->pages()->render($request, 'account/login', $this->text('login.title'), [
            'nameOrEmail' => $nameOrEmail,
            'error' => $error,
            'showResend' => $showResend,
            'next' => self::safeNextPath($request->queryValue('next', $request->formValue('next'))),
            'registrationIsOpen' => $this->services->switches()->isOn('public_registration'),
        ], $status);
    }

    /**
     * Where to go after login: only a path on this site ("/account/preferences"),
     * never another site ("//evil.example" or "https://..."), so the login
     * can't be used to send people elsewhere.
     */
    public static function safeNextPath(string $next): string
    {
        if (preg_match('#^/[A-Za-z0-9/_.-]*$#', $next) !== 1 || str_starts_with($next, '//')) {
            return '/';
        }

        return $next;
    }

    // --- Password reset (ID-06) ---

    public function showForgotPassword(Request $request): Response
    {
        return $this->pages()->render($request, 'account/forgot_password', $this->text('forgot.title'), ['error' => '']);
    }

    public function requestPasswordReset(Request $request): Response
    {
        try {
            $this->services->passwordResetService()->requestReset(
                trim($request->formValue('email')),
                $request->clientIp,
                $request->baseUrl(),
                $this->services->now,
            );
        } catch (AccountRefused $refusal) {
            return $this->pages()->render($request, 'account/forgot_password', $this->text('forgot.title'), [
                'error' => $this->text($refusal->languageKey),
            ], 429);
        } catch (MailFailed $error) {
            // Logged for the admin; the visitor sees the same answer as always,
            // so the page never tells whether an account has this address.
            error_log('phpChess: password reset email failed: ' . $error->getMessage());
        }

        return $this->pages()->redirectWithMessage($request, '/login', 'success', $this->text('forgot.sent'));
    }

    public function showPasswordReset(Request $request): Response
    {
        $token = $request->queryValue('token');
        if (!$this->services->passwordResetService()->isLinkValid($token)) {
            return $this->pages()->redirectWithMessage($request, '/forgot-password', 'error', $this->text('reset.link_invalid'));
        }

        return $this->pages()->render($request, 'account/reset_password', $this->text('reset.title'), ['token' => $token, 'error' => '']);
    }

    public function resetPassword(Request $request): Response
    {
        $token = $request->formValue('token');
        try {
            $this->services->passwordResetService()->resetPassword($token, $request->formValue('password'), $request->formValue('password_again'));
        } catch (AccountRefused $refusal) {
            if ($refusal->languageKey === 'reset.link_invalid') {
                return $this->pages()->redirectWithMessage($request, '/forgot-password', 'error', $this->text($refusal->languageKey));
            }

            return $this->pages()->render($request, 'account/reset_password', $this->text('reset.title'), [
                'token' => $token,
                'error' => $this->text($refusal->languageKey, $refusal->values),
            ], 422);
        }

        return $this->pages()->redirectWithMessage($request, '/login', 'success', $this->text('reset.done'));
    }

    // --- Helpers ---

    /**
     * @param array<string, string|int> $values
     */
    private function text(string $key, array $values = []): string
    {
        return $this->services->translator()->text($key, $values);
    }

    private function pages(): PageRenderer
    {
        return $this->services->pages();
    }
}
