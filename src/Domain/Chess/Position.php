<?php

declare(strict_types=1);

namespace PhpChess\Domain\Chess;

/**
 * A chess position: where the pieces stand, whose turn it is, castling
 * rights, the en passant square and the move counters (G-01, G-06, K1).
 *
 * A Position never changes; play() returns a new one. It is read from and
 * written to FEN text, the standard one-line description of a position.
 * It does not check whether a move is legal: MoveGenerator does that, and
 * Rules uses both to decide the game state.
 *
 * Pieces are FEN letters: upper case for White (K Q R B N P), lower case
 * for Black (k q r b n p), '' for an empty square. Squares are numbered
 * as in Move (a1 = 0 ... h8 = 63).
 */
final class Position
{
    public const string START_FEN = 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1';

    public const string WHITE = 'w';
    public const string BLACK = 'b';

    /**
     * @param array<int, string> $board  64 squares, each a FEN letter or ''
     * @param string $castling  the castling rights still open, from "KQkq", or ''
     * @param int|null $enPassantSquare  the square a pawn just skipped, or null
     */
    public function __construct(
        public readonly array $board,
        public readonly string $sideToMove,
        public readonly string $castling,
        public readonly ?int $enPassantSquare,
        public readonly int $halfmoveClock,
        public readonly int $fullmoveNumber,
    ) {
    }

    // --- Reading FEN ---

    /** The normal starting position. */
    public static function start(): self
    {
        return self::fromFen(self::START_FEN);
    }

    /**
     * Reads a FEN text. Throws InvalidPosition when it is not a well-formed FEN
     * or the position can't happen (not one king each, pawns on the last rank,
     * the side not to move in check). Rules::checkStartPosition() checks more.
     */
    public static function fromFen(string $fen): self
    {
        $fields = preg_split('/\s+/', trim($fen));
        if ($fields === false || count($fields) < 4 || count($fields) > 6) {
            throw new InvalidPosition('A FEN has 4 to 6 fields.');
        }
        [$placement, $side, $castling, $enPassant] = $fields;
        $halfmoveClock = $fields[4] ?? '0';
        $fullmoveNumber = $fields[5] ?? '1';

        $board = self::readPlacement($placement);

        if ($side !== self::WHITE && $side !== self::BLACK) {
            throw new InvalidPosition('The side to move must be w or b.');
        }
        if (preg_match('/^(-|K?Q?k?q?)$/', $castling) !== 1 || $castling === '') {
            throw new InvalidPosition('Castling rights must be "-" or letters from KQkq.');
        }
        $enPassantSquare = null;
        if ($enPassant !== '-') {
            $enPassantSquare = Move::squareNumber($enPassant);
            if ($enPassantSquare === null) {
                throw new InvalidPosition('The en passant field must be "-" or a square.');
            }
        }
        if (preg_match('/^\d{1,4}$/', $halfmoveClock) !== 1 || preg_match('/^\d{1,4}$/', $fullmoveNumber) !== 1) {
            throw new InvalidPosition('The move counters must be numbers.');
        }

        $position = new self(
            $board,
            $side,
            $castling === '-' ? '' : $castling,
            $enPassantSquare,
            (int) $halfmoveClock,
            max(1, (int) $fullmoveNumber),
        );
        $position->checkPlausible();

        return $position;
    }

    /**
     * The 64 squares from the first FEN field, which lists rank 8 first.
     *
     * @return array<int, string>
     */
    private static function readPlacement(string $placement): array
    {
        $ranks = explode('/', $placement);
        if (count($ranks) !== 8) {
            throw new InvalidPosition('The board must have 8 ranks.');
        }

        $board = array_fill(0, 64, '');
        foreach ($ranks as $rankIndex => $rankText) {
            $rank = 7 - $rankIndex;
            $file = 0;
            foreach (str_split($rankText) as $character) {
                if (ctype_digit($character)) {
                    $file += (int) $character;
                    continue;
                }
                if (strpos('KQRBNPkqrbnp', $character) === false || $file > 7) {
                    throw new InvalidPosition('Unknown piece or too many squares in a rank.');
                }
                $board[$rank * 8 + $file] = $character;
                $file++;
            }
            if ($file !== 8) {
                throw new InvalidPosition('Every rank must have 8 squares.');
            }
        }

        return $board;
    }

    /** Refuses positions that can never come up in a game. */
    private function checkPlausible(): void
    {
        $counts = array_count_values(array_filter($this->board, fn (string $piece): bool => $piece !== ''));
        if (($counts['K'] ?? 0) !== 1 || ($counts['k'] ?? 0) !== 1) {
            throw new InvalidPosition('Each side needs exactly one king.');
        }
        for ($file = 0; $file < 8; $file++) {
            foreach ([$file, 56 + $file] as $square) {
                if (strtolower($this->board[$square]) === 'p') {
                    throw new InvalidPosition('Pawns can not stand on the first or last rank.');
                }
            }
        }
        if (MoveGenerator::isInCheck($this, self::opposite($this->sideToMove))) {
            throw new InvalidPosition('The side not to move is in check.');
        }
    }

