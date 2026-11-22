<?php

declare(strict_types=1);

namespace PhpChess\Tests\Infra;

use PhpChess\Infra\Database;
use PhpChess\Infra\Migration\MigrationRunner;
use PhpChess\Infra\System\RateLimiter;
use PhpChess\Tests\Support\TestDatabase;
use PHPUnit\Framework\TestCase;

/**
 * Tests the rate limiter (API-04): the limit per window, and a fresh window.
 */
final class RateLimiterTest extends TestCase
{
    private Database $database;

    protected function setUp(): void
    {
        $this->database = TestDatabase::connectEmpty($this);
        (new MigrationRunner($this->database, MigrationRunner::loadFolder(dirname(__DIR__, 2) . '/migrations')))->runPending();
    }

    protected function tearDown(): void
    {
        if (isset($this->database)) {
            TestDatabase::dropTestTables($this->database);
        }
    }

    public function testAllowsUpToTheLimitThenRefuses(): void
    {
        $limiter = new RateLimiter($this->database);
        $now = 1_800_000_000;

        self::assertTrue($limiter->hit('test', 2, 60, $now));
        self::assertTrue($limiter->hit('test', 2, 60, $now + 1));
        self::assertFalse($limiter->hit('test', 2, 60, $now + 2));
    }

    public function testANewWindowStartsAgain(): void
    {
        $limiter = new RateLimiter($this->database);
        $now = 1_800_000_000;

        $limiter->hit('test', 1, 60, $now);
        self::assertFalse($limiter->hit('test', 1, 60, $now));
        self::assertTrue($limiter->hit('test', 1, 60, $now + 60));
    }

    public function testBucketsAreCountedSeparately(): void
    {
        $limiter = new RateLimiter($this->database);

        $limiter->hit('first', 1, 60, 1_800_000_000);
        self::assertTrue($limiter->hit('second', 1, 60, 1_800_000_000));
    }
}
