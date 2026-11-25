<?php

declare(strict_types=1);

namespace PhpChess\Domain\Strength;

/**
 * The strength test (B15, A60): 10 short puzzles that suggest a strength band.
 *
 * How it works:
 *   1. The first puzzle is near a rating of 1000.
 *   2. After each answer the aim moves: up after a solve, down after a miss.
 *      The step starts at 400 and halves each time (never below 100), so the
 *      test homes in on the player's level.
 *   3. The next puzzle is the unused one whose rating is nearest the aim.
 *   4. After 10 answers the estimate is the rating at which the player
 *      would be expected to solve exactly as many puzzles as they did
 *      (the usual Elo formula, found by trying ratings in steps of 10).
 *   5. The estimate gives one of the bands of Ratings::STRENGTH_BANDS.
 *
 * This class holds no state: the answers so far are passed in each time
 * (the controller keeps them in the session). The puzzles come from
 * strength-puzzles.csv next to this file (Lichess, CC0, A59).
 */
final class StrengthTest
{
    public const int PUZZLES_PER_TEST = 10;

    public const int FIRST_AIM = 1000;

    public const int FIRST_STEP = 400;

    public const int SMALLEST_STEP = 100;

    /** A new test may be taken this many days after the last one (A60). */
    public const int DAYS_BETWEEN_TESTS = 30;

    /** @var list<Puzzle> */
    private array $puzzles;

    /** @param list<Puzzle>|null $puzzles  null: read strength-puzzles.csv */
    public function __construct(?array $puzzles = null)
    {
        $this->puzzles = $puzzles ?? self::readPuzzleFile(__DIR__ . '/strength-puzzles.csv');
    }

    // --- Choosing the next puzzle ---

    /**
     * The next puzzle to show, or null when the test is complete.
     *
     * @param list<array{id: string, solved: bool}> $answers  the answers so far, in order
     */
    public function nextPuzzle(array $answers): ?Puzzle
    {
        if (count($answers) >= self::PUZZLES_PER_TEST) {
            return null;
        }

        $aim = $this->aimAfter($answers);
        $usedIds = array_column($answers, 'id');

        $best = null;
        foreach ($this->puzzles as $puzzle) {
            if (in_array($puzzle->id, $usedIds, true)) {
                continue;
            }
            if ($best === null || abs($puzzle->rating - $aim) < abs($best->rating - $aim)) {
                $best = $puzzle;
            }
        }

        return $best;
    }

    /**
     * The rating the next puzzle should have, after these answers.
     *
     * @param list<array{id: string, solved: bool}> $answers
     */
    public function aimAfter(array $answers): int
    {
        $aim = self::FIRST_AIM;
        $step = self::FIRST_STEP;
        foreach ($answers as $answer) {
            $aim += $answer['solved'] ? $step : -$step;
            $step = max(self::SMALLEST_STEP, intdiv($step, 2));
        }

        return $aim;
    }

    // --- The result ---

    /**
     * The estimated rating after the answers (step 4 above).
     *
     * @param list<array{id: string, solved: bool}> $answers
     */
    public function estimatedRating(array $answers): int
    {
        $ratings = [];
        $solvedCount = 0;
        foreach ($answers as $answer) {
            $puzzle = $this->find($answer['id']);
            if ($puzzle === null) {
                continue;
            }
            $ratings[] = $puzzle->rating;
            $solvedCount += $answer['solved'] ? 1 : 0;
        }
        if ($ratings === []) {
            return self::FIRST_AIM;
        }

        // Solved none or all: the formula has no answer, so stay just
        // below the easiest or just above the hardest puzzle seen.
        if ($solvedCount === 0) {
            return min($ratings) - 200;
        }
        if ($solvedCount === count($ratings)) {
            return max($ratings) + 200;
        }

        // Try ratings from low to high; the first whose expected number of
        // solves reaches the real number is the estimate.
        for ($rating = 0; $rating <= 3000; $rating += 10) {
            $expectedSolves = 0.0;
            foreach ($ratings as $puzzleRating) {
                $expectedSolves += 1 / (1 + 10 ** (($puzzleRating - $rating) / 400));
            }
            if ($expectedSolves >= $solvedCount) {
                return $rating;
            }
        }

        return 3000;
    }

    /** The strength band (a key of Ratings::STRENGTH_BANDS) for an estimated rating. */
    public static function bandFor(int $estimatedRating): string
    {
        return match (true) {
            $estimatedRating < 700 => 'newbie',
            $estimatedRating < 1100 => 'casual',
            $estimatedRating < 1500 => 'club',
            $estimatedRating < 1850 => 'strong_club',
            default => 'expert',
        };
    }

    public function find(string $id): ?Puzzle
    {
        foreach ($this->puzzles as $puzzle) {
            if ($puzzle->id === $id) {
                return $puzzle;
            }
        }

        return null;
    }

    // --- Reading the puzzle file ---

    /** @return list<Puzzle> */
    public static function readPuzzleFile(string $path): array
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            return [];
        }

        $puzzles = [];
        while (($row = fgetcsv($handle, null, ',', '"', '')) !== false) {
            $first = (string) ($row[0] ?? '');
            // Skip comment lines ("# ..."), the header line and broken lines.
            if ($first === '' || $first[0] === '#' || $first === 'id' || count($row) < 4) {
                continue;
            }
            $puzzles[] = new Puzzle($first, (string) $row[1], explode(' ', (string) $row[2]), (int) $row[3]);
        }
        fclose($handle);

        return $puzzles;
    }
}
