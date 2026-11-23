<?php

declare(strict_types=1);

namespace PhpChess\Domain\Chess;

/**
 * Standard algebraic notation (SAN): the way moves are written for people
 * and in PGN files, for example "e4", "Nf3", "exd5", "O-O", "e8=Q+" (G-01, G-11).
 *
 * Only writing is needed: the server receives moves as UCI text (Move) and
 * stores both the UCI and the SAN of each one.
 */
final class San
{
    /** The SAN of a legal move in the position, with "+" for check and "#" for mate. */
    public static function of(Position $position, Move $move): string
    {
        $text = self::withoutCheckSign($position, $move);

        $after = $position->play($move);
        if (MoveGenerator::isInCheck($after, $after->sideToMove)) {
            $text .= MoveGenerator::legalMoves($after) === [] ? '#' : '+';
        }

        return $text;
    }

    private static function withoutCheckSign(Position $position, Move $move): string
    {
        $piece = $position->board[$move->from];
        $kind = strtolower($piece);
        $isCapture = $position->board[$move->to] !== '' || ($kind === 'p' && $move->to === $position->enPassantSquare);
        $target = Move::squareName($move->to);

        // --- Castling ---
        if ($kind === 'k' && abs($move->to - $move->from) === 2) {
            return $move->to > $move->from ? 'O-O' : 'O-O-O';
        }

        // --- Pawns: the file is written only when taking ---
        if ($kind === 'p') {
            $text = $isCapture ? Move::squareName($move->from)[0] . 'x' . $target : $target;

            return $move->promotion === '' ? $text : $text . '=' . strtoupper($move->promotion);
        }

        // --- Other pieces: letter, then file or rank only if another same piece could go there too ---
        return strtoupper($kind) . self::disambiguation($position, $move, $piece) . ($isCapture ? 'x' : '') . $target;
    }

    /** The file, the rank, or both, of the moving piece, when needed to tell it apart. */
    private static function disambiguation(Position $position, Move $move, string $piece): string
    {
        $sameFile = false;
        $sameRank = false;
        $isAmbiguous = false;
        foreach (MoveGenerator::legalMoves($position) as $other) {
            if ($other->to !== $move->to || $other->from === $move->from || $position->board[$other->from] !== $piece) {
                continue;
            }
            $isAmbiguous = true;
            if ($other->from % 8 === $move->from % 8) {
                $sameFile = true;
            }
            if (intdiv($other->from, 8) === intdiv($move->from, 8)) {
                $sameRank = true;
            }
        }

        $fromName = Move::squareName($move->from);
        if (!$isAmbiguous) {
            return '';
        }
        if (!$sameFile) {
            return $fromName[0];
        }
        if (!$sameRank) {
            return $fromName[1];
        }

        return $fromName;
    }
}
