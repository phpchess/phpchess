<?php

declare(strict_types=1);

namespace PhpChess\Tests\Chess;

use PhpChess\Domain\Chess\MoveGenerator;
use PhpChess\Domain\Chess\Position;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Perft tests for the rules engine (G-01, K1).
 *
 * "Perft" counts every sequence of legal moves to a given depth. The totals
 * for these positions are known from the chess programming community
 * (chessprogramming.org, "Perft Results"); if the move generator gets any
 * rule wrong (castling, en passant, promotion, pins, checks), a total is off.
 * The depths are kept small so the suite stays fast.
 */
final class PerftTest extends TestCase
{
    /**
     * @return array<string, array{string, int, int}>
     */
    public static function knownTotals(): array
    {
        return [
            'start, depth 1' => [Position::START_FEN, 1, 20],
            'start, depth 2' => [Position::START_FEN, 2, 400],
            'start, depth 3' => [Position::START_FEN, 3, 8902],
            'kiwipete, depth 1' => ['r3k2r/p1ppqpb1/bn2pnp1/3PN3/1p2P3/2N2Q1p/PPPBBPPP/R3K2R w KQkq - 0 1', 1, 48],
            'kiwipete, depth 2' => ['r3k2r/p1ppqpb1/bn2pnp1/3PN3/1p2P3/2N2Q1p/PPPBBPPP/R3K2R w KQkq - 0 1', 2, 2039],
            'position 3, depth 3' => ['8/2p5/3p4/KP5r/1R3p1k/8/4P1P1/8 w - - 0 1', 3, 2812],
            'position 3, depth 4' => ['8/2p5/3p4/KP5r/1R3p1k/8/4P1P1/8 w - - 0 1', 4, 43238],
            'position 4, depth 2' => ['r3k2r/Pppp1ppp/1b3nbN/nP6/BBP1P3/q4N2/Pp1P2PP/R2Q1RK1 w kq - 0 1', 2, 264],
            'position 4, depth 3' => ['r3k2r/Pppp1ppp/1b3nbN/nP6/BBP1P3/q4N2/Pp1P2PP/R2Q1RK1 w kq - 0 1', 3, 9467],
            'position 5, depth 2' => ['rnbq1k1r/pp1Pbppp/2p5/8/2B5/8/PPP1NnPP/RNBQK2R w KQ - 1 8', 2, 1486],
            'position 6, depth 2' => ['r4rk1/1pp1qppp/p1np1n2/2b1p1B1/2B1P1b1/P1NP1N2/1PP1QPPP/R4RK1 w - - 0 10', 2, 2079],
        ];
    }

    #[DataProvider('knownTotals')]
    public function testTheMoveCountMatchesTheKnownTotal(string $fen, int $depth, int $expectedTotal): void
    {
        self::assertSame($expectedTotal, self::perft(Position::fromFen($fen), $depth));
    }

    private static function perft(Position $position, int $depth): int
    {
        $moves = MoveGenerator::legalMoves($position);
        if ($depth === 1) {
            return count($moves);
        }
        $total = 0;
        foreach ($moves as $move) {
            $total += self::perft($position->play($move), $depth - 1);
        }

        return $total;
    }
}
