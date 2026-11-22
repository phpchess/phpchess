<?php

declare(strict_types=1);

namespace PhpChess\Tests\Install;

use PhpChess\Install\InstallLock;
use PhpChess\Tests\Support\TestInstallFolder;
use PHPUnit\Framework\TestCase;

/**
 * Tests the one-time setup token that reopens a locked installer (INS-06).
 */
final class InstallLockTest extends TestCase
{
    private TestInstallFolder $folder;

    protected function setUp(): void
    {
        $this->folder = new TestInstallFolder();
    }

    protected function tearDown(): void
    {
        $this->folder->remove();
    }

    public function testATokenWorksOnce(): void
    {
        $lock = new InstallLock($this->folder->path);
        $token = $lock->createSetupToken(1000);

        self::assertTrue($lock->useSetupToken($token, 1001));
        self::assertFalse($lock->useSetupToken($token, 1002));
    }

    public function testAWrongTokenIsRefused(): void
    {
        $lock = new InstallLock($this->folder->path);
        $lock->createSetupToken(1000);

        self::assertFalse($lock->useSetupToken('wrong', 1001));
        self::assertFalse($lock->useSetupToken('', 1001));
    }

    public function testAnExpiredTokenIsRefused(): void
    {
        $lock = new InstallLock($this->folder->path);
        $token = $lock->createSetupToken(1000);

        self::assertFalse($lock->useSetupToken($token, 1000 + 3601));
    }

    public function testOnlyTheHashIsStored(): void
    {
        $lock = new InstallLock($this->folder->path);
        $token = $lock->createSetupToken(1000);

        self::assertStringNotContainsString($token, (string) file_get_contents($this->folder->path . '/storage/setup-token.json'));
    }
}
