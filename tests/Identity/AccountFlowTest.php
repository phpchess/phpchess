<?php

declare(strict_types=1);

namespace PhpChess\Tests\Identity;

use PhpChess\Domain\Identity\User;
use PhpChess\Kernel\Authentication;
use PhpChess\Tests\Support\InstalledSite;
use PHPUnit\Framework\TestCase;

/**
 * Tests every account flow through the site, with the abuse cases (ID-01 to ID-07):
 * wrong token, expired token, reuse, throttling, blocked accounts.
 */
final class AccountFlowTest extends TestCase
{
    private InstalledSite $site;

    protected function setUp(): void
    {
        $this->site = new InstalledSite($this);
    }

    protected function tearDown(): void
    {
        if (isset($this->site)) {
            $this->site->remove();
        }
    }

    // --- Registration and verification (ID-01, ID-02, ID-03) ---

    public function testRegistrationWithEmailVerification(): void
    {
        $browser = $this->site->browser();
        $response = $browser->submit('/register', '/register', $this->registrationFields('newplayer'));

        self::assertSame(303, $response->status);
        $user = $this->site->users()->findByUsernameOrEmail('newplayer');
        self::assertNotNull($user);
        self::assertSame(User::STATE_PENDING, $user->state);
        self::assertCount(1, $this->site->mailer->sent);

        // Not active yet: login is refused with the "confirm your email" text and the resend button.
        $login = $browser->submit('/login', '/login', ['name_or_email' => 'newplayer', 'password' => InstalledSite::PASSWORD]);
        self::assertSame(422, $login->status);
        self::assertStringContainsString('confirm your email address first', $login->body);
        self::assertStringContainsString('/verify-email/send', $login->body);

        // Opening the link only shows a button (K7); the button confirms.
        $token = $this->site->mailer->lastToken();
        self::assertStringContainsString('name="token" value="' . $token . '"', $browser->get('/verify-email', ['token' => $token])->body);
        self::assertSame(User::STATE_PENDING, $this->site->users()->findByUsernameOrEmail('newplayer')?->state);
        $browser->post('/verify-email', ['token' => $token]);

        self::assertSame(User::STATE_ACTIVE, $this->site->users()->findByUsernameOrEmail('newplayer')->state);
        $login = $browser->submit('/login', '/login', ['name_or_email' => 'newplayer', 'password' => InstalledSite::PASSWORD]);
        self::assertSame(303, $login->status);
    }

    public function testAVerificationLinkWorksOnlyOnce(): void
    {
        $browser = $this->site->browser();
        $browser->submit('/register', '/register', $this->registrationFields('once'));
        $token = $this->site->mailer->lastToken();

        $browser->submit('/verify-email?token=' . $token, '/verify-email', ['token' => $token]);
        $this->site->users()->setState((int) $this->site->users()->findByUsernameOrEmail('once')?->id, User::STATE_PENDING);
        $browser->submit('/verify-email?token=' . $token, '/verify-email', ['token' => $token]);

        self::assertSame(User::STATE_PENDING, $this->site->users()->findByUsernameOrEmail('once')?->state, 'the reused link did nothing');
    }

    public function testAWrongOrExpiredVerificationLinkIsRefused(): void
    {
        $browser = $this->site->browser();
        $browser->submit('/register', '/register', $this->registrationFields('late'));
        $token = $this->site->mailer->lastToken();
        $this->site->database->run('UPDATE phpc_test_user_tokens SET expires_at = UTC_TIMESTAMP(3) - INTERVAL 1 SECOND');

        $browser->get('/verify-email');
        $browser->post('/verify-email', ['token' => $token]);
        $browser->post('/verify-email', ['token' => str_repeat('a', 64)]);
        $browser->post('/verify-email', ['token' => 'not a token']);

        self::assertSame(User::STATE_PENDING, $this->site->users()->findByUsernameOrEmail('late')?->state);
    }

    public function testWithoutTheEmailRequirementAnAccountIsActiveAtOnce(): void
    {
        $this->site->settings()->set('switch.email_requirement', false);

        $this->site->browser()->submit('/register', '/register', $this->registrationFields('school'));

        self::assertSame(User::STATE_ACTIVE, $this->site->users()->findByUsernameOrEmail('school')?->state);
        self::assertSame([], $this->site->mailer->sent);
    }

    public function testApprovalIsNeededWhenTheAdminAsksForIt(): void
    {
        $this->site->settings()->set('switch.email_requirement', false);
        $this->site->settings()->set('accounts.approval_required', true);

        $this->site->browser()->submit('/register', '/register', $this->registrationFields('waiting'));
        $user = $this->site->users()->findByUsernameOrEmail('waiting');
        self::assertSame(User::STATE_PENDING, $user?->state);

        $admin = $this->site->loggedInBrowser(InstalledSite::ADMIN_NAME, true);
        $admin->submit('/admin/users', '/admin/users/' . $user->id . '/approve');

        self::assertSame(User::STATE_ACTIVE, $this->site->users()->findByUsernameOrEmail('waiting')?->state);
    }

