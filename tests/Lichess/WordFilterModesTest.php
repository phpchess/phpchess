<?php

declare(strict_types=1);

namespace PhpChess\Tests\Lichess;

use PhpChess\Domain\ActionRefused;
use PhpChess\Domain\Community\WordFilter;
use PHPUnit\Framework\TestCase;

/**
 * The word filter's three modes for posts (B36, A89): off, stars, refuse.
 * Names and outside games are refused in every mode (blocks()).
 * And how list lines match (A91): whole words, "|" and "*", the starter list.
 */
final class WordFilterModesTest extends TestCase
{
    public function testStarsReplaceTheWord(): void
    {
        self::assertSame('what a ****!', (new WordFilter(['darn'], WordFilter::MODE_STARS))->forPost('what a Darn!'));
    }

    public function testRefuseRefusesTheWholePost(): void
    {
        $filter = new WordFilter(['darn'], WordFilter::MODE_REFUSE);

        self::assertSame('good game', $filter->forPost('good game'));
        $this->expectException(ActionRefused::class);
        $filter->forPost('darn it');
    }

    public function testWholeWordsOnlySoOrdinaryWordsPass(): void
    {
        $filter = new WordFilter(['ass']);

        self::assertSame('a good class, pass it on', $filter->clean('a good class, pass it on'));
        self::assertSame('what an ***', $filter->clean('what an ass'));
        self::assertFalse($filter->blocks('Cassandra'));
        self::assertTrue($filter->blocks('BigAss7'));
    }

    public function testOtherSpellingsAndRepeatedLetters(): void
    {
        $filter = new WordFilter(['fu*dge|fudgy']);

        self::assertSame('oh *******, ***** cake', $filter->clean('oh fuuudge, fudgy cake'));
    }

    public function testLongWordsAreFoundInsideNames(): void
    {
        $filter = new WordFilter(['badword', '# a note, not a word']);

        self::assertTrue($filter->blocks('mybadword1'));
        self::assertFalse($filter->blocks('a note'));
    }

    public function testTheStarterListLeavesChessAndNamesAlone(): void
    {
        $filter = new WordFilter(explode("\n", WordFilter::starterList()));

        foreach (['pawn', 'Guido', 'Christian', 'Jesús', 'domination', 'escort the king', 'class', 'Dickens', 'Hancock', 'Wang'] as $ordinary) {
            self::assertFalse($filter->blocks($ordinary), $ordinary);
        }
        self::assertTrue($filter->blocks('fuuuck'));
    }

    public function testOffLeavesPostsAloneButStillBlocksNames(): void
    {
        $filter = new WordFilter(['darn'], WordFilter::MODE_OFF);

        self::assertSame('darn it', $filter->forPost('darn it'));
        self::assertTrue($filter->blocks('DarnKid'));
    }
}
