<?php

declare(strict_types=1);

namespace PhpChess\Domain\Study;

use PhpChess\Domain\ActionRefused;
use PhpChess\Domain\Chess\InvalidPosition;
use PhpChess\Domain\Chess\Position;
use PhpChess\Domain\Lichess\UnreadableGame;

/**
 * Reads and writes study PGN: moves with side lines, comments, symbols and
 * drawings (C6-1, A129, A131).
 *
 * PGN, the standard text format, writes a side line in round brackets
 * right after the move it replaces:
 *
 *     1. e4 e5 2. Nf3 (2. f4 { the King's Gambit } exf4) 2... Nc6 $1
 *
 * Here 2.f4 is a side line instead of 2.Nf3; "$1" is the symbol "!" on
 * 2...Nc6. Comments are in { }; drawings are commands inside a comment
 * ([%cal Ge2e4], see Drawings).
 *
 * read() replays every move, in every side line, with our own rules;
 * one illegal or unreadable move rejects the game (UnreadableGame). It
 * keeps the drawings and the comment text and drops every other [%...]
 * command (clock times, engine scores). The text checks (word filter,
 * web addresses) are StudyScanner's job, after reading.
 */
final class StudyPgn
{
    /** The results PGN knows. */
    public const array RESULTS = ['1-0', '0-1', '1/2-1/2', '*'];

    // ===== Reading =====

    /**
     * Reads one game (tags and move text) into a tree.
     *
     * @return array{tags: array<string, string>, tree: MoveTree, result: string}
     * @throws UnreadableGame
     */
    public static function read(string $gameText): array
    {
        // --- Tags: [Name "value"], then the move text ---
        $tags = [];
        $moveText = '';
        foreach (preg_split('/\r\n|\r|\n/', $gameText) ?: [] as $line) {
            $line = trim($line);
            if (str_starts_with($line, '[') && !str_starts_with($line, '[%')) {
                if (preg_match('/^\[([A-Za-z0-9_]+)\s+"((?:[^"\\\\]|\\\\.)*)"\s*\]$/', $line, $match) !== 1) {
                    throw new UnreadableGame('bad tag line');
                }
                $tags[$match[1]] = stripcslashes($match[2]);
            } else {
                $moveText .= $line . "\n";
            }
        }

        // --- The start position ---
        $startFen = Position::START_FEN;
        if (($tags['SetUp'] ?? '') === '1' && isset($tags['FEN'])) {
            try {
                $startFen = Position::fromFen($tags['FEN'])->toFen();
            } catch (InvalidPosition) {
                throw new UnreadableGame('bad FEN tag');
            }
        }

        $tree = new MoveTree($startFen);
        $result = self::readMoves($tree, self::tokens($moveText));

        return ['tags' => $tags, 'tree' => $tree, 'result' => $result];
    }

    /**
     * Plays the words into the tree. A stack remembers, for every open
     * "(", where the line outside it stood.
     *
     * @param list<string> $tokens
     */
    private static function readMoves(MoveTree $tree, array $tokens): string
    {
        $lastMove = 0;       // the move just played in this line (0: none yet)
        $stack = [];         // for each open "(": the line's last move outside it
        $result = '*';

        foreach ($tokens as $token) {
            // --- A side line starts: it replaces the move just played ---
            if ($token === '(') {
                $replaced = $tree->node($lastMove);
                if ($replaced === null || $lastMove === 0) {
                    throw new UnreadableGame('a side line before any move');
                }
                $stack[] = $lastMove;
                $lastMove = $replaced->parent;
                continue;
            }
            if ($token === ')') {
                if ($stack === []) {
                    throw new UnreadableGame('unbalanced variation');
                }
                $lastMove = array_pop($stack);
                continue;
            }

            // --- A comment: belongs to the move just played (0: before the first) ---
            if (str_starts_with($token, '{')) {
                self::addComment($tree, $lastMove, substr($token, 1));
                continue;
            }

            // --- A symbol: $1 ... ---
            if (preg_match('/^\$(\d+)$/', $token, $match) === 1) {
                $node = $tree->node($lastMove);
                if ($node !== null && $lastMove !== 0) {
                    $tree->setSymbols($lastMove, array_merge($node->symbols, [(int) $match[1]]));
                }
                continue;
            }

            if (in_array($token, self::RESULTS, true)) {
                if ($stack === []) {
                    $result = $token;
                }
                continue;
            }

            // --- A move, perhaps with ! or ? after it ---
            $suffixCode = null;
            foreach (Symbols::SUFFIXES as $suffix => $code) {
                if (str_ends_with($token, $suffix)) {
                    $suffixCode = $code;
                    $token = substr($token, 0, -strlen($suffix));
                    break;
                }
            }
            try {
                $lastMove = $tree->addMove($lastMove, $token);
            } catch (ActionRefused $refusal) {
                throw new UnreadableGame($refusal->languageKey === 'study.too_many_moves'
                    ? 'too many moves'
                    : 'illegal or unreadable move "' . mb_substr($token, 0, 12) . '"');
            }
            if ($suffixCode !== null) {
                $tree->setSymbols($lastMove, [$suffixCode]);
            }
        }
        if ($stack !== []) {
            throw new UnreadableGame('unbalanced variation');
        }

        return $result;
    }

