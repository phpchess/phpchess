<?php

declare(strict_types=1);

namespace PhpChess\Domain\Study;

/**
 * One move in a study board's move tree (C6-1, A132).
 *
 * Every node has a number, unique within its board. Node 0 is the start
 * position: it has no move, but it can hold a comment and drawings for
 * the position before the first move.
 *
 * - parent:   the node this move is played from (0 for the first move);
 * - san:      the move in SAN ("Nf3"), '' for node 0;
 * - uci:      the same move as squares ("g1f3"), for the board's markers;
 * - fenAfter: the position after the move (for node 0: the start position);
 * - symbols:  the annotation codes, see Symbols ($1-$19);
 * - comment:  plain text, without the drawing commands;
 * - drawings: arrows and circles, see Drawings ("Ge2e4", "Rd4");
 * - authorId: who added the move (lesson games, C6-4: both sides annotate), or null.
 *
 * The node's children are kept by the tree, not here: the first child is
 * the main line, the others are side lines (variations).
 */
final class StudyNode
{
    /**
     * @param list<int> $symbols
     * @param list<string> $drawings
     */
    public function __construct(
        public readonly int $number,
        public readonly int $parent,
        public readonly string $san,
        public readonly string $uci,
        public readonly string $fenAfter,
        public array $symbols = [],
        public string $comment = '',
        public array $drawings = [],
        public readonly ?int $authorId = null,
    ) {
    }
}
