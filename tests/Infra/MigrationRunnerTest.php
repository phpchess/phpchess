<?php

declare(strict_types=1);

namespace PhpChess\Tests\Infra;

use PhpChess\Infra\Database;
use PhpChess\Infra\Migration\Migration;
use PhpChess\Infra\Migration\MigrationRunner;
use PhpChess\Infra\Migration\MigrationStep;
use PhpChess\Tests\Support\TestDatabase;
use PDOException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Tests the migration runner (INS-05, contract K11): it runs, it is
 * interrupted and resumed, and it never touches another program's tables.
 */
final class MigrationRunnerTest extends TestCase
{
    private Database $database;

    protected function setUp(): void
    {
        $this->database = TestDatabase::connectEmpty($this);
        TestDatabase::createForeignTable($this->database);
    }

    protected function tearDown(): void
    {
        if (isset($this->database)) {
            TestDatabase::dropTestTables($this->database);
            $this->database->run('DROP TABLE IF EXISTS ' . TestDatabase::FOREIGN_TABLE);
        }
    }

    // --- Running the real migrations ---

    public function testRunsTheCoreMigrationsWithThePrefix(): void
    {
        $runner = new MigrationRunner($this->database, MigrationRunner::loadFolder(dirname(__DIR__, 2) . '/migrations'));

        self::assertGreaterThan(0, $runner->pendingStepCount());
        $runner->runPending();

        self::assertSame(0, $runner->pendingStepCount());
        foreach (['settings', 'jobs', 'audit_log', 'rate_limits', 'users', 'schema_migrations'] as $table) {
            self::assertTrue($this->database->tableExists('pt_' . $table), "pt_{$table} exists");
        }
    }

    public function testLeavesOtherTablesAlone(): void
    {
        $runner = new MigrationRunner($this->database, MigrationRunner::loadFolder(dirname(__DIR__, 2) . '/migrations'));
        $runner->runPending();

        self::assertTrue($this->database->tableExists(TestDatabase::FOREIGN_TABLE));
        self::assertSame('not ours', $this->database->fetchValue('SELECT name FROM ' . TestDatabase::FOREIGN_TABLE . ' WHERE id = 1'));
    }

    public function testRunningTwiceChangesNothing(): void
    {
        $runner = new MigrationRunner($this->database, MigrationRunner::loadFolder(dirname(__DIR__, 2) . '/migrations'));
        $runner->runPending();

        self::assertSame([], $runner->runPending());
    }

    // --- Interrupted and resumed ---

    public function testRunsInBatches(): void
    {
        $runner = new MigrationRunner($this->database, [$this->threeStepMigration('SELECT 1')]);

        self::assertCount(2, $runner->runPending(2));
        self::assertSame(1, $runner->pendingStepCount());
        self::assertCount(1, $runner->runPending(2));
        self::assertSame(0, $runner->pendingStepCount());
    }

    public function testResumesAfterAFailedStep(): void
    {
        // The second step fails, as if the request died there.
        $broken = new MigrationRunner($this->database, [$this->threeStepMigration('SELECT * FROM {table_that_does_not_exist}')]);
        try {
            $broken->runPending();
            self::fail('The broken step should have thrown.');
        } catch (PDOException) {
            // expected
        }
        self::assertTrue($this->database->tableExists('pt_first'), 'the step before the failure stays done');
        self::assertSame(2, $broken->pendingStepCount());

        // The fixed version carries on at step 2; step 1 is not run again.
        $fixed = new MigrationRunner($this->database, [$this->threeStepMigration('SELECT 1')]);
        $stepsRun = $fixed->runPending();

        self::assertSame(['0099.2 Second', '0099.3 Third'], $stepsRun);
        self::assertSame(0, $fixed->pendingStepCount());
    }

    // --- K11 refusals ---

    public function testRefusesATableWithoutBraces(): void
    {
        $runner = new MigrationRunner($this->database, [
            new Migration(99, 'Bad', [new MigrationStep('Bad', 'CREATE TABLE other_app_users_copy (id INT)')]),
        ]);

        $this->expectException(RuntimeException::class);
        $runner->runPending();
    }

    public function testRefusesToDropATableItDidNotCreate(): void
    {
        $runner = new MigrationRunner($this->database, [
            new Migration(99, 'Bad', [new MigrationStep('Bad', 'DROP TABLE {other_app_users}')]),
        ]);

        $this->expectException(RuntimeException::class);
        $runner->runPending();
    }

    public function testRefusesToDropADatabase(): void
    {
        $runner = new MigrationRunner($this->database, [
            new Migration(99, 'Bad', [new MigrationStep('Bad', 'DROP DATABASE phpchess')]),
        ]);

        $this->expectException(RuntimeException::class);
        $runner->runPending();
    }

    public function testMayDropATableAMigrationCreated(): void
    {
        $runner = new MigrationRunner($this->database, [
            new Migration(98, 'Create', [new MigrationStep('Create', 'CREATE TABLE IF NOT EXISTS {temporary} (id INT)')]),
            new Migration(99, 'Drop', [new MigrationStep('Drop', 'DROP TABLE IF EXISTS {temporary}')]),
        ]);
        $runner->runPending();

        self::assertFalse($this->database->tableExists('pt_temporary'));
    }

    public function testColumnDefaultsWithOnUpdateAreAllowed(): void
    {
        $step = new MigrationStep('Timestamps', 'CREATE TABLE IF NOT EXISTS {stamped} (changed TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)');
        $runner = new MigrationRunner($this->database, [new Migration(99, 'Ok', [$step])]);

        self::assertStringContainsString('`pt_stamped`', $runner->prepareSql($step));
    }

    private function threeStepMigration(string $secondStepSql): Migration
    {
        return new Migration(99, 'Test', [
            new MigrationStep('First', 'CREATE TABLE IF NOT EXISTS {first} (id INT)'),
            new MigrationStep('Second', $secondStepSql),
            new MigrationStep('Third', 'CREATE TABLE IF NOT EXISTS {third} (id INT)'),
        ]);
    }
}
