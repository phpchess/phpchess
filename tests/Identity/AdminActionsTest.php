<?php

declare(strict_types=1);

namespace PhpChess\Tests\Identity;

use PhpChess\Domain\Identity\User;
use PhpChess\Infra\System\AuditLogRepository;
use PhpChess\Tests\Support\InstalledSite;
use PHPUnit\Framework\TestCase;

/**
 * Tests the admin's actions (ADM-01 to ADM-03, ID-09, SCH-01): what they
 * change, the audit log, and the guards that keep one admin in charge.
 */
final class AdminActionsTest extends TestCase
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

    public function testDisablingAnAccountIsRecordedInTheAuditLog(): void
    {
        $userId = $this->site->createUser('troublemaker');
        $admin = $this->site->loggedInBrowser(InstalledSite::ADMIN_NAME, true);

        $admin->submit('/admin/users', "/admin/users/{$userId}/state", ['state' => User::STATE_DISABLED]);

        self::assertSame(User::STATE_DISABLED, $this->site->users()->findById($userId)?->state);
        $entry = (new AuditLogRepository($this->site->database))->newest()[0];
        self::assertSame('user.state', $entry['action']);
        self::assertSame(InstalledSite::ADMIN_NAME, $entry['username']);
        self::assertStringContainsString('troublemaker', $admin->get('/admin/audit')->body);
    }

    public function testARenameKeepsTheAccountId(): void
    {
        $userId = $this->site->createUser('oldname');
        $admin = $this->site->loggedInBrowser(InstalledSite::ADMIN_NAME, true);

        $admin->submit('/admin/users', "/admin/users/{$userId}/rename", ['username' => 'newname']);

        self::assertSame('newname', $this->site->users()->findById($userId)?->username);
        self::assertNull($this->site->users()->findByUsernameOrEmail('oldname'));
    }

    public function testARenameToATakenNameIsRefused(): void
    {
        $userId = $this->site->createUser('first');
        $this->site->createUser('second');
        $admin = $this->site->loggedInBrowser(InstalledSite::ADMIN_NAME, true);

        $admin->submit('/admin/users', "/admin/users/{$userId}/rename", ['username' => 'second']);

        self::assertSame('first', $this->site->users()->findById($userId)?->username);
    }

    public function testTheAdminCannotBlockOrDemoteThemselves(): void
    {
        $adminId = (int) $this->site->users()->findByUsernameOrEmail(InstalledSite::ADMIN_NAME)?->id;
        $admin = $this->site->loggedInBrowser(InstalledSite::ADMIN_NAME, true);

        $admin->submit('/admin/users', "/admin/users/{$adminId}/state", ['state' => User::STATE_DISABLED]);
        $admin->submit('/admin/users', "/admin/users/{$adminId}/role", ['role' => User::ROLE_PLAYER]);

        $stillAdmin = $this->site->users()->findById($adminId);
        self::assertSame(User::STATE_ACTIVE, $stillAdmin?->state);
        self::assertSame(User::ROLE_ADMIN, $stillAdmin->role);
    }

    public function testRolesCanBeChanged(): void
    {
        $userId = $this->site->createUser('future-teacher');
        $admin = $this->site->loggedInBrowser(InstalledSite::ADMIN_NAME, true);

        $admin->submit('/admin/users', "/admin/users/{$userId}/role", ['role' => User::ROLE_TEACHER]);
        $admin->submit('/admin/users', "/admin/users/{$userId}/role", ['role' => 'emperor']);

        self::assertSame(User::ROLE_TEACHER, $this->site->users()->findById($userId)?->role);
    }

    public function testSwitchesAreSavedFromTheSwitchesPage(): void
    {
        $admin = $this->site->loggedInBrowser(InstalledSite::ADMIN_NAME, true);

        // Every box left unticked except one: all off but game chat.
        $admin->submit('/admin/switches', '/admin/switches', ['switch_game_chat' => 'on']);

        self::assertFalse($this->site->settings()->getBool('switch.public_registration', true));
        self::assertTrue($this->site->settings()->getBool('switch.game_chat', false));
        self::assertSame(404, $this->site->browser()->get('/register')->status);
    }

    public function testATestEmailIsSentToTheAdmin(): void
    {
        $admin = $this->site->loggedInBrowser(InstalledSite::ADMIN_NAME, true);

        $admin->submit('/admin/mail', '/admin/mail/test');

        self::assertSame('admin@example.test', $this->site->mailer->sent[0]->toAddress ?? '');
    }

    public function testAFailedTestEmailIsShownToTheAdmin(): void
    {
        $this->site->mailer->fails = true;
        $admin = $this->site->loggedInBrowser(InstalledSite::ADMIN_NAME, true);

        $admin->submit('/admin/mail', '/admin/mail/test');

        self::assertStringContainsString('The test email could not be sent', $admin->get('/admin/mail')->body);
    }

    public function testTheBannerShowsWhatToFix(): void
    {
        $admin = $this->site->loggedInBrowser(InstalledSite::ADMIN_NAME, true);

        $page = $admin->get('/admin')->body;

        self::assertStringContainsString('Things to fix', $page);
        self::assertStringContainsString('not using HTTPS', $page);
    }

    public function testASetupTokenLinkIsShownOnce(): void
    {
        $admin = $this->site->loggedInBrowser(InstalledSite::ADMIN_NAME, true);

        $page = $admin->submit('/admin', '/admin/setup-token')->body;

        self::assertMatchesRegularExpression('#/chess/install\?token=[0-9a-f]{64}#', $page);
        self::assertFileExists($this->site->folder->path . '/storage/setup-token.json');
    }
}
