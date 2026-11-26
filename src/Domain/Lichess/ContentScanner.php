<?php

declare(strict_types=1);

namespace PhpChess\Domain\Lichess;

use PhpChess\Domain\Community\WordFilter;

/**
 * The content scanner for games that come from outside (C5-1, L-03, A81).
 *
 * Protecting minors comes before everything else. Every PGN, whether it
 * came from lichess.org or from an admin's upload, passes here before
 * anything is stored:
 *
 *   1. Size: at most MAX_BYTES of text and MAX_GAMES games (A80).
 *   2. Strict reading (PgnReader): every move is replayed with our rules;
 *      a game that can't be read is rejected whole. Variations and signs
 *      are thrown away; the main line's comments are kept (B38).
 *   3. Only the ALLOWED_TAGS are kept; every other tag is dropped.
 *   4. No tag value may hold a web address, an e-mail address or a control
 *      character, and none may be longer than MAX_TAG_LENGTH.
 *   5. Every kept text is checked by the site's word filter (the list the
 *      admin keeps for chat and names); a hit rejects the game.
 *   6. Comments are plain text only (A90, strict): letters, digits, spaces
 *      and the few signs in COMMENT_SIGNS, at most MAX_COMMENT_LENGTH each,
 *      plus checks 4 and 5. Anything else (<, >, &, \, $, @, quotes ...)
 *      rejects the whole game: we don't guess what it was meant to be.
 *
 * The result lists the games that passed, written out again as clean PGN,
 * and one plain reason for every game that did not. Nothing here talks to
 * the database or the network.
 */
final class ContentScanner
{
    /** The largest PGN text accepted in one go: 1 MB (A80). */
    public const int MAX_BYTES = 1048576;

    /** The most games accepted in one go (A78, A80). */
    public const int MAX_GAMES = 50;

    /** The longest tag value kept (a player's name, an event). */
    public const int MAX_TAG_LENGTH = 100;

    /** The longest comment kept, in characters. */
    public const int MAX_COMMENT_LENGTH = 500;

    /** The only signs a comment may hold besides letters, digits and spaces (A90). */
    public const string COMMENT_SIGNS = ".,:;!?()-+=#/'";

    /** The only tags kept, in this order (A81). */
    public const array ALLOWED_TAGS = [
        'Event', 'Site', 'Date', 'Round', 'White', 'Black', 'Result',
        'WhiteElo', 'BlackElo', 'ECO', 'Opening', 'TimeControl', 'SetUp', 'FEN',
    ];

    public function __construct(private readonly WordFilter $wordFilter)
    {
    }

    /** Scans PGN text holding one game or many. */
    public function scan(string $pgnText): ScanResult
    {
        // --- 1. Size ---
        if (strlen($pgnText) > self::MAX_BYTES) {
            return new ScanResult([], ['the text is larger than 1 MB']);
        }
        if (!mb_check_encoding($pgnText, 'UTF-8')) {
            return new ScanResult([], ['the text is not UTF-8']);
        }
        $gameTexts = PgnReader::splitGames($pgnText);
        if ($gameTexts === []) {
            return new ScanResult([], ['no game found']);
        }
        if (count($gameTexts) > self::MAX_GAMES) {
            return new ScanResult([], ['more than ' . self::MAX_GAMES . ' games']);
        }

        $accepted = [];
        $rejected = [];
        foreach ($gameTexts as $number => $gameText) {
            $label = 'game ' . ($number + 1);
            try {
                // --- 2. Strict reading, 3. allowed tags only ---
                $game = PgnReader::readGame($gameText)->withOnlyTags(self::ALLOWED_TAGS);
                if ($game->sanMoves === []) {
                    throw new UnreadableGame('no moves');
                }

                // --- 4. and 5. Every kept text ---
                foreach ($game->tags as $name => $value) {
                    // SetUp and FEN were read as a position already: no free text there.
                    if ($name === 'SetUp' || $name === 'FEN') {
                        continue;
                    }
                    $problem = $this->problemWith($value);
                    if ($problem !== null) {
                        throw new UnreadableGame($name . ' ' . $problem);
                    }
                }

                // --- 6. Comments: plain text only ---
                foreach ($game->comments as $ply => $comment) {
                    $problem = $this->problemWithComment($comment);
                    if ($problem !== null) {
                        throw new UnreadableGame('the comment after half-move ' . $ply . ' ' . $problem);
                    }
                }
                $accepted[] = $game;
            } catch (UnreadableGame $reason) {
                $rejected[] = $label . ': ' . $reason->getMessage();
            }
        }

        return new ScanResult($accepted, $rejected);
    }

    /** What is wrong with a comment, or null when it may be kept. */
    private function problemWithComment(string $comment): ?string
    {
        if (mb_strlen($comment) > self::MAX_COMMENT_LENGTH) {
            return 'is too long';
        }
        $allowed = '/^[\p{L}\p{N} ' . preg_quote(self::COMMENT_SIGNS, '/') . ']*$/u';
        if (preg_match($allowed, $comment) !== 1) {
            return 'holds a sign that is not allowed';
        }

        return $this->problemWith($comment, false);
    }

    /** What is wrong with a tag value, or null when it may be kept. */
    private function problemWith(string $value, bool $isTag = true): ?string
    {
        if ($isTag && mb_strlen($value) > self::MAX_TAG_LENGTH) {
            return 'is too long';
        }
        if (preg_match('/[\x00-\x1F\x7F]/', $value) === 1) {
            return 'holds a control character';
        }
        // Lichess puts its own game address in "Site"; that one is allowed.
        $isLichessSite = preg_match('~^https://lichess\.org/[A-Za-z0-9]{8,12}$~', $value) === 1;
        if (!$isLichessSite && preg_match('~(https?:|www\.|://|\.[a-z]{2,6}/)~i', $value) === 1) {
            return 'holds a web address';
        }
        if (preg_match('/\S+@\S+\.\S+/', $value) === 1) {
            return 'holds an e-mail address';
        }
        if ($this->wordFilter->blocks($value)) {
            return 'holds a blocked word';
        }

        return null;
    }
}
