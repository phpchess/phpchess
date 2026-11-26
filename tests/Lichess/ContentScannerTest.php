<?php

declare(strict_types=1);

namespace PhpChess\Tests\Lichess;

use PhpChess\Domain\Community\WordFilter;
use PhpChess\Domain\Lichess\ContentScanner;
use PhpChess\Domain\Lichess\PgnReader;
use PhpChess\Domain\Lichess\UnreadableGame;
use PHPUnit\Framework\TestCase;

/**
 * The content scanner and the strict PGN reader (C5-1, L-03, A81).
 *
 * Protecting minors comes first, so most tests here feed "poisoned" PGN:
 * games with hidden text, web addresses, blocked words, illegal moves,
 * too many games, too many bytes. Each must be kept out, whole.
 */
final class ContentScannerTest extends TestCase
{
    /** A short real game (Scholar's mate) with clean tags. */
    private const string GOOD_GAME = <<<'PGN'
        [Event "Club night"]
        [Site "https://lichess.org/abcdEFGH"]
        [Date "2026.11.20"]
        [White "Anna"]
        [Black "Ben"]
        [Result "1-0"]

        1. e4 e5 2. Bc4 Nc6 3. Qh5 Nf6 4. Qxf7# 1-0
        PGN;

    private function scanner(): ContentScanner
    {
        return new ContentScanner(new WordFilter(['badword']));
    }

    // --- Good games ---

    public function testAGoodGameIsKeptAndWrittenOutClean(): void
    {
        $result = $this->scanner()->scan(self::GOOD_GAME);

        self::assertSame([], $result->rejected);
        self::assertCount(1, $result->accepted);
        $game = $result->accepted[0];
        self::assertSame(['e4', 'e5', 'Bc4', 'Nc6', 'Qh5', 'Nf6', 'Qxf7#'], $game->sanMoves);
        self::assertSame('1-0', $game->result);
        self::assertStringContainsString('[White "Anna"]', $game->toPgn());
        self::assertStringContainsString('4. Qxf7# 1-0', $game->toPgn());
    }

    public function testCommentsVariationsAndSignsAreThrownAway(): void
    {
        $pgn = <<<'PGN'
            [White "Anna"]
            [Black "Ben"]
            [Result "*"]

            1. e4 {visit my site, message me} e5!? $1 2. Nf3 (2. Bc4 {another line} Nc6 (2... Nf6)) Nc6?? ; a comment to the end
            3. Bb5 *
            PGN;

        $game = $this->scanner()->scan($pgn)->accepted[0];

        self::assertSame(['e4', 'e5', 'Nf3', 'Nc6', 'Bb5'], $game->sanMoves);
        self::assertStringNotContainsString('visit', $game->toPgn());
        self::assertStringNotContainsString('another', $game->toPgn());
        self::assertStringNotContainsString('comment', $game->toPgn());
    }

    public function testUnknownTagsAreDropped(): void
    {
        $pgn = "[White \"Anna\"]\n[Black \"Ben\"]\n[Annotator \"anyone\"]\n[MyNote \"hidden text\"]\n[Result \"*\"]\n\n1. d4 d5 *\n";

        $pgnOut = $this->scanner()->scan($pgn)->accepted[0]->toPgn();

        self::assertStringNotContainsString('Annotator', $pgnOut);
        self::assertStringNotContainsString('hidden text', $pgnOut);
    }

    public function testCastlingPromotionAndZeroCastlingAreRead(): void
    {
        // A custom position: White castles with 0-0, then a pawn promotes without "=".
        $pgn = "[SetUp \"1\"]\n[FEN \"4k3/P7/8/8/8/8/8/4K2R w K - 0 1\"]\n[Result \"*\"]\n\n1. 0-0 Kd7 2. a8Q *\n";

        $game = $this->scanner()->scan($pgn)->accepted[0];

        self::assertSame(['O-O', 'Kd7', 'a8=Q'], $game->sanMoves);
        self::assertStringContainsString('[FEN "4k3/P7/8/8/8/8/8/4K2R w K - 0 1"]', $game->toPgn());
    }

    public function testManyGamesInOneFileAreSplit(): void
    {
        $twoGames = self::GOOD_GAME . "\n\n" . str_replace('Anna', 'Cleo', self::GOOD_GAME);

        $result = $this->scanner()->scan($twoGames);

        self::assertCount(2, $result->accepted);
        self::assertSame('Cleo', $result->accepted[1]->tag('White'));
    }

