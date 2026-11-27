<?php

declare(strict_types=1);

namespace PhpChess\Tests\Study;

use PhpChess\Domain\ActionRefused;
use PhpChess\Domain\Chess\Position;
use PhpChess\Domain\Community\WordFilter;
use PhpChess\Domain\Lichess\UnreadableGame;
use PhpChess\Domain\Study\Drawings;
use PhpChess\Domain\Study\MoveTree;
use PhpChess\Domain\Study\StudyPgn;
use PhpChess\Domain\Study\StudyScanner;
use PhpChess\Domain\Study\Symbols;
use PHPUnit\Framework\TestCase;

/**
 * The study board's domain (C6-1, A129-A135): the move tree with side
 * lines, study PGN in and out, symbols, drawings, and the study scanner
 * with poisoned PGN (A131). No database.
 */
final class StudyBoardTest extends TestCase
{
    /** A study with a side line, symbols, a comment and drawings, as Lichess writes it. */
    private const string STUDY = <<<'PGN'
        [Event "Italian ideas"]
        [White "Anna"]
        [Black "Ben"]
        [Result "*"]

        { [%csl Ge4] Start here } 1. e4 e5 2. Nf3 (2. f4 { [%cal Rf4e5] King's Gambit } 2... exf4) 2... Nc6 $1 3. Bc4!? *
        PGN;

    private function scanner(): StudyScanner
    {
        return new StudyScanner(new WordFilter(['badword']));
    }

    // ===== The tree =====

    public function testMovesAndSideLines(): void
    {
        $tree = new MoveTree();
        $e4 = $tree->addMove(0, 'e4');
        $e5 = $tree->addMove($e4, 'e5');
        $nf3 = $tree->addMove($e5, 'Nf3');
        $f4 = $tree->addMove($e5, 'f4');

        self::assertSame([$nf3, $f4], $tree->childrenOf($e5));
        self::assertSame([$e4, $e5, $nf3], $tree->mainLine());
        // The same move again is the same node, not a new side line.
        self::assertSame($f4, $tree->addMove($e5, 'f4'));
        self::assertSame(4, $tree->moveCount());
    }

    public function testAnIllegalMoveIsRefused(): void
    {
        $this->expectException(ActionRefused::class);
        (new MoveTree())->addMove(0, 'e5');
    }

    public function testPromoteMakesTheLineTheMainLine(): void
    {
        $tree = new MoveTree();
        $e4 = $tree->addMove(0, 'e4');
        $d4 = $tree->addMove(0, 'd4');
        $d5 = $tree->addMove($d4, 'd5');

        $tree->promote($d5);

        self::assertSame([$d4, $e4], $tree->childrenOf(0));
        self::assertSame([$d4, $d5], $tree->mainLine());
    }

    public function testDeleteTakesEverythingAfterTheMove(): void
    {
        $tree = new MoveTree();
        $e4 = $tree->addMove(0, 'e4');
        $tree->addMove($tree->addMove($e4, 'e5'), 'Nf3');

        $tree->delete($e4);

        self::assertSame(0, $tree->moveCount());
        self::assertSame([], $tree->childrenOf(0));
    }

    public function testFromListChecksEveryMoveAgain(): void
    {
        $good = [
            ['n' => 0, 'c' => 'start', 'd' => ['Ge4']],
            ['n' => 1, 'p' => 0, 'san' => 'e4', 's' => [1, 3, 16], 'c' => 'best by test', 'd' => ['Ge2e4', 'Xa1', 'Ge4e4']],
        ];
        $tree = MoveTree::fromList(Position::START_FEN, $good);
        $node = $tree->node(1);
        self::assertNotNull($node);
        // One move symbol (the last), one position symbol; bad drawings dropped.
        self::assertSame([3, 16], $node->symbols);
        self::assertSame(['Ge2e4'], $node->drawings);
        self::assertSame('e2e4', $node->uci);

        // A sneaked-in illegal move: the whole save is refused.
        $this->expectException(ActionRefused::class);
        MoveTree::fromList(Position::START_FEN, [['n' => 0], ['n' => 1, 'p' => 0, 'san' => 'Ke2']]);
    }

    // ===== PGN =====

    public function testReadingAStudyKeepsSideLinesSymbolsCommentsAndDrawings(): void
    {
        $game = StudyPgn::read(self::STUDY);
        $tree = $game['tree'];

        self::assertSame(['e4', 'e5', 'Nf3', 'Nc6', 'Bc4'], array_map(
            static fn (int $number): string => (string) $tree->node($number)?->san,
            $tree->mainLine(),
        ));
        $start = $tree->node(0);
        self::assertSame('Start here', $start?->comment);
        self::assertSame(['Ge4'], $start->drawings);

        // 2.f4 is a side line after 1...e5, with its comment and arrow.
        $e5 = $tree->mainLine()[1];
        $f4 = $tree->node($tree->childrenOf($e5)[1]);
        self::assertSame('f4', $f4?->san);
        self::assertSame("King's Gambit", $f4->comment);
        self::assertSame(['Rf4e5'], $f4->drawings);

        $nc6 = $tree->node($tree->mainLine()[3]);
        self::assertSame([1], $nc6?->symbols);
        $bc4 = $tree->node($tree->mainLine()[4]);
        self::assertSame([5], $bc4?->symbols);
    }

    public function testWritingAndReadingAgainGivesTheSameTree(): void
    {
        $first = StudyPgn::read(self::STUDY);
        $text = StudyPgn::write($first['tags'], $first['tree'], $first['result']);
        $second = StudyPgn::read($text);

        self::assertSame($first['tree']->toList(), $second['tree']->toList());
        self::assertStringContainsString('(2. f4 { [%cal Rf4e5] King\'s Gambit } 2... exf4) 2... Nc6 $1', str_replace("\n", ' ', $text));
    }

    public function testOtherCommandsAndHighSymbolsAreDropped(): void
    {
        $game = StudyPgn::read("[Result \"*\"]\n\n1. e4 \$1 \$22 { [%clk 0:05:00] [%eval 0.3] fine } *\n");
        $node = $game['tree']->node(1);

        self::assertSame([1], $node?->symbols);
        self::assertSame('fine', $node->comment);
    }

    public function testAnIllegalMoveInASideLineRejectsTheGame(): void
    {
        $this->expectException(UnreadableGame::class);
        StudyPgn::read("[Result \"*\"]\n\n1. e4 (1. Ke2) e5 *\n");
    }

    public function testUnbalancedBracketsRejectTheGame(): void
    {
        $this->expectException(UnreadableGame::class);
        StudyPgn::read("[Result \"*\"]\n\n1. e4 (1. d4 e5 *\n");
    }

    // ===== Symbols and drawings =====

    public function testSymbolsAreTidied(): void
    {
        self::assertSame([2, 7, 14], Symbols::tidy([1, 14, 2, 7, 7, 40, 0]));
        self::assertSame('!?', Symbols::sign(5));
    }

    public function testDrawingsInAndOut(): void
    {
        [$text, $drawings] = Drawings::takeFromComment('look [%cal Ge2e4,Rd1d8] here [%csl Yd4,Ge2e4]');

        self::assertSame('look here', trim((string) preg_replace('/\s+/', ' ', $text)));
        self::assertSame(['Ge2e4', 'Rd1d8', 'Yd4'], $drawings);
        self::assertSame('[%csl Yd4] [%cal Ge2e4,Rd1d8]', Drawings::toCommentPart($drawings));
    }

    // ===== The scanner (A131): poisoned PGN =====

    public function testTheScannerKeepsACleanStudy(): void
    {
        $result = $this->scanner()->scan(self::STUDY);

        self::assertSame([], $result['rejected']);
        self::assertCount(1, $result['accepted']);
        self::assertSame(['Event' => 'Italian ideas', 'White' => 'Anna', 'Black' => 'Ben'], $result['accepted'][0]['tags']);
    }

    public function testAddressesAreRemovedFromComments(): void
    {
        $result = $this->scanner()->scan("[Result \"*\"]\n\n1. e4 { visit https://bad.example/x or www.bad.example and mail me@bad.example now } *\n");
        $node = $result['accepted'][0]['tree']->node(1);

        self::assertSame('visit or and mail now', $node?->comment);
    }

    public function testABlockedWordInASideLineCommentRejectsTheGame(): void
    {
        $result = $this->scanner()->scan("[Result \"*\"]\n\n1. e4 (1. d4 { badword }) e5 *\n");

        self::assertSame([], $result['accepted']);
        self::assertCount(1, $result['rejected']);
    }

    public function testUnknownTagsAndTagsWithAddressesAreDropped(): void
    {
        $result = $this->scanner()->scan("[Event \"see www.bad.example\"]\n[Annotator \"x\"]\n[White \"Anna\"]\n[Result \"*\"]\n\n1. e4 *\n");

        self::assertSame(['White' => 'Anna'], $result['accepted'][0]['tags']);
    }

    public function testADrawingLineIsNotTakenForANewGame(): void
    {
        self::assertCount(1, StudyScanner::splitGames("[Result \"*\"]\n\n1. e4 {\n[%cal Ge2e4] } *\n"));
    }
}
