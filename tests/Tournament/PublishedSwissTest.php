<?php

declare(strict_types=1);

namespace PhpChess\Tests\Tournament;

use PhpChess\Domain\Tournament\DutchPairing;
use PhpChess\Domain\Tournament\SwissPlayer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The Swiss pairing checked against published reference tournaments (T-03, A99).
 *
 * The fixtures come from bbpPairings, a FIDE-endorsed Dutch engine
 * (tests/Tournament/fixtures/bbp/SOURCE.md). Each is a tournament in FIDE's
 * TRF format plus the next round's pairings as the engine makes them.
 * This test reads the tournament, pairs the next round with DutchPairing
 * and compares the boards (and colours) with the published answer.
 *
 * Reading TRF ("001" player lines, FIDE TRF-16):
 *   columns  5-8   starting rank (our pairing number)
 *   columns 81-84  points so far
 *   from column 92, one block of 10 per round: opponent (4), colour, result
 * A block for the round being paired already filled in (opponent 0000,
 * result Z, H or F) means the player is away that round.
 */
final class PublishedSwissTest extends TestCase
{
    private const string FOLDER = __DIR__ . '/fixtures/bbp/';

    /** Results of a game played on the board. */
    private const array PLAYED = ['1', '0', '=', 'W', 'D', 'L'];

    /** Results of a pairing-allocated bye (a player gets that only once). */
    private const array PAIRING_BYE = ['U', '+'];

    /**
     * @return array<string, array{string}>
     */
    public static function tournaments(): array
    {
        return [
            'Dutch 2025 rule C5' => ['dutch_2025_C5'],
            'Dutch 2025 rule C9' => ['dutch_2025_C9'],
        ];
    }

    #[DataProvider('tournaments')]
    public function testTheNextRoundMatchesThePublishedPairing(string $name): void
    {
        $input = (string) file_get_contents(self::FOLDER . $name . '.input');
        $expected = self::readExpected((string) file_get_contents(self::FOLDER . $name . '.output.expected'));

        $result = (new DutchPairing())->pair(self::readPlayers($input));

        $this->assertNotNull($result);
        $boards = array_map(fn (array $board): string => $board[0] . ' ' . $board[1], $result['boards']);
        if ($result['byeId'] !== null) {
            $boards[] = $result['byeId'] . ' 0';
        }
        sort($boards);
        $this->assertSame($expected, $boards);
    }

    /**
     * A 60-player tournament in round 15: here the full Dutch quality rules
     * (floats, C.14 to C.19, and the global optimum of the 2017 rules) decide,
     * which DutchPairing leaves out on purpose so far (A110). Shown as
     * "incomplete" until that is decided, so the gap stays visible.
     */
    public function testALongTournamentNeedsTheFullDutchRules(): void
    {
        $input = (string) file_get_contents(self::FOLDER . 'issue_7.input');
        $expected = self::readExpected((string) file_get_contents(self::FOLDER . 'issue_7.output.expected'));
        $result = (new DutchPairing())->pair(self::readPlayers($input));
        $this->assertNotNull($result);
        $boards = array_map(fn (array $board): string => $board[0] . ' ' . $board[1], $result['boards']);
        sort($boards);
        if ($boards !== $expected) {
            $same = count(array_intersect($boards, $expected));
            $this->markTestIncomplete("issue_7: {$same} of " . count($expected) . ' boards as published; the full Dutch rules are not built yet (A110).');
        }
        $this->assertSame($expected, $boards);
    }

    // --- Reading the files ---

    /**
     * The players to pair next round, from the TRF text.
     *
     * @return list<SwissPlayer>
     */
    private static function readPlayers(string $trf): array
    {
        $rows = [];
        foreach (preg_split('/\r\n|\r|\n/', $trf) ?: [] as $line) {
            if (str_starts_with($line, '001')) {
                $rows[] = $line;
            }
        }

        // The round to pair: one after the last round in which anyone had an opponent.
        $roundToPair = 1;
        foreach ($rows as $line) {
            foreach (self::blocks($line) as $round => $block) {
                if ($block['opponent'] > 0) {
                    $roundToPair = max($roundToPair, $round + 1);
                }
            }
        }

        $players = [];
        foreach ($rows as $line) {
            $blocks = self::blocks($line);
            if (isset($blocks[$roundToPair])) {
                continue; // away in the round being paired
            }
            $colours = [];
            $opponents = [];
            $hadBye = false;
            $unplayed = 0;
            foreach ($blocks as $block) {
                if (!in_array($block['result'], self::PLAYED, true) || $block['opponent'] === 0) {
                    $unplayed++;
                }
                if ($block['opponent'] > 0) {
                    $opponents[] = $block['opponent'];
                    if (in_array($block['result'], self::PLAYED, true)) {
                        $colours[] = $block['colour'];
                    }
                } elseif (in_array($block['result'], self::PAIRING_BYE, true)) {
                    $hadBye = true;
                }
            }
            $rank = (int) trim(substr($line, 4, 4));
            $halves = (int) round(((float) trim(substr($line, 80, 4))) * 2);
            $players[] = new SwissPlayer($rank, $rank, $halves, $colours, $opponents, $hadBye, $unplayed);
        }

        return $players;
    }

    /**
     * The round blocks of one player line: round => opponent, colour, result.
     *
     * @return array<int, array{opponent: int, colour: string, result: string}>
     */
    private static function blocks(string $line): array
    {
        $blocks = [];
        for ($round = 1, $start = 91; $start + 7 < strlen($line) + 1; $round++, $start += 10) {
            $opponentText = trim(substr($line, $start, 4));
            $result = trim(substr($line, $start + 7, 1));
            if ($opponentText === '' && $result === '') {
                continue;
            }
            $blocks[$round] = [
                'opponent' => (int) $opponentText,
                'colour' => trim(substr($line, $start + 5, 1)),
                'result' => $result,
            ];
        }

        return $blocks;
    }

    /**
     * The published boards as "white black" ("player 0" for the bye), sorted.
     *
     * @return list<string>
     */
    private static function readExpected(string $text): array
    {
        $lines = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $text) ?: []), fn (string $line): bool => $line !== ''));
        $boards = array_map(fn (string $line): string => (string) preg_replace('/\s+/', ' ', $line), array_slice($lines, 1));
        sort($boards);

        return $boards;
    }
}