    // --- Poisoned games: kept out whole ---

    public function testAnIllegalMoveRejectsTheGame(): void
    {
        $result = $this->scanner()->scan("[White \"Anna\"]\n[Result \"*\"]\n\n1. e4 e5 2. Ke3 Qh4 3. Kxh4 *\n");

        self::assertSame([], $result->accepted);
        self::assertStringContainsString('illegal or unreadable move', $result->rejected[0]);
    }

    public function testTextHiddenAsAMoveRejectsTheGame(): void
    {
        $result = $this->scanner()->scan("[White \"Anna\"]\n[Result \"*\"]\n\n1. e4 hello 2. Nf3 *\n");

        self::assertSame([], $result->accepted);
    }

    public function testAWebAddressInANameRejectsTheGame(): void
    {
        foreach (['www.example.com', 'http://bad.example', 'see example.com/chat'] as $name) {
            $pgn = str_replace('"Ben"', '"' . $name . '"', self::GOOD_GAME);

            self::assertSame([], $this->scanner()->scan($pgn)->accepted, $name);
        }
    }

    public function testAnEmailAddressInATagRejectsTheGame(): void
    {
        $pgn = str_replace('"Club night"', '"write to me@example.com"', self::GOOD_GAME);

        self::assertSame([], $this->scanner()->scan($pgn)->accepted);
    }

    public function testABlockedWordRejectsTheGame(): void
    {
        $pgn = str_replace('"Ben"', '"Ben BadWord"', self::GOOD_GAME);

        $result = $this->scanner()->scan($pgn);

        self::assertSame([], $result->accepted);
        self::assertStringContainsString('blocked word', $result->rejected[0]);
    }

    public function testABlockedWordInADroppedTagDoesNotMatter(): void
    {
        // The tag is thrown away before anything is stored, so the game may stay.
        $pgn = str_replace('[Result "1-0"]', "[Result \"1-0\"]\n[Annotator \"badword\"]", self::GOOD_GAME);

        self::assertCount(1, $this->scanner()->scan($pgn)->accepted);
    }

    public function testAnotherSiteAddressIsNotAllowed(): void
    {
        $pgn = str_replace('https://lichess.org/abcdEFGH', 'https://elsewhere.example/abcdEFGH', self::GOOD_GAME);

        self::assertSame([], $this->scanner()->scan($pgn)->accepted);
    }

    public function testAnOpenCommentOrVariationRejectsTheGame(): void
    {
        self::assertSame([], $this->scanner()->scan("[Result \"*\"]\n\n1. e4 { never closed e5 *\n")->accepted);
        self::assertSame([], $this->scanner()->scan("[Result \"*\"]\n\n1. e4 (1. d4 e5 *\n")->accepted);
        self::assertSame([], $this->scanner()->scan("[Result \"*\"]\n\n1. e4 ) e5 *\n")->accepted);
    }

    public function testABrokenTagLineRejectsTheGame(): void
    {
        $result = $this->scanner()->scan("[White Anna]\n[Result \"*\"]\n\n1. e4 *\n");

        self::assertSame([], $result->accepted);
    }

    public function testTooManyGamesAreAllRefused(): void
    {
        $result = $this->scanner()->scan(str_repeat(self::GOOD_GAME . "\n\n", ContentScanner::MAX_GAMES + 1));

        self::assertSame([], $result->accepted);
        self::assertStringContainsString('more than', $result->rejected[0]);
    }

    public function testMoreThanOneMegabyteIsRefused(): void
    {
        $result = $this->scanner()->scan(self::GOOD_GAME . str_repeat(' ', ContentScanner::MAX_BYTES));

        self::assertSame([], $result->accepted);
    }

    public function testTextThatIsNotUtf8IsRefused(): void
    {
        self::assertSame([], $this->scanner()->scan("[White \"\xff\xfe\"]\n\n1. e4 *\n")->accepted);
    }

    public function testControlCharactersInATagRejectTheGame(): void
    {
        $pgn = str_replace('"Ben"', "\"Ben\x07\"", self::GOOD_GAME);

        self::assertSame([], $this->scanner()->scan($pgn)->accepted);
    }

    public function testTheReaderThrowsWithAReason(): void
    {
        $this->expectException(UnreadableGame::class);

        PgnReader::readGame("[Result \"*\"]\n\n1. e5 *\n");
    }
}
