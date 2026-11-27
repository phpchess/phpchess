<?php

declare(strict_types=1);

namespace PhpChess\Domain\Lichess;

use PhpChess\Domain\Chess\InvalidPosition;
use PhpChess\Domain\Chess\Move;
use PhpChess\Domain\Chess\MoveGenerator;
use PhpChess\Domain\Chess\Position;
use PhpChess\Domain\Chess\San;

/**
 * Reads PGN text, strictly (C5-1, L-03, A81).
 *
 * PGN is the standard text format for chess games: tag lines like
 * [White "Kasparov"], then the moves ("1. e4 e5 2. Nf3 ..."), then the
 * result. One file may hold many games.
 *
 * This reader is the first half of the content scanner (ContentScanner is
 * the second). It keeps tags, moves and the comments of the main line
 * (in { }, B38; the scanner checks their text strictly), and throws away:
 *   - comments after ; and comments inside variations;
 *   - Lichess's machine notes in comments, like [%eval 0.3] and [%clk 0:05:00];
 *   - variations in ( ) (side lines);
 *   - NAGs ($1, $2 ...) and the signs ! ? after moves.
 * Then it replays every move with our own rules. A move that is not legal
 * or can't be read makes the whole game unreadable: nothing of it is kept.
 *
 * splitGames() cuts a file into games; readGame() reads one.
 */
final class PgnReader
{
    /** The longest game we accept, in half-moves (a real game is far shorter). */
    public const int MAX_PLIES = 1000;

