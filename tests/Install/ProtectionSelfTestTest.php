<?php

declare(strict_types=1);

namespace PhpChess\Tests\Install;

use PhpChess\Install\ProtectionSelfTest;
use PhpChess\Tests\Support\TestInstallFolder;
use PHPUnit\Framework\TestCase;

/**
 * Tests the protection self-test (INS-04) with a faked web server.
 */
final class ProtectionSelfTestTest extends TestCase
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

    public function testProtectedWhenTheServerRefuses(): void
    {
        $selfTest = new ProtectionSelfTest($this->folder->path, fn (string $url): string => '403 Forbidden');

        $result = $selfTest->run('http://example.test/chess');

        self::assertSame(ProtectionSelfTest::RESULT_PROTECTED, $result['result']);
        self::assertSame([
            'http://example.test/chess/storage/protection-selftest.txt',
            'http://example.test/chess/config/protection-selftest.txt',
        ], $result['testedUrls']);
    }

    public function testExposedWhenTheServerShowsTheFile(): void
    {
        // A server that ignores .htaccess: it serves the file as it is on disk.
        $appRoot = $this->folder->path;
        $leakyServer = function (string $url) use ($appRoot): string {
            $relativePath = substr($url, strlen('http://example.test/chess'));

            return (string) file_get_contents($appRoot . $relativePath);
        };

        $result = (new ProtectionSelfTest($appRoot, $leakyServer))->run('http://example.test/chess');

        self::assertSame(ProtectionSelfTest::RESULT_EXPOSED, $result['result']);
        self::assertCount(2, $result['exposedUrls']);
    }

    public function testUnknownWhenTheServerCannotBeReached(): void
    {
        $result = (new ProtectionSelfTest($this->folder->path, fn (string $url): ?string => null))->run('http://example.test');

        self::assertSame(ProtectionSelfTest::RESULT_UNKNOWN, $result['result']);
    }

    public function testRemovesTheTestFiles(): void
    {
        (new ProtectionSelfTest($this->folder->path, fn (string $url): string => ''))->run('http://example.test');

        self::assertFileDoesNotExist($this->folder->path . '/storage/protection-selftest.txt');
        self::assertFileDoesNotExist($this->folder->path . '/config/protection-selftest.txt');
    }

    public function testEveryPrivateFolderOfTheRepositoryHasADenyFile(): void
    {
        $repository = dirname(__DIR__, 2);
        foreach (['config', 'storage', 'src', 'templates', 'migrations', 'lang', 'skins'] as $folder) {
            $denyFile = $repository . '/' . $folder . '/.htaccess';
            self::assertFileExists($denyFile);
            self::assertStringContainsString('Require all denied', (string) file_get_contents($denyFile));
        }
    }
}
