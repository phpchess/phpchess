<?php

declare(strict_types=1);

namespace PhpChess\Tests;

use PhpChess\Version;
use PHPUnit\Framework\TestCase;

/** Proves the tool chain: autoloading, PHPUnit and PHPStan all see the code. */
final class VersionTest extends TestCase
{
    public function testVersionIsSixPointZero(): void
    {
        self::assertStringStartsWith('6.0.', Version::CURRENT);
    }
}
