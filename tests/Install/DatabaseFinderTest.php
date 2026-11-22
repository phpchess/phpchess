<?php

declare(strict_types=1);

namespace PhpChess\Tests\Install;

use PhpChess\Infra\DatabaseServer;
use PhpChess\Install\DatabaseFinder;
use PhpChess\Tests\Support\TestDatabase;
use PHPUnit\Framework\TestCase;

/**
 * Tests how the installer finds the database without database skills (A23).
 */
final class DatabaseFinderTest extends TestCase
{
    public function testUsesAGivenDatabase(): void
    {
        $config = TestDatabase::config($this);

        $found = DatabaseFinder::find($config->databaseHost, $config->databasePort, $config->databaseUser, $config->databasePassword, $config->databaseName);

        self::assertSame(DatabaseFinder::FOUND, $found['status']);
        self::assertSame($config->databaseName, $found['name']);
    }

    public function testFindsADatabaseWithoutAName(): void
    {
        $config = TestDatabase::config($this);

        $found = DatabaseFinder::find($config->databaseHost, $config->databasePort, $config->databaseUser, $config->databasePassword, '');

        // Either a database was found or created, or the visitor is asked to pick one.
        self::assertContains($found['status'], [DatabaseFinder::FOUND, DatabaseFinder::CHOOSE]);
    }

    public function testExplainsAWrongPasswordInPlainWords(): void
    {
        $config = TestDatabase::config($this);

        $found = DatabaseFinder::find($config->databaseHost, $config->databasePort, $config->databaseUser, 'wrong password', '');

        self::assertSame(DatabaseFinder::CANNOT_CONNECT, $found['status']);
        self::assertStringContainsString('refused the user name or password', $found['message']);
    }

    public function testRefusesUnsafeDatabaseNames(): void
    {
        self::assertTrue(DatabaseServer::isSafeDatabaseName('jane_phpchess'));
        self::assertFalse(DatabaseServer::isSafeDatabaseName('x`; DROP DATABASE y'));
        self::assertFalse(DatabaseServer::isSafeDatabaseName(''));
        self::assertFalse(DatabaseServer::isSafeDatabaseName('with-dash'));
    }
}
