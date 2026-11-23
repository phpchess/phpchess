<?php

declare(strict_types=1);

namespace PhpChess\Domain\Chess;

/**
 * Finds every legal move in a position, and whether a square is attacked
 * (G-01, contract K1: the server decides chess).
 *
 * How it works, in two steps:
 * 1. "Candidate" moves: every move the pieces could make by how they move,
 *    ignoring whether the own king is left in check.
 * 2. Each candidate is played on a copy of the position; it is legal when
 *    the own king is not attacked afterwards.
 * This is not the fastest way, but it is the easiest to read and to trust.
 * The perft tests (tests/Chess/PerftTest.php) count the moves to a known
 * total, which proves it right.
 *
 * Directions are steps on the board as (file change, rank change), so a
 * step never wraps around from the h-file to the a-file.
 */
final class MoveGenerator
{
    private const array KNIGHT_STEPS = [[1, 2], [2, 1], [2, -1], [1, -2], [-1, -2], [-2, -1], [-2, 1], [-1, 2]];
    private const array KING_STEPS = [[1, 0], [1, 1], [0, 1], [-1, 1], [-1, 0], [-1, -1], [0, -1], [1, -1]];
    private const array ROOK_DIRECTIONS = [[1, 0], [-1, 0], [0, 1], [0, -1]];
    private const array BISHOP_DIRECTIONS = [[1, 1], [1, -1], [-1, 1], [-1, -1]];
    private const array PROMOTION_PIECES = ['q', 'r', 'b', 'n'];

    // --- Legal moves ---

    /**
     * Every legal move for the side to move.
     *
     * @return list<Move>
     */
    public static function legalMoves(Position $position): array
    {
        $legal = [];
        foreach (self::candidateMoves($position) as $move) {
            $after = $position->play($move);
            if (!self::isInCheck($after, $position->sideToMove)) {
                $legal[] = $move;
            }
        }

        return $legal;
    }

    /** The legal move matching the given one (same squares and promotion), or null when it is not legal. */
    public static function findLegal(Position $position, Move $wanted): ?Move
    {
        foreach (self::legalMoves($position) as $move) {
            if ($move->equals($wanted)) {
                return $move;
            }
        }

        return null;
    }

    // --- Check and attacks ---

    /** True when the king of the given colour is attacked. */
    public static function isInCheck(Position $position, string $colour): bool
    {
        $kingSquare = $position->kingSquare($colour);

        return $kingSquare >= 0 && self::isAttacked($position, $kingSquare, Position::opposite($colour));
    }

    /** True when a piece of the attacking colour attacks the square. */
    public static function isAttacked(Position $position, int $square, string $attacker): bool
    {
        $board = $position->board;
        $isWhite = $attacker === Position::WHITE;
        $file = $square % 8;
        $rank = intdiv($square, 8);

        // Pawns attack diagonally forward, so look one rank "behind" the square.
        $pawnRank = $isWhite ? $rank - 1 : $rank + 1;
        foreach ([-1, 1] as $fileStep) {
            $pawnSquare = self::squareAt($file + $fileStep, $pawnRank);
            if ($pawnSquare !== null && $board[$pawnSquare] === ($isWhite ? 'P' : 'p')) {
                return true;
            }
        }

        foreach (self::KNIGHT_STEPS as [$fileStep, $rankStep]) {
            $from = self::squareAt($file + $fileStep, $rank + $rankStep);
            if ($from !== null && $board[$from] === ($isWhite ? 'N' : 'n')) {
                return true;
            }
        }

        foreach (self::KING_STEPS as [$fileStep, $rankStep]) {
            $from = self::squareAt($file + $fileStep, $rank + $rankStep);
            if ($from !== null && $board[$from] === ($isWhite ? 'K' : 'k')) {
                return true;
            }
        }

        // Sliding pieces: walk each line until the first piece.
        $straight = $isWhite ? ['R', 'Q'] : ['r', 'q'];
        $diagonal = $isWhite ? ['B', 'Q'] : ['b', 'q'];
        foreach ([[self::ROOK_DIRECTIONS, $straight], [self::BISHOP_DIRECTIONS, $diagonal]] as [$directions, $sliders]) {
            foreach ($directions as [$fileStep, $rankStep]) {
                $currentFile = $file + $fileStep;
                $currentRank = $rank + $rankStep;
                while (($from = self::squareAt($currentFile, $currentRank)) !== null) {
                    if ($board[$from] !== '') {
                        if (in_array($board[$from], $sliders, true)) {
                            return true;
                        }
                        break;
                    }
                    $currentFile += $fileStep;
                    $currentRank += $rankStep;
                }
            }
        }

        return false;
    }

    // --- Candidate moves ---

    /**
     * Every move by how the pieces move, before checking the own king.
     *
     * @return list<Move>
     */
    private static function candidateMoves(Position $position): array
    {
        $moves = [];
        foreach ($position->board as $square => $piece) {
            if ($piece === '' || Position::colourOf($piece) !== $position->sideToMove) {
                continue;
            }
            switch (strtolower($piece)) {
                case 'p':
                    self::addPawnMoves($position, $square, $moves);
                    break;
                case 'n':
                    self::addStepMoves($position, $square, self::KNIGHT_STEPS, $moves);
                    break;
                case 'b':
                    self::addSlidingMoves($position, $square, self::BISHOP_DIRECTIONS, $moves);
                    break;
                case 'r':
                    self::addSlidingMoves($position, $square, self::ROOK_DIRECTIONS, $moves);
                    break;
                case 'q':
                    self::addSlidingMoves($position, $square, [...self::ROOK_DIRECTIONS, ...self::BISHOP_DIRECTIONS], $moves);
                    break;
                case 'k':
                    self::addStepMoves($position, $square, self::KING_STEPS, $moves);
                    self::addCastlingMoves($position, $square, $moves);
                    break;
            }
        }

        return $moves;
    }

