<?php

declare(strict_types=1);

namespace PhpChess\Domain\Study;

use PhpChess\Domain\Community\WordFilter;
use PhpChess\Domain\Lichess\ContentScanner;
use PhpChess\Domain\Lichess\UnreadableGame;

/**
 * The content scanner for study boards (C6-1, A131, built on A81).
 *
 * Protecting minors comes first. A study keeps more than a plain game
 * (side lines, symbols, drawings, comment text), so it gets its own
 * scanner, with the same spirit as the Lichess one (ContentScanner):
 *
 *   1. Size: at most ContentScanner::MAX_BYTES and MAX_GAMES games (A80).
 *   2. Strict reading (StudyPgn): every move in every side line is
 *      replayed with our rules; one bad move rejects the game whole.
 *   3. Only ContentScanner::ALLOWED_TAGS are kept; a tag value with a web
 *      address, an e-mail address or more than 100 characters is dropped.
 *   4. In comments, web and e-mail addresses and control characters are
 *      removed (A131); every [%...] command except the drawings already
 *      went in StudyPgn.
 *   5. Every kept text (tags and comments) is checked by the site's word
 *      filter; a hit rejects the game.
 *
 * checkTree() runs steps 4 and 5 on a tree a member edited in the browser.
 * Nothing here talks to the database or the network.
 */
final class StudyScanner
{
    /** The most games one paste or file may hold. */
    public const int MAX_GAMES = 50;

    public function __construct(private readonly WordFilter $wordFilter)
    {
    }

    /**
     * @return array{accepted: list<array{tags: array<string, string>, tree: MoveTree, result: string}>, rejected: list<string>}
     */
    public function scan(string $pgnText): array
    {
        // --- 1. Size ---
        if (strlen($pgnText) > ContentScanner::MAX_BYTES) {
            return ['accepted' => [], 'rejected' => ['the text is larger than 1 MB']];
        }
        if (!mb_check_encoding($pgnText, 'UTF-8')) {
            return ['accepted' => [], 'rejected' => ['the text is not UTF-8']];
        }
        $gameTexts = self::splitGames($pgnText);
        if ($gameTexts === []) {
            return ['accepted' => [], 'rejected' => ['no game found']];
        }
        if (count($gameTexts) > self::MAX_GAMES) {
            return ['accepted' => [], 'rejected' => ['more than ' . self::MAX_GAMES . ' games']];
        }

        $accepted = [];
        $rejected = [];
        foreach ($gameTexts as $index => $gameText) {
            try {
                // --- 2. Strict reading ---
                $game = StudyPgn::read($gameText);

                // --- 3. and 5. Tags ---
                $tags = [];
                foreach (ContentScanner::ALLOWED_TAGS as $name) {
                    $value = $game['tags'][$name] ?? null;
                    if ($value === null || $name === 'SetUp' || $name === 'FEN' || $name === 'Result') {
                        continue;
                    }
                    if (mb_strlen($value) > ContentScanner::MAX_TAG_LENGTH || self::cleanText($value) !== $value) {
                        continue;
                    }
                    if ($this->wordFilter->blocks($value)) {
                        throw new UnreadableGame($name . ' holds a blocked word');
                    }
                    $tags[$name] = $value;
                }

                // --- 4. and 5. Comments ---
                if (!$this->checkTree($game['tree'])) {
                    throw new UnreadableGame('a comment holds a blocked word');
                }
                $accepted[] = ['tags' => $tags, 'tree' => $game['tree'], 'result' => $game['result']];
            } catch (UnreadableGame $reason) {
                $rejected[] = 'game ' . ($index + 1) . ': ' . $reason->getMessage();
            }
        }

        return ['accepted' => $accepted, 'rejected' => $rejected];
    }

    /**
     * Cleans every comment in a tree (addresses and control characters
     * out) and checks it with the word filter. False on a blocked word.
     */
    public function checkTree(MoveTree $tree): bool
    {
        foreach ($tree->inOrder() as $node) {
            $clean = self::cleanText($node->comment);
            if ($clean !== $node->comment) {
                $tree->setComment($node->number, $clean);
            }
            if ($clean !== '' && $this->wordFilter->blocks($clean)) {
                return false;
            }
        }

        return true;
    }

    /** A text without web addresses, e-mail addresses and control characters (A131). */
    public static function cleanText(string $text): string
    {
        $text = (string) preg_replace('/[\x00-\x1F\x7F]/', ' ', $text);
        $text = (string) preg_replace('~\b(https?://|www\.)\S*~i', ' ', $text);
        $text = (string) preg_replace('~\S*://\S*~', ' ', $text);
        $text = (string) preg_replace('/\S+@\S+\.\S+/', ' ', $text);
        $text = (string) preg_replace('~\b[\w-]+\.(com|org|net|de|io|info|ru|uk|me|tv|gg|xyz)(/\S*)?\b~i', ' ', $text);

        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }

    /**
     * Cuts a text into games. A new game starts at a tag line after move
     * text; a line starting "[%" is a drawing inside a comment, not a tag.
     *
     * @return list<string>
     */
    public static function splitGames(string $pgnText): array
    {
        $games = [];
        $current = '';
        $seenMoves = false;
        foreach (preg_split('/\r\n|\r|\n/', $pgnText) ?: [] as $line) {
            $trimmed = ltrim($line);
            $isTag = str_starts_with($trimmed, '[') && !str_starts_with($trimmed, '[%');
            if ($isTag && $seenMoves) {
                $games[] = $current;
                $current = '';
                $seenMoves = false;
            }
            if (!$isTag && trim($line) !== '') {
                $seenMoves = true;
            }
            $current .= $line . "\n";
        }
        if (trim($current) !== '') {
            $games[] = $current;
        }

        return $games;
    }
}
