<?php

declare(strict_types=1);

namespace PhpChess\Tests\Kernel;

use PhpChess\Domain\System\FeatureSwitches;
use PhpChess\Domain\System\ServerSupport;
use PhpChess\Install\PreflightCheck;
use PhpChess\Tests\Support\InstalledSite;
use PHPUnit\Framework\TestCase;

/**
 * A server without GD or without push support (ServerSupport): the
 * feature stays off everywhere, however the switch is set, and the
 * admin and the installer are told why. No php.ini editing needed.
 */
final class ServerSupportTest extends TestCase
{
    private InstalledSite $site;

    protected function setUp(): void
    {
        $this->site = new InstalledSite($this);
        $this->site->createUser('anna');
    }

    protected function tearDown(): void
    {
        ServerSupport::pretendMissing([]);
        if (isset($this->site)) {
            $this->site->remove();
        }
    }

    public function testWithoutGdUploadsStayOffAndTheAdminSeesWhy(): void
    {
        ServerSupport::pretendMissing([ServerSupport::IMAGES]);
        $switches = new FeatureSwitches($this->site->settings());
        $switches->set(FeatureSwitches::FILE_UPLOADS, true);

        self::assertFalse($switches->isOn(FeatureSwitches::FILE_UPLOADS));
        $anna = $this->site->loggedInBrowser('anna');
        self::assertStringNotContainsString('name="picture"', $anna->get('/account/profile')->body);
        self::assertSame(404, $anna->post('/account/avatar/upload')->status);

        $page = $this->site->loggedInBrowser(InstalledSite::ADMIN_NAME, true)->get('/admin/switches')->body;
        self::assertStringContainsString('picture uploads need PHP&#039;s GD extension', $page);
        self::assertMatchesRegularExpression('/name="switch_file_uploads" value="on" disabled/', $page);
    }

    public function testWithoutPushSupportPushStaysOff(): void
    {
        ServerSupport::pretendMissing([ServerSupport::PUSH]);
        $switches = new FeatureSwitches($this->site->settings());
        $switches->set(FeatureSwitches::PUSH_NOTIFICATIONS, true);

        self::assertFalse($switches->isOn(FeatureSwitches::PUSH_NOTIFICATIONS));
        $anna = $this->site->loggedInBrowser('anna');
        self::assertStringNotContainsString('Turn on push here', $anna->get('/account/notifications')->body);
    }

    public function testThePreflightShowsTheExtrasAsOptional(): void
    {
        ServerSupport::pretendMissing([ServerSupport::IMAGES, ServerSupport::PUSH]);
        $results = (new PreflightCheck($this->site->folder->path))->checkServer(true);
        $extras = array_values(array_filter($results, fn ($result): bool => str_contains($result->name, 'gd') || str_contains($result->name, 'Push')));

        self::assertCount(2, $extras);
        foreach ($extras as $extra) {
            self::assertFalse($extra->passed);
            self::assertFalse($extra->required);
        }
    }
}
