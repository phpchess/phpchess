<?php

declare(strict_types=1);

namespace PhpChess\Domain\Study;

/**
 * Annotation symbols on the study board (C6-1, A129, A135).
 *
 * In PGN a symbol is written as a "NAG": a dollar sign and a number, for
 * example "$1" for "!" (a good move). We keep the codes $1 to $19, the
 * ones every chess program knows; higher codes are dropped on import.
 *
 * Two groups:
 *   - move symbols ($1-$6): how good the move was; at most one per move;
 *   - position symbols ($10-$19): who stands better; at most one per move.
 * $7-$9 (forced move, singular move, worst move) are kept on import but
 * have no button.
 */
final class Symbols
{
    /** The lowest and highest code kept. */
    public const int LOWEST = 1;
    public const int HIGHEST = 19;

    /** Move symbols: code => sign. */
    public const array MOVE = [
        1 => '!',
        2 => '?',
        3 => '!!',
        4 => '??',
        5 => '!?',
        6 => '?!',
    ];

    /** Position symbols: code => sign. */
    public const array POSITION = [
        10 => '=',
        11 => '=',
        12 => '=',
        13 => '∞',
        14 => '⩲',
        15 => '⩱',
        16 => '±',
        17 => '∓',
        18 => '+−',
        19 => '−+',
    ];

    /** The other kept codes, shown as their number ($7 forced, $8 singular, $9 worst). */
    public const array OTHER = [7 => '□', 8 => '□', 9 => '??'];

    /** The position symbols that get a button (11 and 12 look the same as 10). */
    public const array POSITION_BUTTONS = [10, 13, 14, 15, 16, 17, 18, 19];

    /** The move signs as they may stand right after a move in PGN ("e4!"), => their code. */
    public const array SUFFIXES = ['!!' => 3, '??' => 4, '!?' => 5, '?!' => 6, '!' => 1, '?' => 2];

    /** True for a code we keep. */
    public static function isKept(int $code): bool
    {
        return $code >= self::LOWEST && $code <= self::HIGHEST;
    }

    /** The sign shown for a code. */
    public static function sign(int $code): string
    {
        return self::MOVE[$code] ?? self::POSITION[$code] ?? self::OTHER[$code] ?? '';
    }

    /**
     * Tidies a list of codes: only kept codes, no repeats, at most one move
     * symbol and one position symbol (the last one given wins), in order.
     *
     * @param list<int> $codes
     * @return list<int>
     */
    public static function tidy(array $codes): array
    {
        $moveSymbol = null;
        $positionSymbol = null;
        $others = [];
        foreach ($codes as $code) {
            if (isset(self::MOVE[$code])) {
                $moveSymbol = $code;
            } elseif (isset(self::POSITION[$code])) {
                $positionSymbol = $code;
            } elseif (self::isKept($code) && !in_array($code, $others, true)) {
                $others[] = $code;
            }
        }
        $tidy = array_merge($moveSymbol === null ? [] : [$moveSymbol], $others, $positionSymbol === null ? [] : [$positionSymbol]);

        return $tidy;
    }
}
