<?php

declare(strict_types=1);

namespace PhpChess\Domain\Tournament;

/**
 * The largest set of pairs in a graph: Edmonds' blossom algorithm (A112).
 *
 * Used by the Swiss pairing to answer two questions exactly:
 * - how many boards can a bracket make at most (MaxPairs, FIDE C.04.3 art. 3.1)?
 * - can everyone still unpaired be paired (the completion criterion C4)?
 *
 * The players are numbered 0 .. n-1; $mayMeet[a] lists who a may play.
 * The algorithm grows a search tree from each unmatched player; an edge to
 * an unmatched player gives an "augmenting path" (one more pair). An odd
 * cycle (a "blossom") is shrunk to a single point, so the search can go on
 * through it. Each search is O(n²), so the whole is O(n³): about 200 players
 * take a fraction of a second.
 *
 * Pure code, no database.
 */
final class MaximumMatching
{
    /** @var array<int, list<int>> */
    private array $mayMeet;
    private int $count;
    /** @var array<int, int> partner of each player, -1 for none */
    private array $partner = [];
    /** @var array<int, int> the tree parent on the search path, -1 for none */
    private array $parent = [];
    /** @var array<int, int> the base of the blossom each player is in */
    private array $base = [];
    /** @var array<int, bool> */
    private array $inTree = [];
    /** @var array<int, bool> */
    private array $inBlossom = [];
    /** @var list<int> */
    private array $queue = [];

    /**
     * The number of pairs in a largest matching.
     *
     * @param array<int, list<int>> $mayMeet  player => players they may meet (both directions listed)
     */
    public static function size(int $count, array $mayMeet): int
    {
        $matching = new self($count, $mayMeet);

        return $matching->run();
    }

    /**
     * True when everyone can be paired at once (a perfect matching).
     *
     * @param array<int, list<int>> $mayMeet
     */
    public static function isPerfect(int $count, array $mayMeet): bool
    {
        if ($count % 2 === 1) {
            return false;
        }

        return self::size($count, $mayMeet) * 2 === $count;
    }

    /**
     * @param array<int, list<int>> $mayMeet
     */
    private function __construct(int $count, array $mayMeet)
    {
        $this->count = $count;
        $this->mayMeet = $mayMeet;
    }

    private function run(): int
    {
        $this->partner = array_fill(0, $this->count, -1);
        $pairs = 0;

        // --- A quick start: pair greedily, the search then improves on it ---
        for ($player = 0; $player < $this->count; $player++) {
            if ($this->partner[$player] !== -1) {
                continue;
            }
            foreach ($this->mayMeet[$player] ?? [] as $other) {
                if ($this->partner[$other] === -1) {
                    $this->partner[$player] = $other;
                    $this->partner[$other] = $player;
                    $pairs++;
                    break;
                }
            }
        }

        // --- One search per unpaired player; each success adds a pair ---
        for ($player = 0; $player < $this->count; $player++) {
            if ($this->partner[$player] === -1) {
                $end = $this->findAugmentingPath($player);
                if ($end !== -1) {
                    $this->flipPath($end);
                    $pairs++;
                }
            }
        }

        return $pairs;
    }

    /** Searches from $root; returns the free player at the end of an augmenting path, or -1. */
    private function findAugmentingPath(int $root): int
    {
        $this->parent = array_fill(0, $this->count, -1);
        $this->inTree = array_fill(0, $this->count, false);
        $this->base = range(0, max(0, $this->count - 1));
        $this->inTree[$root] = true;
        $this->queue = [$root];

        for ($head = 0; $head < count($this->queue); $head++) {
            $current = $this->queue[$head];
            foreach ($this->mayMeet[$current] ?? [] as $next) {
                if ($this->base[$current] === $this->base[$next] || $this->partner[$current] === $next) {
                    continue;
                }
                $closesOddCycle = $next === $root || ($this->partner[$next] !== -1 && $this->parent[$this->partner[$next]] !== -1);
                if ($closesOddCycle) {
                    $this->shrinkBlossom($current, $next);
                } elseif ($this->parent[$next] === -1) {
                    $this->parent[$next] = $current;
                    if ($this->partner[$next] === -1) {
                        return $next;
                    }
                    $partner = $this->partner[$next];
                    $this->inTree[$partner] = true;
                    $this->queue[] = $partner;
                }
            }
        }

        return -1;
    }

    /** Shrinks the odd cycle closed by the edge a-b into its base. */
    private function shrinkBlossom(int $a, int $b): void
    {
        $commonBase = $this->lowestCommonBase($a, $b);
        $this->inBlossom = array_fill(0, $this->count, false);
        $this->markPath($a, $commonBase, $b);
        $this->markPath($b, $commonBase, $a);
        for ($player = 0; $player < $this->count; $player++) {
            if ($this->inBlossom[$this->base[$player]]) {
                $this->base[$player] = $commonBase;
                if (!$this->inTree[$player]) {
                    $this->inTree[$player] = true;
                    $this->queue[] = $player;
                }
            }
        }
    }

    /** The first base the paths from a and b to the root share. */
    private function lowestCommonBase(int $a, int $b): int
    {
        $seen = array_fill(0, $this->count, false);
        while (true) {
            $a = $this->base[$a];
            $seen[$a] = true;
            if ($this->partner[$a] === -1) {
                break;
            }
            $a = $this->parent[$this->partner[$a]];
        }
        while (true) {
            $b = $this->base[$b];
            if ($seen[$b]) {
                return $b;
            }
            $b = $this->parent[$this->partner[$b]];
        }
    }

    /** Marks the blossom's players on the way from $player down to $commonBase. */
    private function markPath(int $player, int $commonBase, int $child): void
    {
        while ($this->base[$player] !== $commonBase) {
            $this->inBlossom[$this->base[$player]] = true;
            $this->inBlossom[$this->base[$this->partner[$player]]] = true;
            $this->parent[$player] = $child;
            $child = $this->partner[$player];
            $player = $this->parent[$this->partner[$player]];
        }
    }

    /** Swaps paired and unpaired edges along the path ending at $end: one pair more. */
    private function flipPath(int $end): void
    {
        $player = $end;
        while ($player !== -1) {
            $previous = $this->parent[$player];
            $previousPartner = $this->partner[$previous];
            $this->partner[$player] = $previous;
            $this->partner[$previous] = $player;
            $player = $previousPartner;
        }
    }
}
