<?php

declare(strict_types=1);

namespace PhpChess\Kernel;

use PhpChess\Domain\Identity\LoginService;
use PhpChess\Domain\Identity\User;
use PhpChess\Http\Cookie;
use PhpChess\Http\Request;
use PhpChess\Infra\Identity\ApiTokenRepository;
use PhpChess\Infra\Identity\UserRepository;

/**
 * Who is logged in, for this request (ID-04, ID-05, ID-08).
 *
 * The session holds the account id. On each request the account is read
 * fresh, so a disabled account is logged out at once. Without a session, a
 * remember-me cookie logs the visitor in again and is replaced by a new one.
 *
 * Admin re-authentication (ID-08): admin pages need the password to have been
 * confirmed in the last 30 minutes; each admin page view extends that.
 *
 * Cookies to set are collected here, and the Kernel adds them to the response.
 */
final class Authentication
{
    public const string REMEMBER_ME_COOKIE = 'phpchess_remember';

    private const string SESSION_USER_ID = 'user_id';
    private const string SESSION_ADMIN_CONFIRMED_AT = 'admin_confirmed_at';
    private const int ADMIN_CONFIRMATION_SECONDS = 1800;

    private ?User $currentUser = null;
    private bool $currentUserLoaded = false;
    private bool $usedApiToken = false;

    /** @var list<Cookie> */
    private array $cookiesToSet = [];

    public function __construct(
        private readonly Session $session,
        private readonly UserRepository $users,
        private readonly LoginService $logins,
        private readonly Request $request,
        private readonly ?ApiTokenRepository $apiTokens = null,
    ) {
    }

    // --- Who is logged in ---

    /** The logged-in, active account, or null for a visitor. */
    public function currentUser(): ?User
    {
        if (!$this->currentUserLoaded) {
            $this->currentUserLoaded = true;
            $this->currentUser = $this->loadCurrentUser();
        }

        return $this->currentUser;
    }

    /**
     * True when this request logged in with an API token (ID-10) instead of
     * the session. Such a request carries no cookies a stranger's page could
     * use, so the Kernel does not ask it for a CSRF token.
     */
    public function usedApiToken(): bool
    {
        $this->currentUser();

        return $this->usedApiToken;
    }

    private function loadCurrentUser(): ?User
    {
        // An app on the API sends "Authorization: Bearer <token>" (ID-10).
        $authorization = $this->request->header('authorization');
        if ($this->request->isApi() && $this->apiTokens !== null && str_starts_with($authorization, 'Bearer ')) {
            $userId = $this->apiTokens->userIdFor(trim(substr($authorization, strlen('Bearer '))));
            $user = $userId === null ? null : $this->users->findById($userId);
            if ($user === null || !$user->isActive()) {
                return null;
            }
            $this->usedApiToken = true;

            return $user;
        }

        $userId = $this->session->get(self::SESSION_USER_ID);
        if (is_int($userId)) {
            $user = $this->users->findById($userId);
            if ($user !== null && $user->isActive()) {
                return $user;
            }
            // Disabled, archived or deleted since the login: logged out at once.
            $this->session->remove(self::SESSION_USER_ID);

            return null;
        }

        $rememberToken = $this->request->cookies[self::REMEMBER_ME_COOKIE] ?? '';
        if ($rememberToken === '') {
            return null;
        }

        $remembered = $this->logins->useRememberMeToken($rememberToken);
        if ($remembered === null) {
            $this->cookiesToSet[] = Cookie::removal(self::REMEMBER_ME_COOKIE, $this->cookiePath(), $this->request->isHttps);

            return null;
        }

        $this->session->renewId();
        $this->session->set(self::SESSION_USER_ID, $remembered['user']->id);
        $this->cookiesToSet[] = $this->rememberMeCookie($remembered['newToken']);

        return $remembered['user'];
    }

    // --- Logging in and out ---

    /** Starts a logged-in session (after LoginService::checkLogin). */
    public function logIn(User $user, bool $rememberMe): void
    {
        // A new session id at login, so an id known before the login is worthless (ID-04).
        $this->session->renewId();
        $this->session->set(self::SESSION_USER_ID, $user->id);
        $this->session->remove(self::SESSION_ADMIN_CONFIRMED_AT);
        $this->currentUser = $user;
        $this->currentUserLoaded = true;

        if ($rememberMe) {
            $this->cookiesToSet[] = $this->rememberMeCookie($this->logins->createRememberMeToken($user->id));
        }
    }

    /** Ends the session and every remember-me login of the account. */
    public function logOut(): void
    {
        $user = $this->currentUser();
        if ($user !== null) {
            $this->logins->forgetAllDevices($user->id);
        }
        $this->session->remove(self::SESSION_USER_ID);
        $this->session->remove(self::SESSION_ADMIN_CONFIRMED_AT);
        $this->session->renewId();
        $this->cookiesToSet[] = Cookie::removal(self::REMEMBER_ME_COOKIE, $this->cookiePath(), $this->request->isHttps);
        $this->currentUser = null;
    }

    // --- Admin re-authentication (ID-08) ---

    /** Records that the admin just confirmed their password. */
    public function confirmAdmin(int $now): void
    {
        $this->session->set(self::SESSION_ADMIN_CONFIRMED_AT, $now);
    }

    /**
     * True when the password was confirmed in the last 30 minutes. Each check
     * that passes extends the time, so an admin at work isn't asked again.
     */
    public function isAdminConfirmed(int $now): bool
    {
        $confirmedAt = $this->session->get(self::SESSION_ADMIN_CONFIRMED_AT);
        if (!is_int($confirmedAt) || $now - $confirmedAt > self::ADMIN_CONFIRMATION_SECONDS) {
            return false;
        }
        $this->session->set(self::SESSION_ADMIN_CONFIRMED_AT, $now);

        return true;
    }

    // --- Cookies ---

    /**
     * The cookies this request wants to set; the Kernel adds them to the response.
     *
     * @return list<Cookie>
     */
    public function cookiesToSet(): array
    {
        return $this->cookiesToSet;
    }

    private function rememberMeCookie(string $token): Cookie
    {
        return new Cookie(
            self::REMEMBER_ME_COOKIE,
            $token,
            time() + LoginService::REMEMBER_ME_SECONDS,
            $this->cookiePath(),
            $this->request->isHttps,
        );
    }

    private function cookiePath(): string
    {
        return $this->request->basePath === '' ? '/' : $this->request->basePath . '/';
    }
}
