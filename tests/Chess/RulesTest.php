<?php

declare(strict_types=1);

namespace PhpChess\Tests\Chess;

use PhpChess\Domain\Chess\InvalidPosition;
use PhpChess\Domain\Chess\Move;
use PhpChess\Domain\Chess\Position;
use PhpChess\Domain\Chess\Rules;
use PhpChess\Domain\Chess\San;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The rules suite's positions for each ending, the SAN writer and the FEN
 * reader (G-01, G-06, K1).
 */
final class RulesTest extends TestCase
{
    // --- Endings on the board ---

    /**
     * @return array<string, array{string, string|null}>
     */
    public static function endingPositions(): array
    {
        return [
            'checkmate (fool\'s mate)' => ['rnb1kbnr/pppp1ppp/8/4p3/6Pq/5P2/PPPPP2P/RNBQKBNR w KQkq - 1 3', Rules::ENDING_CHECKMATE],
            'stalemate' => ['7k/5Q2/6K1/8/8/8/8/8 b - - 0 1', Rules::ENDING_STALEMATE],
            'king against king' => ['8/8/4k3/8/8/3K4/8/8 w - - 0 1', Rules::ENDING_INSUFFICIENT_MATERIAL],
            'king and bishop against king' => ['8/8/4k3/8/8/3KB3/8/8 w - - 0 1', Rules::ENDING_INSUFFICIENT_MATERIAL],
            'king and knight against king' => ['8/8/4k3/8/8/3KN3/8/8 w - - 0 1', Rules::ENDING_INSUFFICIENT_MATERIAL],
            'bishops on the same colour' => ['8/8/4kb2/8/8/3KB3/8/8 w - - 0 1', Rules::ENDING_INSUFFICIENT_MATERIAL],
            'bishops on opposite colours go on' => ['8/8/4k1b1/8/8/3KB3/8/8 w - - 0 1', null],
            'a pawn left goes on' => ['8/8/4k3/8/8/3KP3/8/8 w - - 0 1', null],
            'fifty moves without pawn move or capture' => ['8/8/4k3/8/8/R2K4/8/8 w - - 100 80', Rules::ENDING_FIFTY_MOVES],
            'the start goes on' => [Position::START_FEN, null],
        ];
    }

    #[DataProvider('endingPositions')]
    public function testTheEndingIsFound(string $fen, ?string $expectedEnding): void
    {
        $position = Position::fromFen($fen);

        self::assertSame($expectedEnding, Rules::ending($position, [$position->repetitionKey()]));
    }

    public function testThreefoldRepetitionEndsTheGame(): void
    {
        $position = Position::start();
        $keys = [$position->repetitionKey()];
        $ending = null;
        // Both knights go out and back twice: the start position is seen a third time.
        foreach (['g1f3', 'g8f6', 'f3g1', 'f6g8', 'g1f3', 'g8f6', 'f3g1', 'f6g8'] as $uci) {
            $move = Rules::legalMoveFromUci($position, $uci);
            self::assertNotNull($move);
            $position = $position->play($move);
            $keys[] = $position->repetitionKey();
            $ending = Rules::ending($position, $keys);
        }

        self::assertSame(Rules::ENDING_THREEFOLD_REPETITION, $ending);
    }

    public function testCheckmateIsWonByTheSideThatMoved(): void
    {
        $position = Position::fromFen('rnb1kbnr/pppp1ppp/8/4p3/6Pq/5P2/PPPPP2P/RNBQKBNR w KQkq - 1 3');

        self::assertSame(Position::BLACK, Rules::winnerAfterCheckmate($position));
    }

    public function testAKingAndKnightCanNotWinOnTime(): void
    {
        $position = Position::fromFen('8/8/4k3/8/8/3KN3/8/8 w - - 0 1');

        self::assertFalse(Rules::canEverMate($position, Position::WHITE));
        self::assertFalse(Rules::canEverMate($position, Position::BLACK));
    }

    // --- Moves ---

    public function testAnIllegalMoveIsRefused(): void
    {
        self::assertNull(Rules::legalMoveFromUci(Position::start(), 'e2e5'));
        self::assertNull(Rules::legalMoveFromUci(Position::start(), 'nonsense'));
        self::assertNotNull(Rules::legalMoveFromUci(Position::start(), 'e2e4'));
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function sanExamples(): array
    {
        return [
            'pawn push' => [Position::START_FEN, 'e2e4', 'e4'],
            'knight' => [Position::START_FEN, 'g1f3', 'Nf3'],
            'castling short' => ['r3k2r/8/8/8/8/8/8/R3K2R w KQkq - 0 1', 'e1g1', 'O-O'],
            'castling long' => ['r3k2r/8/8/8/8/8/8/R3K2R w KQkq - 0 1', 'e1c1', 'O-O-O'],
            'promotion with check' => ['8/4P1k1/8/8/8/8/8/4K3 w - - 0 1', 'e7e8q', 'e8=Q'],
            'rooks told apart by file' => ['4k3/8/8/8/8/8/8/R4RK1 w - - 0 1', 'a1d1', 'Rad1'],
            'en passant' => ['4k3/8/8/3pP3/8/8/8/4K3 w - d6 0 1', 'e5d6', 'exd6'],
            'mate sign' => ['rnbqkbnr/pppp1ppp/8/4p3/6P1/5P2/PPPPP2P/RNBQKBNR b KQkq - 0 2', 'd8h4', 'Qh4#'],
        ];
    }

    #[DataProvider('sanExamples')]
    public function testSanIsWrittenAsPeopleExpect(string $fen, string $uci, string $expectedSan): void
    {
        $position = Position::fromFen($fen);
        $move = Rules::legalMoveFromUci($position, $uci);
        self::assertInstanceOf(Move::class, $move);

        self::assertSame($expectedSan, San::of($position, $move));
    }

    // --- FEN ---

    public function testFenReadsAndWritesTheSameText(): void
    {
        $fen = 'r3k2r/p1ppqpb1/bn2pnp1/3PN3/1p2P3/2N2Q1p/PPPBBPPP/R3K2R w KQkq - 0 1';

        self::assertSame($fen, Position::fromFen($fen)->toFen());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function impossibleFens(): array
    {
        return [
            'no kings' => ['8/8/8/8/8/8/8/8 w - - 0 1'],
            'two white kings' => ['4k3/8/8/8/8/8/8/3KK3 w - - 0 1'],
            'pawn on the first rank' => ['4k3/8/8/8/8/8/8/P3K3 w - - 0 1'],
            'side not to move in check' => ['4k3/4R3/8/8/8/8/8/4K3 w - - 0 1'],
            'not a fen' => ['hello'],
        ];
    }

    #[DataProvider('impossibleFens')]
    public function testImpossiblePositionsAreRefused(string $fen): void
    {
        $this->expectException(InvalidPosition::class);
        Position::fromFen($fen);
    }
}