    public function testRegistrationRulesAreChecked(): void
    {
        $this->site->createUser('taken');
        $browser = $this->site->browser();

        $cases = [
            'too short name' => ['username' => 'ab'] + $this->registrationFields('x'),
            'taken name' => $this->registrationFields('taken'),
            'bad email' => ['email' => 'not-an-email'] + $this->registrationFields('fine'),
            'short password' => ['password' => 'short', 'password_again' => 'short'] + $this->registrationFields('fine2'),
            'different passwords' => ['password_again' => 'something else 1'] + $this->registrationFields('fine3'),
        ];
        foreach ($cases as $case => $fields) {
            self::assertSame(422, $browser->submit('/register', '/register', $fields)->status, $case);
        }
    }

    public function testRegistrationIsThrottledPerAddress(): void
    {
        $browser = $this->site->browser();
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $browser->submit('/register', '/register', $this->registrationFields('many' . $attempt));
        }

        $response = $browser->submit('/register', '/register', $this->registrationFields('many6'));

        self::assertSame(422, $response->status);
        self::assertStringContainsString('Too many tries', $response->body);
    }

    // --- Login (ID-04, ID-05, ID-07) ---

    public function testAWrongPasswordAndAnUnknownNameGetTheSameAnswer(): void
    {
        $this->site->createUser('known');
        $browser = $this->site->browser();

        $wrongPassword = $browser->submit('/login', '/login', ['name_or_email' => 'known', 'password' => 'wrong password']);
        $unknownName = $browser->submit('/login', '/login', ['name_or_email' => 'nobody', 'password' => 'wrong password']);

        self::assertSame(422, $wrongPassword->status);
        self::assertStringContainsString('The user name or the password is wrong', $wrongPassword->body);
        self::assertStringContainsString('The user name or the password is wrong', $unknownName->body);
    }

    public function testLoginIsThrottledPerName(): void
    {
        $this->site->createUser('target');
        $browser = $this->site->browser();
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $browser->submit('/login', '/login', ['name_or_email' => 'target', 'password' => 'guess ' . $attempt]);
        }

        // Even the right password is refused now, until the 15 minutes are over.
        $response = $browser->submit('/login', '/login', ['name_or_email' => 'target', 'password' => InstalledSite::PASSWORD]);

        self::assertStringContainsString('Too many tries. Please wait 15 minutes', $response->body);
    }

    public function testADisabledAccountCannotLogIn(): void
    {
        $this->site->createUser('blocked', User::ROLE_PLAYER, User::STATE_DISABLED);

        $response = $this->site->browser()->submit('/login', '/login', ['name_or_email' => 'blocked', 'password' => InstalledSite::PASSWORD]);

        self::assertStringContainsString('This account is disabled', $response->body);
    }

    public function testAnAccountDisabledDuringItsSessionIsLoggedOutAtOnce(): void
    {
        $userId = $this->site->createUser('soon');
        $browser = $this->site->loggedInBrowser('soon');
        self::assertSame(200, $browser->get('/account/preferences')->status);

        $this->site->users()->setState($userId, User::STATE_DISABLED);

        self::assertSame(303, $browser->get('/account/preferences')->status, 'sent to the login page');
    }

    public function testRememberMeLogsInAgainAndRotatesTheToken(): void
    {
        $this->site->createUser('remembered');
        $browser = $this->site->browser();
        $browser->submit('/login', '/login', ['name_or_email' => 'remembered', 'password' => InstalledSite::PASSWORD, 'remember_me' => 'yes']);
        $firstToken = $browser->cookies[Authentication::REMEMBER_ME_COOKIE] ?? '';
        self::assertNotSame('', $firstToken);

        // A new browser session with only the cookie: logged in, and the cookie is replaced.
        $nextVisit = $this->site->browser();
        $nextVisit->cookies = [Authentication::REMEMBER_ME_COOKIE => $firstToken];
        self::assertSame(200, $nextVisit->get('/account/preferences')->status);
        self::assertArrayHasKey(Authentication::REMEMBER_ME_COOKIE, $nextVisit->cookies);
        self::assertNotSame($firstToken, $nextVisit->cookies[Authentication::REMEMBER_ME_COOKIE]);

        // The old token is used up: copying it does not work a second time.
        $copy = $this->site->browser();
        $copy->cookies = [Authentication::REMEMBER_ME_COOKIE => $firstToken];
        self::assertSame(303, $copy->get('/account/preferences')->status);
    }

    public function testLogoutEndsRememberMeEverywhere(): void
    {
        $this->site->createUser('leaving');
        $browser = $this->site->browser();
        $browser->submit('/login', '/login', ['name_or_email' => 'leaving', 'password' => InstalledSite::PASSWORD, 'remember_me' => 'yes']);
        $token = $browser->cookies[Authentication::REMEMBER_ME_COOKIE] ?? '';

        $browser->submit('/account/preferences', '/logout');

        $later = $this->site->browser();
        $later->cookies = [Authentication::REMEMBER_ME_COOKIE => $token];
        self::assertSame(303, $later->get('/account/preferences')->status);
        self::assertArrayNotHasKey(Authentication::REMEMBER_ME_COOKIE, $browser->cookies);
    }

    public function testLoginNeverSendsPeopleToAnotherSite(): void
    {
        $this->site->createUser('safe');
        $browser = $this->site->browser();

        $response = $browser->submit('/login', '/login', ['name_or_email' => 'safe', 'password' => InstalledSite::PASSWORD, 'next' => '//evil.example/']);

        self::assertSame('/chess/', $response->header('Location'));
    }

    // --- Password reset (ID-06) ---

    public function testPasswordResetFromStartToFinish(): void
    {
        $this->site->createUser('forgetful');
        $browser = $this->site->browser();

        $browser->submit('/forgot-password', '/forgot-password', ['email' => 'forgetful@example.test']);
        $token = $this->site->mailer->lastToken();
        self::assertNotSame('', $token);

        self::assertSame(200, $browser->get('/reset-password', ['token' => $token])->status);
        $browser->post('/reset-password', ['token' => $token, 'password' => 'my new password', 'password_again' => 'my new password']);

        $login = $browser->submit('/login', '/login', ['name_or_email' => 'forgetful', 'password' => 'my new password']);
        self::assertSame(303, $login->status);

        // The link worked once; now it is refused.
        self::assertSame(303, $this->site->browser()->get('/reset-password', ['token' => $token])->status);
    }

    public function testAnUnknownEmailGetsTheSameAnswerAndNoEmail(): void
    {
        $browser = $this->site->browser();

        $response = $browser->submit('/forgot-password', '/forgot-password', ['email' => 'nobody@example.test']);

        self::assertSame(303, $response->status);
        self::assertSame([], $this->site->mailer->sent);
    }

    public function testATypoInTheNewPasswordKeepsTheLinkUsable(): void
    {
        $this->site->createUser('typo');
        $browser = $this->site->browser();
        $browser->submit('/forgot-password', '/forgot-password', ['email' => 'typo@example.test']);
        $token = $this->site->mailer->lastToken();

        $browser->get('/reset-password', ['token' => $token]);
        $typo = $browser->post('/reset-password', ['token' => $token, 'password' => 'my new password', 'password_again' => 'my new passwrd']);

        self::assertSame(422, $typo->status);
        self::assertSame(200, $browser->get('/reset-password', ['token' => $token])->status);
    }

    public function testAnExpiredResetLinkIsRefused(): void
    {
        $this->site->createUser('slow');
        $this->site->browser()->submit('/forgot-password', '/forgot-password', ['email' => 'slow@example.test']);
        $token = $this->site->mailer->lastToken();
        $this->site->database->run('UPDATE phpc_test_user_tokens SET expires_at = UTC_TIMESTAMP(3) - INTERVAL 1 SECOND');

        self::assertSame(303, $this->site->browser()->get('/reset-password', ['token' => $token])->status);
    }

    // --- Preferences (ID-13) ---

    public function testPreferencesAreSavedAndOnlyKnownValuesKept(): void
    {
        $userId = $this->site->createUser('choosy');
        $browser = $this->site->loggedInBrowser('choosy');

        $browser->submit('/account/preferences', '/account/preferences', [
            'language' => 'en',
            'colour_scheme' => 'dark',
            'board_theme' => 'not-a-board',
            'piece_set' => 'standard',
        ]);

        $saved = (new \PhpChess\Infra\Identity\PreferenceRepository($this->site->database))->findForUser($userId);
        self::assertSame('dark', $saved->colourScheme);
        self::assertSame('green', $saved->boardTheme, 'an unknown value falls back to the default');
        self::assertFalse($saved->emailNotices);
        self::assertStringContainsString('data-colour-scheme="dark"', $browser->get('/')->body);
    }

    /**
     * @return array<string, string>
     */
    private function registrationFields(string $username): array
    {
        return [
            'username' => $username,
            'email' => $username . '@example.test',
            'password' => InstalledSite::PASSWORD,
            'password_again' => InstalledSite::PASSWORD,
        ];
    }
}