    // --- Writing FEN ---

    /** The position as FEN text. */
    public function toFen(): string
    {
        return $this->placementAndState() . ' ' . $this->halfmoveClock . ' ' . $this->fullmoveNumber;
    }

    /**
     * The first four FEN fields: what makes two positions "the same" for
     * threefold repetition. The en passant square is kept only when a pawn
     * can really take there, so a skipped square nobody can use does not
     * make two equal positions look different.
     */
    public function repetitionKey(): string
    {
        return $this->placementAndState(true);
    }

    private function placementAndState(bool $onlyUsableEnPassant = false): string
    {
        $ranks = [];
        for ($rank = 7; $rank >= 0; $rank--) {
            $text = '';
            $emptyCount = 0;
            for ($file = 0; $file < 8; $file++) {
                $piece = $this->board[$rank * 8 + $file];
                if ($piece === '') {
                    $emptyCount++;
                    continue;
                }
                if ($emptyCount > 0) {
                    $text .= (string) $emptyCount;
                    $emptyCount = 0;
                }
                $text .= $piece;
            }
            if ($emptyCount > 0) {
                $text .= (string) $emptyCount;
            }
            $ranks[] = $text;
        }

        $enPassant = '-';
        if ($this->enPassantSquare !== null && (!$onlyUsableEnPassant || $this->canTakeEnPassant())) {
            $enPassant = Move::squareName($this->enPassantSquare);
        }

        return implode('/', $ranks) . ' ' . $this->sideToMove . ' ' . ($this->castling === '' ? '-' : $this->castling) . ' ' . $enPassant;
    }

    private function canTakeEnPassant(): bool
    {
        foreach (MoveGenerator::legalMoves($this) as $move) {
            if ($move->to === $this->enPassantSquare && strtolower($this->board[$move->from]) === 'p') {
                return true;
            }
        }

        return false;
    }

    // --- Pieces ---

    /** The colour of a FEN letter: 'w' for upper case, 'b' for lower case. */
    public static function colourOf(string $piece): string
    {
        return ctype_upper($piece) ? self::WHITE : self::BLACK;
    }

    public static function opposite(string $colour): string
    {
        return $colour === self::WHITE ? self::BLACK : self::WHITE;
    }

    /** The square of the given side's king. */
    public function kingSquare(string $colour): int
    {
        $king = $colour === self::WHITE ? 'K' : 'k';
        $square = array_search($king, $this->board, true);

        return is_int($square) ? $square : -1;
    }

    // --- Playing a move ---

    /**
     * The position after the move. The move must be legal (from
     * MoveGenerator::legalMoves); this method does not check that.
     */
    public function play(Move $move): self
    {
        $board = $this->board;
        $piece = $board[$move->from];
        $kind = strtolower($piece);
        $captured = $board[$move->to];
        $isWhite = $this->sideToMove === self::WHITE;

        // The piece moves; a pawn on the last rank becomes the promotion piece.
        $board[$move->from] = '';
        $board[$move->to] = $move->promotion === '' ? $piece : ($isWhite ? strtoupper($move->promotion) : $move->promotion);

        // En passant: the pawn taken stands beside the square moved to.
        if ($kind === 'p' && $move->to === $this->enPassantSquare && $captured === '') {
            $board[$move->to + ($isWhite ? -8 : 8)] = '';
            $captured = 'p';
        }

        // Castling: the king moves two squares, the rook jumps over it.
        if ($kind === 'k' && abs($move->to - $move->from) === 2) {
            $isKingside = $move->to > $move->from;
            $rookFrom = $isKingside ? $move->from + 3 : $move->from - 4;
            $rookTo = $isKingside ? $move->from + 1 : $move->from - 1;
            $board[$rookTo] = $board[$rookFrom];
            $board[$rookFrom] = '';
        }

        // A pawn moving two squares leaves the skipped square for en passant.
        $enPassantSquare = null;
        if ($kind === 'p' && abs($move->to - $move->from) === 16) {
            $enPassantSquare = intdiv($move->from + $move->to, 2);
        }

        return new self(
            $board,
            self::opposite($this->sideToMove),
            self::castlingAfter($this->castling, $move),
            $enPassantSquare,
            ($kind === 'p' || $captured !== '') ? 0 : $this->halfmoveClock + 1,
            $isWhite ? $this->fullmoveNumber : $this->fullmoveNumber + 1,
        );
    }

    /** Castling rights are lost when the king or a rook moves, or a rook is taken on its corner. */
    private static function castlingAfter(string $castling, Move $move): string
    {
        $lostBySquare = [
            4 => 'KQ', 0 => 'Q', 7 => 'K',      // e1, a1, h1
            60 => 'kq', 56 => 'q', 63 => 'k',   // e8, a8, h8
        ];
        foreach ([$move->from, $move->to] as $square) {
            if (isset($lostBySquare[$square])) {
                $castling = str_replace(str_split($lostBySquare[$square]), '', $castling);
            }
        }

        return $castling;
    }
}
