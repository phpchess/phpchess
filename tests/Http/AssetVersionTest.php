<?php

declare(strict_types=1);

namespace PhpChess\Tests\Http;

use PhpChess\Http\AssetVersion;
use PhpChess\Version;
use PHPUnit\Framework\TestCase;

/**
 * The service worker's cache name (B20, A62): it holds the version and
 * changes when an asset file changes, so phones never keep old scripts.
 */
final class AssetVersionTest extends TestCase
{
    private string $folder;

    protected function setUp(): void
    {
        $this->folder = sys_get_temp_dir() . '/phpchess-asset-test-' . bin2hex(random_bytes(4));
        mkdir($this->folder . '/js', 0777, true);
        file_put_contents($this->folder . '/js/app.js', 'one');
    }

    protected function tearDown(): void
    {
        foreach (glob($this->folder . '/js/*') ?: [] as $file) {
            unlink($file);
        }
        rmdir($this->folder . '/js');
        rmdir($this->folder);
    }

    public function testTheNameHoldsTheVersionAndOnlySafeLetters(): void
    {
        $name = AssetVersion::cacheName($this->folder);

        self::assertStringContainsString(Version::CURRENT, $name);
        self::assertMatchesRegularExpression('/^[A-Za-z0-9.-]+$/', $name);
    }

    public function testTheNameStaysTheSameWhileNothingChanges(): void
    {
        self::assertSame(AssetVersion::cacheName($this->folder), AssetVersion::cacheName($this->folder));
    }

    public function testAChangedOrNewFileGivesANewName(): void
    {
        $before = AssetVersion::fingerprint($this->folder);

        file_put_contents($this->folder . '/js/app.js', 'one more');
        $afterChange = AssetVersion::fingerprint($this->folder);
        self::assertNotSame($before, $afterChange);

        file_put_contents($this->folder . '/js/new.js', 'x');
        self::assertNotSame($afterChange, AssetVersion::fingerprint($this->folder));
    }
}
