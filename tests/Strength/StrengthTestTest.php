<?php

declare(strict_types=1);

namespace PhpChess\Tests\Strength;

use PhpChess\Domain\Chess\Position;
use PhpChess\Domain\Strength\Puzzle;
use PhpChess\Domain\Strength\StrengthTest;
use PHPUnit\Framework\TestCase;

/**
 * The strength test's puzzle choice and result (B15, A60).
 *
 * Most tests use a made-up list of puzzles (one per 100 rating points),
 * so the expected choices are easy to work out by hand. One test reads the
 * real file and checks it is usable.
 */
final class StrengthTestTest extends TestCase
{
    public function testTheRealPuzzleFileHasEnoughPuzzlesForEveryLevel(): void
    {
        $puzzles = StrengthTest::readPuzzleFile(__DIR__ . '/../../src/Domain/Strength/strength-puzzles.csv');

        self::assertGreaterThanOrEqual(60, count($puzzles));
        foreach ($puzzles as $puzzle) {
            self::assertMatchesRegularExpression('/^[a-h][1-8][a-h][1-8][qrbn]?$/', $puzzle->moves[0], $puzzle->id);
            self::assertContains(count($puzzle->moves), [2, 4], $puzzle->id);
            Position::fromFen($puzzle->fen); // throws on a broken position
        }
    }

    public function testTheFirstPuzzleIsNear1000(): void
    {
        $puzzle = $this->test()->nextPuzzle([]);

        self::assertNotNull($puzzle);
        self::assertSame(1000, $puzzle->rating);
    }

    public function testTheAimGoesUpAfterASolveAndDownAfterAMiss(): void
    {
        $test = $this->test();

        self::assertSame(1400, $test->aimAfter([['id' => 'p1000', 'solved' => true]]));
        self::assertSame(600, $test->aimAfter([['id' => 'p1000', 'solved' => false]]));
        // 1000 + 400 - 200 + 100 + 100 (the step never goes below 100)
        self::assertSame(1400, $test->aimAfter([
            ['id' => 'a', 'solved' => true],
            ['id' => 'b', 'solved' => false],
            ['id' => 'c', 'solved' => true],
            ['id' => 'd', 'solved' => true],
        ]));
    }

    public function testAPuzzleIsNeverShownTwice(): void
    {
        $test = $this->test();
        $answers = [];
        $seen = [];
        while (($puzzle = $test->nextPuzzle($answers)) !== null) {
            self::assertNotContains($puzzle->id, $seen);
            $seen[] = $puzzle->id;
            $answers[] = ['id' => $puzzle->id, 'solved' => false];
        }

        self::assertCount(StrengthTest::PUZZLES_PER_TEST, $seen);
    }

    public function testSolvingEverythingGivesExpertAndNothingGivesNewbie(): void
    {
        $test = $this->test();

        self::assertSame('expert', StrengthTest::bandFor($test->estimatedRating($this->play($test, true))));
        self::assertSame('newbie', StrengthTest::bandFor($test->estimatedRating($this->play($test, false))));
    }

    public function testHalfRightAtTheSameLevelGivesThatLevel(): void
    {
        $answers = [
            ['id' => 'p1300', 'solved' => true],
            ['id' => 'p1300b', 'solved' => false],
        ];

        self::assertSame(1300, $this->test()->estimatedRating($answers));
        self::assertSame('club', StrengthTest::bandFor(1300));
    }

    // --- Helpers ---

    /** One made-up puzzle per 100 points from 600 to 2200, plus a second one at 1300. */
    private function test(): StrengthTest
    {
        $puzzles = [];
        for ($rating = 600; $rating <= 2200; $rating += 100) {
            $puzzles[] = new Puzzle('p' . $rating, Position::START_FEN, ['e2e4', 'e7e5'], $rating);
        }
        $puzzles[] = new Puzzle('p1300b', Position::START_FEN, ['e2e4', 'e7e5'], 1300);

        return new StrengthTest($puzzles);
    }

    /**
     * Takes a whole test, answering every puzzle the same way.
     *
     * @return list<array{id: string, solved: bool}>
     */
    private function play(StrengthTest $test, bool $solved): array
    {
        $answers = [];
        while (($puzzle = $test->nextPuzzle($answers)) !== null) {
            $answers[] = ['id' => $puzzle->id, 'solved' => $solved];
        }

        return $answers;
    }
}
