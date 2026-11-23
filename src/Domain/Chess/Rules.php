<?php

declare(strict_types=1);

namespace PhpChess\Domain\Chess;

/**
 * The Rules module's front door (G-01, contract K1): is a move legal, and is
 * the game over by the rules of chess after it?
 *
 * Only this module decides chess. The move function (Games::move) asks it
 * about every move; the browser's own checks (chess.js) are only for quick
 * feedback.
 *
 * Endings decided here, on the board:
 * - checkmate and stalemate;
 * - insufficient material (neither side can mate: K v K, K+minor v K,
 *   K+B v K+B with bishops on the same colour);
 * - threefold repetition and the 50-move rule. The rules of chess let a
 *   player claim these; phpChess ends the game automatically, so nobody has
 *   to know how to claim (A19). The 75-move and fivefold rules are covered.
 */
final class Rules
{
    public const string ENDING_CHECKMATE = 'checkmate';
    public const string ENDING_STALEMATE = 'stalemate';
    public const string ENDING_INSUFFICIENT_MATERIAL = 'insufficient_material';
    public const string ENDING_THREEFOLD_REPETITION = 'threefold_repetition';
    public const string ENDING_FIFTY_MOVES = 'fifty_moves';

    // --- Moves ---

    /** The legal move for UCI text like "e2e4" in the position, or null when it is not legal. */
    public static function legalMoveFromUci(Position $position, string $uci): ?Move
    {
        $wanted = Move::fromUci($uci);

        return $wanted === null ? null : MoveGenerator::findLegal($position, $wanted);
    }

    // --- Is the game over? ---

    /**
     * How the game ends in this position, or null when it goes on.
     *
     * @param list<string> $repetitionKeys  Position::repetitionKey() of every
     *   position so far in the game, this one included
     */
    public static function ending(Position $position, array $repetitionKeys): ?string
    {
        if (MoveGenerator::legalMoves($position) === []) {
            return MoveGenerator::isInCheck($position, $position->sideToMove) ? self::ENDING_CHECKMATE : self::ENDING_STALEMATE;
        }
        if (self::isInsufficientMaterial($position)) {
            return self::ENDING_INSUFFICIENT_MATERIAL;
        }
        $timesSeen = array_count_values($repetitionKeys)[$position->repetitionKey()] ?? 0;
        if ($timesSeen >= 3) {
            return self::ENDING_THREEFOLD_REPETITION;
        }
        if ($position->halfmoveClock >= 100) {
            return self::ENDING_FIFTY_MOVES;
        }

        return null;
    }

    /** The winner's colour after checkmate: the side that just moved. */
    public static function winnerAfterCheckmate(Position $position): string
    {
        return Position::opposite($position->sideToMove);
    }

    // --- Material ---

    /** True when neither side can ever give mate. */
    public static function isInsufficientMaterial(Position $position): bool
    {
        return !self::canEverMate($position, Position::WHITE) && !self::canEverMate($position, Position::BLACK);
    }

    /**
     * True when the side still has the pieces to mate at all (with help).
     * Used for timeouts too (A33): when the side whose opponent ran out of
     * time can't mate, the game is a draw, not a win.
     *
     * A lone king can't. King and one knight, or king and bishops all on one
     * colour of square, can't either, unless the other side has pieces that
     * could block its own king (then a mate is possible in theory).
     */
    public static function canEverMate(Position $position, string $colour): bool
    {
        $ownPieces = [];
        $otherPieceCount = 0;
        $bishopSquareColours = [];
        foreach ($position->board as $square => $piece) {
            if ($piece === '' || strtolower($piece) === 'k') {
                continue;
            }
            if (Position::colourOf($piece) !== $colour) {
                $otherPieceCount++;
                continue;
            }
            $ownPieces[] = strtolower($piece);
            if (strtolower($piece) === 'b') {
                $bishopSquareColours[(intdiv($square, 8) + $square % 8) % 2] = true;
            }
        }

        foreach ($ownPieces as $kind) {
            if ($kind === 'p' || $kind === 'r' || $kind === 'q') {
                return true;
            }
        }
        $knightCount = count(array_filter($ownPieces, fn (string $kind): bool => $kind === 'n'));
        $bishopCount = count($ownPieces) - $knightCount;
        if ($knightCount >= 2 || ($knightCount >= 1 && $bishopCount >= 1) || count($bishopSquareColours) === 2) {
            return true;
        }
        if ($knightCount + $bishopCount === 0) {
            return false;
        }

        // One knight, or bishops on one colour: a mate needs the other side's
        // own pieces to block its king. Knight: any other piece will do.
        // Bishops: only if the other side has a piece that is not a bishop
        // on the same colour (a knight, or a pawn that can block).
        if ($knightCount === 1) {
            return $otherPieceCount > 0;
        }

        // Here all bishops stand on one colour of square: the one key of the list.
        foreach (array_keys($bishopSquareColours) as $onlyBishopColour) {
            return self::otherSideHasNonSameColourBishop($position, $colour, $onlyBishopColour);
        }

        return false;
    }

    private static function otherSideHasNonSameColourBishop(Position $position, string $colour, int $bishopSquareColour): bool
    {
        foreach ($position->board as $square => $piece) {
            if ($piece === '' || strtolower($piece) === 'k' || Position::colourOf($piece) === $colour) {
                continue;
            }
            $isSameColourBishop = strtolower($piece) === 'b' && (intdiv($square, 8) + $square % 8) % 2 === $bishopSquareColour;
            if (!$isSameColourBishop) {
                return true;
            }
        }

        return false;
    }
}
