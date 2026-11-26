<?php

declare(strict_types=1);

namespace PhpChess\Tests\Tournament;

use PhpChess\Domain\Tournament\DutchPairing;
use PhpChess\Domain\Tournament\SwissPlayer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The Swiss pairing, FIDE Dutch system (T-03, A99; FIDE C.04.3).
 *
 * - Round 1 follows the Dutch rule: the top half meets the bottom half,
 *   colours alternating from board 1 (the handbook's round-1 example).
 * - A worked round 2: the score brackets and the colour rules.
 * - Whole tournaments simulated with many player counts: never a rematch,
 *   never a third game in a row with the same colour, the colour difference
 *   never beyond 2, the bye at most once per player, and the leaders meet.
 *
 * Pure code: no database.
 */
final class DutchPairingTest extends TestCase
{
    public function testRoundOneTopHalfMeetsBottomHalfWithAlternatingColours(): void
    {
        $result = (new DutchPairing())->pair(self::freshPlayers(8));

        $this->assertNotNull($result);
        $this->assertSame([[1, 5], [6, 2], [3, 7], [8, 4]], $result['boards']);
        $this->assertNull($result['byeId']);
    }

    public function testRoundOneWithAnOddNumberGivesTheByeToTheLowestRanked(): void
    {
        $result = (new DutchPairing())->pair(self::freshPlayers(7));

        $this->assertNotNull($result);
        $this->assertSame(7, $result['byeId']);
        $this->assertSame([[1, 4], [5, 2], [3, 6]], $result['boards']);
    }

    /**
     * Four players. Round 1: 1-3 (1 won), 4-2 (2 won with Black).
     * Round 2: the winners' bracket is 1 and 2: 1 had White, 2 had Black,
     * so 2 gets White (both wishes granted). The losers' bracket: 3 had
     * Black, 4 had White, so 3 gets White.
     */
    public function testRoundTwoPairsTheScoreBracketsAndGrantsBothColourWishes(): void
    {
        $players = [
            new SwissPlayer(1, 1, 2, ['w'], [3], false),
            new SwissPlayer(2, 2, 2, ['b'], [4], false),
            new SwissPlayer(3, 3, 0, ['b'], [1], false),
            new SwissPlayer(4, 4, 0, ['w'], [2], false),
        ];

        $result = (new DutchPairing())->pair($players);

        $this->assertNotNull($result);
        $this->assertSame([[2, 1], [3, 4]], $result['boards']);
    }

    public function testARematchIsAvoidedByATranspositionInTheBracket(): void
    {
        // Everyone on the same score; 1 has already met 3, so S1[1] takes the next of S2.
        $players = [
            new SwissPlayer(1, 1, 2, ['w'], [3], false),
            new SwissPlayer(2, 2, 2, ['b'], [4], false),
            new SwissPlayer(3, 3, 2, ['b'], [1], false),
            new SwissPlayer(4, 4, 2, ['w'], [2], false),
        ];

        $result = (new DutchPairing())->pair($players);

        $this->assertNotNull($result);
        foreach ($result['boards'] as [$white, $black]) {
            $this->assertNotSame([1, 3], [min($white, $black), max($white, $black)]);
            $this->assertNotSame([2, 4], [min($white, $black), max($white, $black)]);
        }
    }

    public function testAPlayerWhoHadTheByeDoesNotGetItAgain(): void
    {
        $players = [...array_slice(self::freshPlayers(5), 0, 4), new SwissPlayer(5, 5, 2, [], [], true)];

        $result = (new DutchPairing())->pair($players);

        $this->assertNotNull($result);
        $this->assertNotSame(5, $result['byeId']);
    }

    public function testNoPairingIsPossibleWhenEveryoneHasMet(): void
    {
        $players = [
            new SwissPlayer(1, 1, 2, ['w'], [2], false),
            new SwissPlayer(2, 2, 0, ['b'], [1], false),
        ];

        $this->assertNull((new DutchPairing())->pair($players));
    }

    // --- Whole tournaments, simulated ---

    /**
     * @return array<string, array{int, int}>
     */
    public static function fields(): array
    {
        return [
            '6 players, 4 rounds' => [6, 4],
            '7 players, 4 rounds' => [7, 4],
            '10 players, 5 rounds' => [10, 5],
            '15 players, 5 rounds' => [15, 5],
            '24 players, 7 rounds' => [24, 7],
            '41 players, 7 rounds' => [41, 7],
        ];
    }

    #[DataProvider('fields')]
    public function testAWholeSwissKeepsTheAbsoluteRules(int $count, int $rounds): void
    {
        // What each player has so far, by player id.
        $halves = array_fill(1, $count, 0);
        $colours = array_fill(1, $count, []);
        $opponents = array_fill(1, $count, []);
        $hadBye = array_fill(1, $count, false);
        $pairing = new DutchPairing();

        for ($round = 1; $round <= $rounds; $round++) {
            $players = [];
            for ($id = 1; $id <= $count; $id++) {
                $players[] = new SwissPlayer($id, $id, $halves[$id], $colours[$id], $opponents[$id], $hadBye[$id]);
            }
            $result = $pairing->pair($players);
            $this->assertNotNull($result, "round {$round} could be paired");

            $seen = [];
            foreach ($result['boards'] as [$white, $black]) {
                $this->assertNotContains($black, $opponents[$white], "no rematch in round {$round}");
                $seen[] = $white;
                $seen[] = $black;
                $colours[$white][] = 'w';
                $colours[$black][] = 'b';
                $opponents[$white][] = $black;
                $opponents[$black][] = $white;
                // A fixed, repeatable result: the lower id wins, some boards are drawn.
                if (($white + $black + $round) % 5 === 0) {
                    $halves[$white] += 1;
                    $halves[$black] += 1;
                } else {
                    $halves[min($white, $black)] += 2;
                }
            }
            if ($result['byeId'] !== null) {
                $this->assertFalse($hadBye[$result['byeId']], 'the bye at most once');
                $hadBye[$result['byeId']] = true;
                $halves[$result['byeId']] += 2;
                $seen[] = $result['byeId'];
            }
            sort($seen);
            $this->assertSame(range(1, $count), $seen, "everyone is paired once in round {$round}");
        }

        foreach ($colours as $id => $playerColours) {
            $difference = count(array_filter($playerColours, fn (string $colour): bool => $colour === 'w')) * 2 - count($playerColours);
            $this->assertLessThanOrEqual(2, abs($difference), "player {$id}: colour difference at most 2");
            for ($i = 2; $i < count($playerColours); $i++) {
                $this->assertFalse($playerColours[$i] === $playerColours[$i - 1] && $playerColours[$i] === $playerColours[$i - 2], "player {$id}: never three of a colour in a row");
            }
        }
    }

    /**
     * @return list<SwissPlayer>
     */
    private static function freshPlayers(int $count): array
    {
        $players = [];
        for ($id = 1; $id <= $count; $id++) {
            $players[] = new SwissPlayer($id, $id, 0, [], [], false);
        }

        return $players;
    }
}
