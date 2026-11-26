<?php

declare(strict_types=1);

namespace PhpChess\Tests\Tournament;

use PhpChess\Domain\Tournament\BergerTables;
use PhpChess\Domain\Tournament\KnockoutBracket;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The pairing tables of Round Robin and knockout (T-02, T-04, A100): the
 * Berger tables match FIDE's published tables, everyone meets everyone
 * exactly once (twice in a double round), odd numbers give one bye per
 * player, and the knockout bracket gives the byes to the top seeds.
 * Pure code: no database.
 */
final class PairingTablesTest extends TestCase
{
    // --- Berger tables (FIDE Handbook C.05, Annex 1) ---

    public function testSixPlayersMatchFidesPublishedTable(): void
    {
        $expected = [
            [[1, 6], [2, 5], [3, 4]],
            [[6, 4], [5, 3], [1, 2]],
            [[2, 6], [3, 1], [4, 5]],
            [[6, 5], [1, 4], [2, 3]],
            [[3, 6], [4, 2], [5, 1]],
        ];

        $this->assertSame($expected, BergerTables::rounds(6));
    }

    public function testFourPlayersMatchFidesPublishedTable(): void
    {
        $this->assertSame([
            [[1, 4], [2, 3]],
            [[4, 3], [1, 2]],
            [[2, 4], [3, 1]],
        ], BergerTables::rounds(4));
    }

    /**
     * @return array<string, array{int}>
     */
    public static function playerCounts(): array
    {
        return ['2 players' => [2], '3 players' => [3], '5 players' => [5], '8 players' => [8], '9 players' => [9], '14 players' => [14]];
    }

    #[DataProvider('playerCounts')]
    public function testEveryoneMeetsEveryoneOnceAndByesAreFair(int $count): void
    {
        $rounds = BergerTables::rounds($count);
        $meetings = [];
        $byes = array_fill(1, $count, 0);
        $whites = array_fill(1, $count, 0);
        foreach ($rounds as $boards) {
            $seenThisRound = [];
            foreach ($boards as [$white, $black]) {
                $seenThisRound[] = $white;
                if ($black === null) {
                    $byes[$white]++;
                    continue;
                }
                $seenThisRound[] = $black;
                $key = min($white, $black) . '-' . max($white, $black);
                $meetings[$key] = ($meetings[$key] ?? 0) + 1;
                $whites[$white]++;
            }
            // Every player appears once in every round (on a board or with the bye).
            sort($seenThisRound);
            $this->assertSame(range(1, $count), $seenThisRound);
        }

        $this->assertCount($count % 2 === 0 ? $count - 1 : $count, $rounds);
        $this->assertCount(intdiv($count * ($count - 1), 2), $meetings);
        $this->assertSame([1], array_values(array_unique($meetings)));
        $this->assertSame($count % 2 === 0 ? [0] : [1], array_values(array_unique($byes)), 'odd numbers: exactly one bye each');
        // Colours stay fair: nobody has White more than one game more than half.
        foreach ($whites as $whiteCount) {
            $this->assertLessThanOrEqual(intdiv($count, 2) + 1, $whiteCount);
        }
    }

    public function testADoubleRoundSwapsTheColours(): void
    {
        $rounds = BergerTables::rounds(4, true);

        $this->assertCount(6, $rounds);
        $this->assertSame([[4, 1], [3, 2]], $rounds[3]);
    }

    public function testTheByeIsLastSoRealBoardsStartAtOne(): void
    {
        foreach (BergerTables::rounds(5) as $boards) {
            $last = $boards[count($boards) - 1];
            $this->assertNull($last[1]);
        }
    }

    // --- The knockout bracket ---

    public function testSeedOrderKeepsTheTopSeedsApart(): void
    {
        $this->assertSame([1, 4, 2, 3], KnockoutBracket::seedOrder(4));
        $this->assertSame([1, 8, 4, 5, 2, 7, 3, 6], KnockoutBracket::seedOrder(8));
    }

    public function testSixPlayersGiveTheTopTwoSeedsAByeInRoundOne(): void
    {
        $this->assertSame(8, KnockoutBracket::size(6));
        $this->assertSame(3, KnockoutBracket::roundCount(6));
        $this->assertSame([[1, null], [4, 5], [2, null], [3, 6]], KnockoutBracket::firstRound(6));
    }

    public function testAnOddNumberGivesExactlyTheMissingByes(): void
    {
        $byes = 0;
        foreach (KnockoutBracket::firstRound(13) as [, $bottom]) {
            $byes += $bottom === null ? 1 : 0;
        }

        $this->assertSame(3, $byes);
    }

    public function testRoundNamesCountFromTheFinal(): void
    {
        $this->assertSame('quarter_final', KnockoutBracket::roundName(1, 8));
        $this->assertSame('semi_final', KnockoutBracket::roundName(2, 8));
        $this->assertSame('final', KnockoutBracket::roundName(3, 8));
        $this->assertSame('round', KnockoutBracket::roundName(1, 32));
    }
}
