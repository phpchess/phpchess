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
 * the second). It throws away everything that is not a tag or a move:
 *   - comments in { } and after ; (text anyone could have written);
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
        $result = '*';
        foreach ($tokens as $token) {
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

        return new ReadGame($tags, $startFen, $sanMoves, $result);
    }

    /**
     * The move text as a list of words: SAN moves and the result, with
     * comments, variations, move numbers and signs left out.
     *
     * @return list<string>
     */
    private static function moveTokens(string $moveText): array
    {
        // Comments: { ... } and ; to the end of the line.
        $text = (string) preg_replace('/\{[^}]*\}/s', ' ', $moveText);
        $text = (string) preg_replace('/;[^\n]*/', ' ', $text);
        if (str_contains($text, '{') || str_contains($text, '}')) {
            throw new UnreadableGame('unclosed comment');
        }

        // Variations: ( ... ), which may hold more variations inside.
        $withoutVariations = '';
        $depth = 0;
        foreach (str_split($text) as $character) {
            if ($character === '(') {
                $depth++;
            } elseif ($character === ')') {
                $depth--;
                if ($depth < 0) {
                    throw new UnreadableGame('unbalanced variation');
                }
            } elseif ($depth === 0) {
                $withoutVariations .= $character;
            }
        }
        if ($depth !== 0) {
            throw new UnreadableGame('unbalanced variation');
        }

        // NAGs ($1), move numbers (12. or 12...), and the signs ! ?
        $text = (string) preg_replace('/\$\d+/', ' ', $withoutVariations);
        $text = (string) preg_replace('/\b\d+\.(\.\.)?/', ' ', $text);
        $text = str_replace(['!', '?'], '', $text);

        $words = preg_split('/\s+/', trim($text)) ?: [];

        return array_values(array_filter($words, static fn (string $word): bool => $word !== ''));
    }

    /**
     * The legal move a SAN word names, or null. Every legal move is written
     * in SAN by our own San class and compared, so only real moves match.
     */
    private static function findMove(Position $position, string $sanWord): ?Move
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
