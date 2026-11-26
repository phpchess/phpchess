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

    public function testVariationsSignsAndSemicolonCommentsAreThrownAway(): void
    {
        $pgn = <<<'PGN'
            [White "Anna"]
            [Black "Ben"]
            [Result "*"]

            1. e4 {A good start.} e5!? $1 2. Nf3 (2. Bc4 {another line} Nc6 (2... Nf6)) Nc6?? ; a note to the end
            3. Bb5 *
            PGN;

        $game = $this->scanner()->scan($pgn)->accepted[0];

        self::assertSame(['e4', 'e5', 'Nf3', 'Nc6', 'Bb5'], $game->sanMoves);
        self::assertStringNotContainsString('another', $game->toPgn());
        self::assertStringNotContainsString('note', $game->toPgn());
    }

    // --- Comments of the main line (B38, A90: plain text only) ---

    public function testMainLineCommentsAreKeptInTheirPlace(): void
    {
        $pgn = "[Result \"*\"]\n\n{Before the game.} 1. e4 {A good start.} e5 2. Nf3 { [%eval 0.3] [%clk 0:05:00] } Nc6 {Both develop.} *\n";

        $game = $this->scanner()->scan($pgn)->accepted[0];

        // Half-moves played => comment; Lichess's machine notes are gone.
        self::assertSame([0 => 'Before the game.', 1 => 'A good start.', 4 => 'Both develop.'], $game->comments);
        $written = $game->toPgn();
        self::assertStringContainsString('{ Before the game. } 1. e4 { A good start. } 1... e5', $written);
        self::assertStringNotContainsString('%eval', $written);

        // Written out and read again, the comments stay the same.
        self::assertSame($game->comments, $this->scanner()->scan($written)->accepted[0]->comments);
    }

    /** @return array<string, array{string}> */
    public static function poisonedComments(): array
    {
        return [
            'html' => ['<script>alert(1)</script>'],
            'a tag' => ['<b>bold</b>'],
            'an entity' => ['Tom &amp; Jerry'],
            'php' => ['Look: $x = 1'],
            'a backslash' => ['C:\\games'],
            'quotes' => ['He said "hi"'],
            'a web address' => ['see lichess.org/abc'],
            'a web address 2' => ['www dot example dot com https:'],
            'an e-mail address' => ['write to me at kid@example.com'],
            'a blocked word' => ['what a badword move'],
            'a control character' => ["bad\x07bell"],
            'a brace inside' => ['open { here'],
            'emoji' => ['nice move 😀'],
            'too long' => [str_repeat('a', ContentScanner::MAX_COMMENT_LENGTH + 1)],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('poisonedComments')]
    public function testACommentThatIsNotPlainTextRejectsTheWholeGame(string $comment): void
    {
        $result = $this->scanner()->scan("[Result \"*\"]\n\n1. e4 {" . $comment . "} e5 *\n");

        self::assertSame([], $result->accepted);
        self::assertCount(1, $result->rejected);
    }

    public function testCommentsInsideVariationsAreNotChecked(): void
    {
        // They are thrown away with the variation, so they can't be shown.
        $result = $this->scanner()->scan("[Result \"*\"]\n\n1. e4 (1. d4 {<script>}) e5 *\n");

        self::assertCount(1, $result->accepted);
        self::assertSame([], $result->accepted[0]->comments);
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

    public function testABroadcastGameFromLichessPasses(): void
    {
        // The shape of a real Lichess broadcast game (2026-11-26): long lichess.org
        // addresses, clock and engine notes, signs like ?! and the engine's comments.
        $pgn = "[Event \"Round 4: Lerma Castillo, Juan - Brito De La O, Ricardo Gael\"]
"
            . "[Site \"https://lichess.org/broadcast/olimpiada-nacional-conade-2026/round-4/K2jjyI9Y/nxwhtvur\"]
"
            . "[White \"Lerma Castillo, Juan\"]
[Black \"Brito De La O, Ricardo Gael\"]
[Result \"1-0\"]
"
            . "[BroadcastName \"Olimpiada Nacional CONADE 2026 Etapa Estatal Estado de México | Sub  16 Varonil\"]
"
            . "[GameURL \"https://lichess.org/broadcast/olimpiada/round-4/K2jjyI9Y/nxwhtvur\"]

"
            . "1. e4 { [%eval 0.18] [%clk 0:30:57] } 1... c5 { [%eval 0.25] [%clk 0:30:45] } "
            . "2. Nf3?! { [%eval 0.45] } { Inaccuracy. a5 was best. } { [%clk 0:10:50] } 2... g6?? { [%eval 0.34] } 1-0
";

        $result = $this->scanner()->scan($pgn);

        self::assertSame([], $result->rejected);
        self::assertCount(1, $result->accepted);
        self::assertSame('', $result->accepted[0]->tag('Site'));
        self::assertSame('Lerma Castillo, Juan', $result->accepted[0]->tag('White'));
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
