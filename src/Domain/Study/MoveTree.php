<?php

declare(strict_types=1);

namespace PhpChess\Domain\Study;

use PhpChess\Domain\ActionRefused;
use PhpChess\Domain\Chess\InvalidPosition;
use PhpChess\Domain\Chess\Move;
use PhpChess\Domain\Chess\MoveGenerator;
use PhpChess\Domain\Chess\Position;
use PhpChess\Domain\Chess\San;
use PhpChess\Domain\Lichess\PgnReader;

/**
 * The moves of a study board as a tree, with side lines (C6-1, A121, A132).
 *
 * A game is one line of moves. A study adds side lines: "instead of 3.Bc4,
 * 3.Bb5 was also possible". So every move can have several next moves.
 * The first next move is the main line, the others are side lines, in
 * order. This is the same as PGN's ( ) variations.
 *
 * What the tree can do, as on Lichess (A129):
 *   - play a move (a new one becomes a new side line, a known one is reused);
 *   - delete a move, with everything after it;
 *   - promote a move: make its line the main line at that point;
 *   - set a move's comment, symbols and drawings.
 *
 * Every move is checked with our own rules (K1) when it is added, so a
 * tree never holds an illegal move. Limits: MAX_MOVES moves (A135).
 *
 * The browser edits its own copy and sends the whole tree to save;
 * fromList() builds it again here and checks every move (never trusted).
 */
final class MoveTree
{
    /** The most moves on one board (A135). */
    public const int MAX_MOVES = 1000;

    /** The longest comment, in characters (A135). */
    public const int MAX_COMMENT_LENGTH = 2000;

    /** @var array<int, StudyNode> number => node */
    private array $nodes = [];

    /** @var array<int, list<int>> number => the numbers of its next moves, main line first */
    private array $children = [];

    /** The number the next new move gets. */
    private int $nextNumber = 1;

    public function __construct(public readonly string $startFen = Position::START_FEN)
    {
        $this->nodes[0] = new StudyNode(0, 0, '', '', $startFen);
        $this->children[0] = [];
    }

    // ===== Reading =====

    public function node(int $number): ?StudyNode
    {
        return $this->nodes[$number] ?? null;
    }

    /** @return list<int> the next moves after a node, main line first */
    public function childrenOf(int $number): array
    {
        return $this->children[$number] ?? [];
    }

    /** How many moves the tree holds (node 0 is not a move). */
    public function moveCount(): int
    {
        return count($this->nodes) - 1;
    }

    /** @return list<int> the main line from the start: node numbers, without 0 */
    public function mainLine(): array
    {
        $line = [];
        $number = 0;
        while (($this->children[$number] ?? []) !== []) {
            $number = $this->children[$number][0];
            $line[] = $number;
        }

        return $line;
    }

    /**
     * Every node, parents before children, main line first at each point:
     * the order used to store the tree and to send it to the browser.
     *
     * @return list<StudyNode>
     */
    public function inOrder(): array
    {
        $list = [];
        $waiting = [0];
        while ($waiting !== []) {
            $number = array_shift($waiting);
            $list[] = $this->nodes[$number];
            $waiting = array_merge($waiting, $this->children[$number]);
        }

        return $list;
    }

    // ===== Changing =====

    /**
     * Plays a move after a node, given in SAN or as a Move. Returns the
     * new node's number, or the number of the same move if it was there
     * already. Throws ActionRefused for an illegal move.
     */
    public function addMove(int $parent, string|Move $move, ?int $authorId = null, ?int $number = null): int
    {
        $parentNode = $this->nodes[$parent] ?? throw new ActionRefused('study.bad_tree');
        try {
            $position = Position::fromFen($parentNode->fenAfter);
        } catch (InvalidPosition) {
            throw new ActionRefused('study.bad_tree');
        }
        $legalMove = is_string($move) ? PgnReader::findMove($position, $move) : $move;
        if ($legalMove === null || !self::isLegal($position, $legalMove)) {
            throw new ActionRefused('study.illegal_move');
        }
        $san = San::of($position, $legalMove);

        // --- The same move played again: no new side line ---
        foreach ($this->children[$parent] as $childNumber) {
            if ($this->nodes[$childNumber]->san === $san) {
                return $childNumber;
            }
        }
        if ($this->moveCount() >= self::MAX_MOVES) {
            throw new ActionRefused('study.too_many_moves');
        }

        $number ??= $this->nextNumber;
        if (isset($this->nodes[$number]) || $number < 1) {
            throw new ActionRefused('study.bad_tree');
        }
        $this->nextNumber = max($this->nextNumber, $number + 1);
        $this->nodes[$number] = new StudyNode($number, $parent, $san, $legalMove->toUci(), $position->play($legalMove)->toFen(), authorId: $authorId);
        $this->children[$number] = [];
        $this->children[$parent][] = $number;

        return $number;
    }