    /**
     * @param list<Move> $moves
     */
    private static function addPawnMoves(Position $position, int $from, array &$moves): void
    {
        $isWhite = $position->sideToMove === Position::WHITE;
        $direction = $isWhite ? 1 : -1;
        $startRank = $isWhite ? 1 : 6;
        $file = $from % 8;
        $rank = intdiv($from, 8);

        // One step forward, and two from the starting rank, onto empty squares.
        $oneStep = self::squareAt($file, $rank + $direction);
        if ($oneStep !== null && $position->board[$oneStep] === '') {
            self::addPawnMove($from, $oneStep, $moves);
            $twoSteps = self::squareAt($file, $rank + 2 * $direction);
            if ($rank === $startRank && $twoSteps !== null && $position->board[$twoSteps] === '') {
                $moves[] = new Move($from, $twoSteps);
            }
        }

        // Taking diagonally: an enemy piece, or the en passant square.
        foreach ([-1, 1] as $fileStep) {
            $to = self::squareAt($file + $fileStep, $rank + $direction);
            if ($to === null) {
                continue;
            }
            $target = $position->board[$to];
            $isEnemy = $target !== '' && Position::colourOf($target) !== $position->sideToMove;
            if ($isEnemy || $to === $position->enPassantSquare) {
                self::addPawnMove($from, $to, $moves);
            }
        }
    }

    /**
     * A pawn move; on the last rank it becomes four moves, one per promotion piece.
     *
     * @param list<Move> $moves
     */
    private static function addPawnMove(int $from, int $to, array &$moves): void
    {
        $toRank = intdiv($to, 8);
        if ($toRank === 0 || $toRank === 7) {
            foreach (self::PROMOTION_PIECES as $piece) {
                $moves[] = new Move($from, $to, $piece);
            }

            return;
        }
        $moves[] = new Move($from, $to);
    }

    /**
     * Knight and king: one step in each direction, onto an empty or enemy square.
     *
     * @param list<array{int, int}> $steps
     * @param list<Move> $moves
     */
    private static function addStepMoves(Position $position, int $from, array $steps, array &$moves): void
    {
        foreach ($steps as [$fileStep, $rankStep]) {
            $to = self::squareAt($from % 8 + $fileStep, intdiv($from, 8) + $rankStep);
            if ($to !== null && self::canLandOn($position, $to)) {
                $moves[] = new Move($from, $to);
            }
        }
    }

    /**
     * Bishop, rook and queen: along each line until a piece blocks.
     *
     * @param list<array{int, int}> $directions
     * @param list<Move> $moves
     */
    private static function addSlidingMoves(Position $position, int $from, array $directions, array &$moves): void
    {
        foreach ($directions as [$fileStep, $rankStep]) {
            $file = $from % 8 + $fileStep;
            $rank = intdiv($from, 8) + $rankStep;
            while (($to = self::squareAt($file, $rank)) !== null) {
                if ($position->board[$to] === '') {
                    $moves[] = new Move($from, $to);
                } else {
                    if (self::canLandOn($position, $to)) {
                        $moves[] = new Move($from, $to);
                    }
                    break;
                }
                $file += $fileStep;
                $rank += $rankStep;
            }
        }
    }

    /**
     * Castling: the right is still open, the rook is in its corner, the
     * squares between are empty, and the king is not in check, does not
     * pass through an attacked square and does not land on one.
     *
     * @param list<Move> $moves
     */
    private static function addCastlingMoves(Position $position, int $kingSquare, array &$moves): void
    {
        $isWhite = $position->sideToMove === Position::WHITE;
        $homeSquare = $isWhite ? 4 : 60;
        if ($kingSquare !== $homeSquare) {
            return;
        }
        $enemy = Position::opposite($position->sideToMove);
        $rook = $isWhite ? 'R' : 'r';
        $board = $position->board;

        $sides = [
            // right letter, rook square, squares that must be empty, squares the king crosses
            [$isWhite ? 'K' : 'k', $homeSquare + 3, [$homeSquare + 1, $homeSquare + 2], [$homeSquare + 1, $homeSquare + 2]],
            [$isWhite ? 'Q' : 'q', $homeSquare - 4, [$homeSquare - 1, $homeSquare - 2, $homeSquare - 3], [$homeSquare - 1, $homeSquare - 2]],
        ];
        foreach ($sides as [$right, $rookSquare, $mustBeEmpty, $kingCrosses]) {
            if (!str_contains($position->castling, $right) || $board[$rookSquare] !== $rook) {
                continue;
            }
            foreach ($mustBeEmpty as $square) {
                if ($board[$square] !== '') {
                    continue 2;
                }
            }
            if (self::isAttacked($position, $homeSquare, $enemy)) {
                return;
            }
            foreach ($kingCrosses as $square) {
                if (self::isAttacked($position, $square, $enemy)) {
                    continue 2;
                }
            }
            $moves[] = new Move($homeSquare, $kingCrosses[1]);
        }
    }

    // --- Helpers ---

    /** True when the square is empty or holds an enemy piece. */
    private static function canLandOn(Position $position, int $square): bool
    {
        $piece = $position->board[$square];

        return $piece === '' || Position::colourOf($piece) !== $position->sideToMove;
    }

    /** The square number for a file and rank, or null when that is off the board. */
    private static function squareAt(int $file, int $rank): ?int
    {
        if ($file < 0 || $file > 7 || $rank < 0 || $rank > 7) {
            return null;
        }

        return $rank * 8 + $file;
    }
}