    /** Adds a comment's drawings and text to a node (a second comment is added to the first). */
    private static function addComment(MoveTree $tree, int $number, string $comment): void
    {
        $node = $tree->node($number);
        if ($node === null) {
            return;
        }
        [$text, $drawings] = Drawings::takeFromComment($comment);
        // Every other command ([%clk 0:05:00], [%eval 0.3]) is dropped (A131).
        $text = trim((string) preg_replace('/\s+/u', ' ', (string) preg_replace('/\[%[^\]]*\]/', ' ', $text)));
        try {
            $tree->setComment($number, trim($node->comment . ' ' . $text));
        } catch (ActionRefused) {
            throw new UnreadableGame('a comment is too long');
        }
        $tree->setDrawings($number, array_merge($node->drawings, $drawings));
    }

    /**
     * The move text as words: "(", ")", "{comment text", "$1", moves and
     * results. Move numbers and ; comments are left out.
     *
     * @return list<string>
     */
    private static function tokens(string $moveText): array
    {
        $tokens = [];
        $length = strlen($moveText);
        $word = '';
        for ($i = 0; $i < $length; $i++) {
            $character = $moveText[$i];
            $endsWord = in_array($character, ['{', '}', '(', ')', ';', ' ', "\n", "\t", "\r"], true);
            if ($endsWord && $word !== '') {
                $tokens[] = $word;
                $word = '';
            }
            if ($character === '{') {
                $end = strpos($moveText, '}', $i);
                if ($end === false) {
                    throw new UnreadableGame('unclosed comment');
                }
                $tokens[] = '{' . substr($moveText, $i + 1, $end - $i - 1);
                $i = $end;
            } elseif ($character === '}') {
                throw new UnreadableGame('unbalanced comment');
            } elseif ($character === ';') {
                $end = strpos($moveText, "\n", $i);
                $i = $end === false ? $length : $end;
            } elseif ($character === '(' || $character === ')') {
                $tokens[] = $character;
            } elseif (!$endsWord) {
                $word .= $character;
            }
        }
        if ($word !== '') {
            $tokens[] = $word;
        }

        // --- Move numbers: "12." "12..." or glued to the move: "12.e4" ---
        $clean = [];
        foreach ($tokens as $token) {
            if (!str_starts_with($token, '{')) {
                $token = (string) preg_replace('/^\d+\.+/', '', $token);
                if ($token === '') {
                    continue;
                }
            }
            $clean[] = $token;
        }

        return $clean;
    }

    // ===== Writing =====

    /**
     * The board as PGN: tags, then the moves with side lines, symbols,
     * comments and drawings, then the result.
     *
     * @param array<string, string> $tags  in order; Result is set from $result
     */
    public static function write(array $tags, MoveTree $tree, string $result = '*'): string
    {
        $tags['Result'] = in_array($result, self::RESULTS, true) ? $result : '*';
        if ($tree->startFen !== Position::START_FEN) {
            $tags['SetUp'] = '1';
            $tags['FEN'] = $tree->startFen;
        }
        $lines = [];
        foreach ($tags as $name => $value) {
            $lines[] = '[' . $name . ' "' . str_replace(['\\', '"', "\n", "\r"], ['\\\\', '\\"', ' ', ' '], $value) . '"]';
        }

        $words = [];
        $start = $tree->node(0);
        $startComment = $start === null ? '' : self::commentOf($start);
        if ($startComment !== '') {
            $words[] = $startComment;
        }
        self::writeLine($tree, 0, true, $words);
        $words[] = $tags['Result'];

        return implode("\n", $lines) . "\n\n" . wordwrap(implode(' ', $words), 80, "\n") . "\n";
    }

    /**
     * Writes the moves after one node: the main move, its side lines in
     * brackets, then the main line on.
     *
     * @param list<string> $words
     */
    private static function writeLine(MoveTree $tree, int $parent, bool $needsNumber, array &$words): void
    {
        $children = $tree->childrenOf($parent);
        if ($children === []) {
            return;
        }
        $main = array_shift($children);
        $mainHadComment = self::writeMove($tree, $main, $needsNumber, $words);
        foreach ($children as $side) {
            // The brackets stand right against the side line: "(2. f4 exf4)".
            $first = count($words);
            $sideHadComment = self::writeMove($tree, $side, true, $words);
            self::writeLine($tree, $side, $sideHadComment, $words);
            $words[$first] = '(' . $words[$first];
            $words[count($words) - 1] .= ')';
        }
        // After a comment or a side line, Black's next move gets its number again: "2... Nc6".
        self::writeLine($tree, $main, $mainHadComment || $children !== [], $words);
    }

    /**
     * Writes one move: its number when needed, SAN, symbols, comment.
     * Returns true when a comment was written.
     *
     * @param list<string> $words
     */
    private static function writeMove(MoveTree $tree, int $number, bool $needsNumber, array &$words): bool
    {
        $node = $tree->node($number);
        $parent = $node === null ? null : $tree->node($node->parent);
        if ($node === null || $parent === null) {
            return false;
        }
        $before = Position::fromFen($parent->fenAfter);
        if ($before->sideToMove === Position::WHITE) {
            $words[] = $before->fullmoveNumber . '.';
        } elseif ($needsNumber) {
            $words[] = $before->fullmoveNumber . '...';
        }
        $words[] = $node->san;
        foreach ($node->symbols as $code) {
            $words[] = '$' . $code;
        }
        $comment = self::commentOf($node);
        if ($comment !== '') {
            $words[] = $comment;
        }

        return $comment !== '';
    }

    /** "{ [%csl Gd4] [%cal Ge2e4] text }", or '' when there is nothing to write. */
    private static function commentOf(StudyNode $node): string
    {
        $inside = trim(Drawings::toCommentPart($node->drawings) . ' ' . $node->comment);

        return $inside === '' ? '' : '{ ' . $inside . ' }';
    }
}