    /** Deletes a move and everything after it. The start (node 0) can't be deleted. */
    public function delete(int $number): void
    {
        $node = $this->nodes[$number] ?? null;
        if ($node === null || $number === 0) {
            return;
        }
        $this->children[$node->parent] = array_values(array_filter(
            $this->children[$node->parent],
            static fn (int $child): bool => $child !== $number,
        ));
        $waiting = [$number];
        while ($waiting !== []) {
            $gone = array_shift($waiting);
            $waiting = array_merge($waiting, $this->children[$gone]);
            unset($this->nodes[$gone], $this->children[$gone]);
        }
    }

    /**
     * Promotes a move: its side line becomes the main line at every point
     * from the start to this move, as Lichess's "make main line" does.
     */
    public function promote(int $number): void
    {
        while ($number !== 0 && isset($this->nodes[$number])) {
            $parent = $this->nodes[$number]->parent;
            $others = array_values(array_filter($this->children[$parent], static fn (int $child): bool => $child !== $number));
            $this->children[$parent] = array_merge([$number], $others);
            $number = $parent;
        }
    }

    /** Sets a node's comment: plain text, spaces tidied, at most MAX_COMMENT_LENGTH. */
    public function setComment(int $number, string $comment): void
    {
        $node = $this->nodes[$number] ?? throw new ActionRefused('study.bad_tree');
        $text = trim((string) preg_replace('/\s+/u', ' ', $comment));
        if (mb_strlen($text) > self::MAX_COMMENT_LENGTH) {
            throw new ActionRefused('study.comment_too_long');
        }
        // A brace would end the comment early in PGN: it can't be part of the text.
        $node->comment = str_replace(['{', '}'], ['(', ')'], $text);
    }

    /** @param list<int> $codes */
    public function setSymbols(int $number, array $codes): void
    {
        $node = $this->nodes[$number] ?? throw new ActionRefused('study.bad_tree');
        if ($number === 0) {
            return;
        }
        $node->symbols = Symbols::tidy($codes);
    }

    /** @param list<string> $drawings */
    public function setDrawings(int $number, array $drawings): void
    {
        $node = $this->nodes[$number] ?? throw new ActionRefused('study.bad_tree');
        $node->drawings = Drawings::tidy($drawings);
    }

    // ===== From the browser =====

    /**
     * Builds a tree from the list the browser sends (study-board.js) and
     * checks everything in it. Each entry: {n: number, p: parent, san,
     * s: symbol codes, c: comment, d: drawings}. Parents come before their
     * children; the order of the children is the order in the list.
     * Entry 0 (the start) carries only the start's comment and drawings.
     *
     * @param list<mixed> $entries
     * @param array<int, ?int> $authors  node number => who added it, kept from the stored tree
     */
    public static function fromList(string $startFen, array $entries, ?int $newAuthorId = null, array $authors = []): self
    {
        $tree = new self($startFen);
        foreach ($entries as $entry) {
            if (!is_array($entry) || !is_int($entry['n'] ?? null)) {
                throw new ActionRefused('study.bad_tree');
            }
            $number = $entry['n'];
            if ($number !== 0) {
                $parent = $entry['p'] ?? null;
                $san = $entry['san'] ?? null;
                if (!is_int($parent) || !is_string($san) || !isset($tree->nodes[$parent])) {
                    throw new ActionRefused('study.bad_tree');
                }
                $author = array_key_exists($number, $authors) ? $authors[$number] : $newAuthorId;
                if ($tree->addMove($parent, $san, $author, $number) !== $number) {
                    throw new ActionRefused('study.bad_tree');
                }
            }
            $tree->setComment($number, is_string($entry['c'] ?? null) ? $entry['c'] : '');
            $tree->setSymbols($number, array_values(array_filter((array) ($entry['s'] ?? []), 'is_int')));
            $tree->setDrawings($number, array_values(array_filter((array) ($entry['d'] ?? []), 'is_string')));
        }

        return $tree;
    }

    /**
     * The tree as the browser reads it: the same shape fromList() takes,
     * plus the position (f) and the move's squares (u).
     *
     * @return list<array{n: int, p: int, san: string, u: string, f: string, s: list<int>, c: string, d: list<string>}>
     */
    public function toList(): array
    {
        return array_map(static fn (StudyNode $node): array => [
            'n' => $node->number,
            'p' => $node->parent,
            'san' => $node->san,
            'u' => $node->uci,
            'f' => $node->fenAfter,
            's' => $node->symbols,
            'c' => $node->comment,
            'd' => $node->drawings,
        ], $this->inOrder());
    }

    private static function isLegal(Position $position, Move $move): bool
    {
        foreach (MoveGenerator::legalMoves($position) as $legal) {
            if ($legal->equals($move)) {
                return true;
            }
        }

        return false;
    }
}
