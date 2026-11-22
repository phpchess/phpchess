<?php

declare(strict_types=1);

namespace PhpChess\Tests\Http;

use PhpChess\Http\Request;
use PHPUnit\Framework\TestCase;

/**
 * Tests where the install is found in the URL (INS-02): web root, subfolder,
 * domain pointed at public/, and public/ opened directly.
 */
final class RequestTest extends TestCase
{
    public function testWebRootThroughTheRootHtaccess(): void
    {
        self::assertSame('', Request::findBasePath('/install', '/public/index.php'));
    }

    public function testSubfolderThroughTheRootHtaccess(): void
    {
        self::assertSame('/chess', Request::findBasePath('/chess/install', '/chess/public/index.php'));
        self::assertSame('/chess', Request::findBasePath('/chess/', '/chess/public/index.php'));
    }

    public function testDomainPointedAtPublic(): void
    {
        self::assertSame('', Request::findBasePath('/install', '/index.php'));
    }

    public function testPublicOpenedDirectly(): void
    {
        self::assertSame('/chess/public', Request::findBasePath('/chess/public/install', '/chess/public/index.php'));
    }
}
