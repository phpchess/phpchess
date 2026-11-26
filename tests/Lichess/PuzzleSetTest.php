<?php

declare(strict_types=1);

namespace PhpChess\Tests\Lichess;

use PhpChess\Domain\Chess\Position;
use PhpChess\Domain\Chess\Rules;
use PhpChess\Domain\Puzzles\PuzzleSet;
use PHPUnit\Framework\TestCase;

/**
 * The bundled puzzle set (C5-1, L-01, A76, A80).
 *
 * Reads the real puzzle-set.csv and replays every puzzle with our own
 * rules: a broken position or an illegal move in the file would show a
 * player a puzzle that can't be solved.
 */
final class PuzzleSetTest extends TestCase
{
    public function testEveryPuzzleIsAValidPositionWithLegalMoves(): void
    {
        $file = fopen(__DIR__ . '/../../src/Domain/Puzzles/puzzle-set.csv', 'r');
        self::assertNotFalse($file);
        fgetcsv($file, null, ',', '"', '');

        $count = 0;
        while (($row = fgetcsv($file, null, ',', '"', '')) !== false) {
            $position = Position::fromFen((string) $row[1]);
            foreach (explode(' ', (string) $row[2]) as $uci) {
                $move = Rules::legalMoveFromUci($position, $uci);
                self::assertNotNull($move, 'puzzle ' . $row[0] . ', move ' . $uci);
                $position = $position->play($move);
            }
            $count++;
        }
        fclose($file);

        self::assertGreaterThan(3000, $count);
    }

    public function testEveryMotifAndLevelHasPuzzles(): void
    {
        $set = new PuzzleSet();
        foreach (PuzzleSet::MOTIFS as $motif) {
            foreach (array_keys(PuzzleSet::LEVELS) as $level) {
                self::assertNotNull($set->next($motif, $level), $motif . ' ' . $level);
            }
        }
    }

    public function testRecentPuzzlesAreSkippedWhileOthersAreLeft(): void
    {
        $set = new PuzzleSet();
        $first = $set->next('mateIn1', 'beginner');
        self::assertNotNull($first);

        for ($try = 0; $try < 20; $try++) {
            self::assertNotSame($first['id'], $set->next('mateIn1', 'beginner', [$first['id']])['id'] ?? '');
        }
    }
}
