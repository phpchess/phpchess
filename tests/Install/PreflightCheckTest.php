<?php

declare(strict_types=1);

namespace PhpChess\Tests\Install;

use PhpChess\Install\CheckResult;
use PhpChess\Install\PreflightCheck;
use PhpChess\Tests\Support\TestInstallFolder;
use PHPUnit\Framework\TestCase;

/**
 * Tests the pre-flight checks (INS-03).
 */
final class PreflightCheckTest extends TestCase
{
    public function testThisServerPassesTheRequiredChecks(): void
    {
        $folder = new TestInstallFolder();
        try {
            $results = (new PreflightCheck($folder->path))->checkServer(false);
        } finally {
            $folder->remove();
        }

        self::assertTrue(CheckResult::allRequiredPassed($results));
    }

    public function testAMissingStorageFolderStopsTheInstall(): void
    {
        $results = (new PreflightCheck(sys_get_temp_dir() . '/no-such-phpchess-folder'))->checkServer(true);

        self::assertFalse(CheckResult::allRequiredPassed($results));
    }

    public function testDatabaseVersions(): void
    {
        self::assertTrue(PreflightCheck::checkDatabaseVersion('10.11.8-MariaDB')->passed);
        self::assertTrue(PreflightCheck::checkDatabaseVersion('5.5.5-10.11.19-MariaDB')->passed);
        self::assertTrue(PreflightCheck::checkDatabaseVersion('8.0.36')->passed);
        self::assertFalse(PreflightCheck::checkDatabaseVersion('10.6.17-MariaDB')->passed);
        self::assertFalse(PreflightCheck::checkDatabaseVersion('5.7.44')->passed);
    }
}
