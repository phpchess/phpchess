<?php

declare(strict_types=1);

namespace PhpChess\Tests\Support;

use PhpChess\Infra\Database;
use PhpChess\Kernel\Config;
use PHPUnit\Framework\TestCase;

/**
 * The test database for tests that need MariaDB/MySQL (A22: "phpchess_support").
 *
 * The connection details come from environment variables, so no password is
 * in the repository:
 *   PHPCHESS_TEST_DB_HOST, PHPCHESS_TEST_DB_PORT, PHPCHESS_TEST_DB_NAME,
 *   PHPCHESS_TEST_DB_USER, PHPCHESS_TEST_DB_PASSWORD
 * Without them the database tests are skipped, unless PHPCHESS_TEST_DB_REQUIRED=1
 * (as in CI), which makes a missing database a failure.
 *
 * Tests use the table prefix "pt_" and start from an empty set of "pt_" tables.
 * They also create a table of "another program" (other_app_users) to prove
 * that phpChess never touches tables that aren't its own (K11).
 */
final class TestDatabase
{
    public const string PREFIX = 'pt_';
    public const string FOREIGN_TABLE = 'other_app_users';

    /** The config for the test database, or skips/fails the test when it isn't set up. */
    public static function config(TestCase $test, string $prefix = self::PREFIX): Config
    {
        $name = (string) getenv('PHPCHESS_TEST_DB_NAME');
        if ($name === '') {
            if (getenv('PHPCHESS_TEST_DB_REQUIRED') === '1') {
                $test::fail('PHPCHESS_TEST_DB_NAME is not set, but the database tests are required.');
            }
            $test::markTestSkipped('No test database configured (PHPCHESS_TEST_DB_NAME).');
        }

        return new Config(
            databaseHost: (string) (getenv('PHPCHESS_TEST_DB_HOST') ?: '127.0.0.1'),
            databasePort: (int) (getenv('PHPCHESS_TEST_DB_PORT') ?: '3306'),
            databaseName: $name,
            databaseUser: (string) getenv('PHPCHESS_TEST_DB_USER'),
            databasePassword: (string) getenv('PHPCHESS_TEST_DB_PASSWORD'),
            tablePrefix: $prefix,
        );
    }

    /** A fresh connection with no "pt_" tables left from earlier tests. */
    public static function connectEmpty(TestCase $test): Database
    {
        $database = Database::connect(self::config($test));
        self::dropTestTables($database);

        return $database;
    }

    /** Removes every table starting with the test prefix. */
    public static function dropTestTables(Database $database): void
    {
        $tables = $database->fetchAll(
            'SELECT table_name AS name FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name LIKE ?',
            [str_replace('_', '\\_', self::PREFIX) . '%'],
        );
        $database->run('SET FOREIGN_KEY_CHECKS = 0');
        foreach ($tables as $table) {
            $name = is_string($table['name']) ? $table['name'] : '';
            if (str_starts_with($name, self::PREFIX)) {
                $database->run('DROP TABLE `' . $name . '`');
            }
        }
        $database->run('SET FOREIGN_KEY_CHECKS = 1');
    }

    /** Creates "another program's" table with one row in it. */
    public static function createForeignTable(Database $database): void
    {
        $database->run('DROP TABLE IF EXISTS ' . self::FOREIGN_TABLE);
        $database->run('CREATE TABLE ' . self::FOREIGN_TABLE . ' (id INT PRIMARY KEY, name VARCHAR(50)) ENGINE=InnoDB');
        $database->run('INSERT INTO ' . self::FOREIGN_TABLE . " (id, name) VALUES (1, 'not ours')");
    }

    /** A second, separate connection, for lock tests. */
    public static function secondConnection(TestCase $test): Database
    {
        return Database::connect(self::config($test));
    }
}