    /**
     * Cuts PGN text into one text per game. A new game starts at a tag line
     * that follows move text.
     *
     * @return list<string>
     */
    public static function splitGames(string $pgnText): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $pgnText) ?: [];
        $games = [];
        $current = '';
        $seenMoves = false;
        foreach ($lines as $line) {
            $isTag = str_starts_with(ltrim($line), '[');
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

    /**
     * Reads one game. Throws UnreadableGame with the reason when it can't.
     */
    public static function readGame(string $gameText): ReadGame
    {
        // --- Tags: [Name "value"] ---
        $tags = [];
        $moveText = '';
        foreach (preg_split('/\r\n|\r|\n/', $gameText) ?: [] as $line) {
            $line = trim($line);
            if (str_starts_with($line, '[')) {
                if (preg_match('/^\[([A-Za-z0-9_]+)\s+"((?:[^"\\\\]|\\\\.)*)"\s*\]$/', $line, $match) !== 1) {
                    throw new UnreadableGame('bad tag line');
                }
                $tags[$match[1]] = stripcslashes($match[2]);
            } else {
                $moveText .= $line . "\n";
            }
        }

        // --- The start position: the normal one, or the FEN tag ---
        $startFen = Position::START_FEN;
        try {
            if (($tags['SetUp'] ?? '') === '1' && isset($tags['FEN'])) {
                $position = Position::fromFen($tags['FEN']);
                $startFen = $position->toFen();
            } else {
                $position = Position::start();
            }
        } catch (InvalidPosition) {
            throw new UnreadableGame('bad FEN tag');
        }

        // --- Moves: remove comments, variations and signs, then replay ---
        $tokens = self::moveTokens($moveText);
        $sanMoves = [];
        $comments = [];
        $result = '*';
        foreach ($tokens as $token) {
            // A comment belongs after the moves played so far (0: before the first).
            if (str_starts_with($token, '{')) {
                $text = self::commentText($token);
                if ($text !== '') {
                    $ply = count($sanMoves);
                    $comments[$ply] = isset($comments[$ply]) ? $comments[$ply] . ' ' . $text : $text;
                }
                continue;
            }
            if (in_array($token, ['1-0', '0-1', '1/2-1/2', '*'], true)) {
                $result = $token;
                break;
            }
            $move = self::findMove($position, $token);
            if ($move === null) {
                throw new UnreadableGame('illegal or unreadable move "' . mb_substr($token, 0, 12) . '"');
            }
            $sanMoves[] = San::of($position, $move);
            $position = $position->play($move);
            if (count($sanMoves) > self::MAX_PLIES) {
                throw new UnreadableGame('too many moves');
            }
        }

        return new ReadGame($tags, $startFen, $sanMoves, $result, $comments);
    }

    /**
     * The move text as a list of words: SAN moves, the result, and the
     * main line's comments (each one word starting with "{"). Variations,
     * ; comments, move numbers and signs are left out.
     *
     * @return list<string>
     */
    private static function moveTokens(string $moveText): array
    {
        // --- Walk the text once: comments, variations, the rest ---
        $mainLine = '';          // the move text outside comments and variations
        $comments = [];          // the main line's comments, in order
        $depth = 0;              // how deep inside ( ) we are
        $length = strlen($moveText);
        for ($i = 0; $i < $length; $i++) {
            $character = $moveText[$i];
            if ($character === '{') {
                $end = strpos($moveText, '}', $i);
                if ($end === false) {
                    throw new UnreadableGame('unclosed comment');
                }
                if ($depth === 0) {
                    // A marker in the move text keeps the comment in its place.
                    $mainLine .= ' {' . count($comments) . ' ';
                    $comments[] = substr($moveText, $i + 1, $end - $i - 1);
                }
                $i = $end;
            } elseif ($character === '}') {
                throw new UnreadableGame('unbalanced comment');
            } elseif ($character === ';') {
                $end = strpos($moveText, "\n", $i);
                $i = $end === false ? $length : $end;
            } elseif ($character === '(') {
                $depth++;
            } elseif ($character === ')') {
                $depth--;
                if ($depth < 0) {
                    throw new UnreadableGame('unbalanced variation');
                }
            } elseif ($depth === 0) {
                $mainLine .= $character;
            }
        }
        if ($depth !== 0) {
            throw new UnreadableGame('unbalanced variation');
        }

        // --- NAGs ($1), move numbers (12. or 12...), and the signs ! ? ---
        $text = (string) preg_replace('/\$\d+/', ' ', $mainLine);
        $text = (string) preg_replace('/\b\d+\.(\.\.)?/', ' ', $text);
        $text = str_replace(['!', '?'], '', $text);

        // --- Words; a marker "{3" becomes "{" and the fourth comment's text ---
        $words = [];
        foreach (preg_split('/\s+/', trim($text)) ?: [] as $word) {
            if ($word === '') {
                continue;
            }
            if (preg_match('/^\{(\d+)$/', $word, $match) === 1) {
                $word = '{' . $comments[(int) $match[1]];
            }
            $words[] = $word;
        }

        return $words;
    }

    /**
     * A comment's text: Lichess's machine notes ([%eval ...], [%clk ...])
     * removed and the spaces tidied. The scanner checks what is left.
     */
    private static function commentText(string $token): string
    {
        $text = (string) preg_replace('/\[%[^\]]*\]/', ' ', substr($token, 1));

        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }

    /**
     * The legal move a SAN word names, or null (also used by the study board,
     * C6-1). Every legal move is written
     * in SAN by our own San class and compared, so only real moves match.
     */
    public static function findMove(Position $position, string $sanWord): ?Move
    {
        $wanted = self::normalise($sanWord);

        // For speed, only moves to the square the word names are written out
        // (castling has no square in its word, so all moves are tried then).
        $targetSquare = null;
        if (preg_match('/([a-h][1-8])(=[QRBN])?$/', $wanted, $match) === 1) {
            $targetSquare = Move::squareNumber($match[1]);
        }

        foreach (MoveGenerator::legalMoves($position) as $move) {
            if ($targetSquare !== null && $move->to !== $targetSquare) {
                continue;
            }
            if (self::normalise(San::of($position, $move)) === $wanted) {
                return $move;
            }
        }

        return null;
    }

    /** SAN without check signs, with "0-0" written "O-O" and "e8Q" written "e8=Q". */
    private static function normalise(string $san): string
    {
        $san = rtrim($san, '+#');
        if ($san === '0-0') {
            return 'O-O';
        }
        if ($san === '0-0-0') {
            return 'O-O-O';
        }

        // A promotion written without "=": "e8Q" or "dxe8Q".
        return (string) preg_replace('/^([a-h](x[a-h])?[18])([QRBN])$/', '$1=$3', $san);
    }
}
