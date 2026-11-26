<?php

declare(strict_types=1);

namespace PhpChess\Domain\Tournament;

/**
 * The Swiss pairing, FIDE Dutch system (T-03, A99; FIDE Handbook C.04.3).
 *
 * Input: the players still in the tournament (SwissPlayer), with their
 * score, colours and past opponents. Output: the boards of the next round
 * and the player who gets the pairing-bye (odd number of players).
 *
 * The rules, in the order this class applies them:
 *
 * 1. **Ranking**: players are ordered by score, then by pairing number.
 * 2. **Bye** (C.04.1 c, C.04.3 C.2, C.9 of 2025): with an odd number, the
 *    lowest-ranked player who has not had a bye yet gets it, among those
 *    with the fewest rounds without a game, provided the others can then
 *    be paired.
 * 3. **Score brackets** (B.1): players with the same score form a bracket,
 *    from the top. Players who could not be paired in the bracket above
 *    float down into the next one and are paired first.
 * 4. **In a bracket** (B.2, B.3): the upper half S1 meets the lower half S2,
 *    S1[1] - S2[1], S1[2] - S2[2] ... If that breaks a rule, S2 is reordered
 *    (transpositions), and then one player of S1 is exchanged with one of S2
 *    (exchanges), until a pairing is found.
 * 5. **Absolute rules** (C.1, C.3): no two players meet twice, and two
 *    players who both must have the same colour do not meet.
 * 6. **Quality** (C.5, C.6, C.10 to C.13): as many boards as possible, then
 *    as many colour wishes granted as possible.
 * 7. **Backtracking** (C.4): if a lower bracket cannot be completed, the
 *    bracket above tries its next pairing.
 * 8. **Colours** (E): both wishes granted where possible; otherwise the
 *    stronger wish wins, then alternation, then the higher-ranked player.
 *
 * Simplified on purpose (A99): the float rules C.14 to C.19 (avoid floating
 * the same player down two rounds running) and the top-scorer exceptions of
 * the last round are not applied. For club and school events this gives the
 * same pairings in nearly every round; the results are checked against the
 * published examples in the tests.
 *
 * Pure code, no database. A work budget keeps a hopeless search short: when
 * it runs out, the whole field is paired as one group (still never a
 * rematch); null means no pairing is possible any more.
 */
final class DutchPairing
{
    /** The most candidate pairings checked in one call, so a page never hangs. */
    private const int WORK_BUDGET = 200_000;

    /** The most candidates of one size kept per bracket before they are sorted by quality. */
    private const int CANDIDATES_PER_SIZE = 300;

    private int $workLeft = self::WORK_BUDGET;

    /** @var array<int, int> player id => rank (0 = highest), for the colour rules */
    private array $rankOf = [];

    /**
     * Pairs one round.
     *
     * @param list<SwissPlayer> $players
     * @return array{boards: list<array{0: int, 1: int}>, byeId: ?int}|null  boards as [white id, black id]; null when no pairing is possible
     */
    public function pair(array $players): ?array
    {
        $this->workLeft = self::WORK_BUDGET;
        $ranked = self::ranked($players);
        $this->rankOf = [];
        foreach ($ranked as $rank => $player) {
            $this->rankOf[$player->id] = $rank;
        }

        // --- An even number: no bye ---
        if (count($ranked) % 2 === 0) {
            $pairs = $this->pairField($ranked);

            return $pairs === null ? null : ['boards' => $this->withColours($pairs), 'byeId' => null];
        }

        // --- An odd number: try the bye from the bottom up, fewest unplayed rounds first ---
        $mostUnplayed = max(array_map(fn (SwissPlayer $player): int => $player->unplayedRounds, $ranked));
        for ($unplayed = 0; $unplayed <= $mostUnplayed; $unplayed++) {
            for ($index = count($ranked) - 1; $index >= 0; $index--) {
                $candidate = $ranked[$index];
                if ($candidate->hadPairingBye || $candidate->unplayedRounds !== $unplayed) {
                    continue;
                }
                $rest = $ranked;
                array_splice($rest, $index, 1);
                $pairs = $this->pairField($rest);
                if ($pairs !== null) {
                    return ['boards' => $this->withColours($pairs), 'byeId' => $candidate->id];
                }
            }
        }

        return null;
    }

    // --- Ranking and brackets ---

    /**
     * @param list<SwissPlayer> $players
     * @return list<SwissPlayer>
     */
    public static function ranked(array $players): array
    {
        usort($players, function (SwissPlayer $a, SwissPlayer $b): int {
            if ($a->halves !== $b->halves) {
                return $b->halves <=> $a->halves;
            }

            return $a->pairingNumber <=> $b->pairingNumber;
        });

        return $players;
    }

    /**
     * Pairs a field with an even number of players: bracket by bracket, and
     * as one group if the budget runs out.
     *
     * @param list<SwissPlayer> $ranked
     * @return list<array{0: SwissPlayer, 1: SwissPlayer}>|null
     */
    private function pairField(array $ranked): ?array
    {
        $pairs = $this->pairBrackets(self::scoreBrackets($ranked), 0, []);
        if ($pairs !== null || $this->workLeft > 0) {
            return $pairs;
        }

        // The budget ran out: one bracket for everyone, with a fresh budget.
        $this->workLeft = self::WORK_BUDGET;

        return $this->pairBrackets([$ranked], 0, []);
    }

    /**
     * Splits the ranked players into score brackets, highest score first.
     *
     * @param list<SwissPlayer> $ranked
     * @return list<list<SwissPlayer>>
     */
    private static function scoreBrackets(array $ranked): array
    {
        $brackets = [];
        foreach ($ranked as $player) {
            $brackets[$player->halves][] = $player;
        }
        krsort($brackets);

        return array_values($brackets);
    }

    /**
     * Pairs bracket $index (with the players floating down from above) and
     * every bracket below it. Returns null when that is impossible, so the
     * bracket above tries its next candidate (backtracking, C.4).
     *
     * @param list<list<SwissPlayer>> $brackets
     * @param list<SwissPlayer> $floaters
     * @return list<array{0: SwissPlayer, 1: SwissPlayer}>|null
     */
    private function pairBrackets(array $brackets, int $index, array $floaters): ?array
    {
        if ($index === count($brackets)) {
            return $floaters === [] ? [] : null;
        }
        $bracket = [...$floaters, ...$brackets[$index]];
        $isLast = $index === count($brackets) - 1;
        if ($isLast && count($bracket) % 2 === 1) {
            return null;
        }
        $mostPairs = intdiv(count($bracket), 2);
        $fewestPairs = $isLast ? $mostPairs : 0;

        // --- As many boards as possible first (C.5) ---
        for ($pairCount = $mostPairs; $pairCount >= $fewestPairs; $pairCount--) {
            foreach ($this->candidates($bracket, $pairCount) as [$pairs, $leftOver]) {
                $below = $this->pairBrackets($brackets, $index + 1, $leftOver);
                if ($below !== null) {
                    return [...$pairs, ...$below];
                }
                if ($this->workLeft <= 0) {
                    return null;
                }
            }
        }

        return null;
    }

    // --- Candidates inside one bracket ---

    /**
     * The allowed ways to make $pairCount boards in a bracket, best first:
     * S1 against transpositions of S2, then with one exchange between S1
     * and S2; sorted by how many colour wishes they break (stable, so the
     * Dutch order decides between equals).
     *
     * @param list<SwissPlayer> $bracket
     * @return list<array{0: list<array{0: SwissPlayer, 1: SwissPlayer}>, 1: list<SwissPlayer>}>
     */
    private function candidates(array $bracket, int $pairCount): array
    {
        if ($pairCount === 0) {
            return [[[], $bracket]];
        }
        $found = [];
        foreach (self::halvesWithExchanges($bracket, $pairCount) as [$upper, $lower]) {
            $this->collectTranspositions($upper, $lower, [], [], $found);
            if (count($found) >= self::CANDIDATES_PER_SIZE || $this->workLeft <= 0) {
                break;
            }
        }

        // --- Fewest broken colour wishes first ---
        $scored = [];
        foreach ($found as $order => $candidate) {
            $scored[] = ['cost' => $this->colourCost($candidate[0]), 'order' => $order, 'candidate' => $candidate];
        }
        usort($scored, fn (array $a, array $b): int => [$a['cost'], $a['order']] <=> [$b['cost'], $b['order']]);

        return array_map(fn (array $entry): array => $entry['candidate'], $scored);
    }

    /**
     * S1 and S2 for a number of boards: first as they are, then each
     * exchange of one S1 player with one S2 player, the smallest change
     * first (the lowest of S1 with the highest of S2).
     *
     * @param list<SwissPlayer> $bracket
     * @return list<array{0: list<SwissPlayer>, 1: list<SwissPlayer>}>
     */
    private static function halvesWithExchanges(array $bracket, int $pairCount): array
    {
        $upper = array_slice($bracket, 0, $pairCount);
        $lower = array_slice($bracket, $pairCount);
        $variants = [[$upper, $lower]];

        $exchanges = [];
        foreach ($upper as $upperIndex => $fromUpper) {
            foreach ($lower as $lowerIndex => $fromLower) {
                // The size of the change: how far apart the two exchanged players are.
                $distance = ($pairCount - $upperIndex) + $lowerIndex;
                $exchanges[] = ['distance' => $distance, 'upperIndex' => $upperIndex, 'lowerIndex' => $lowerIndex];
            }
        }
        usort($exchanges, fn (array $a, array $b): int => [$a['distance'], -$a['upperIndex']] <=> [$b['distance'], -$b['upperIndex']]);

        foreach ($exchanges as $exchange) {
            $newUpper = $upper;
            $newLower = $lower;
            $newUpper[$exchange['upperIndex']] = $lower[$exchange['lowerIndex']];
            $newLower[$exchange['lowerIndex']] = $upper[$exchange['upperIndex']];
            $variants[] = [self::ranked(array_values($newUpper)), self::ranked(array_values($newLower))];
        }

        return $variants;
    }

    /**
     * Walks through the transpositions of S2 in order: S1[1] takes the first
     * allowed opponent of S2, S1[2] the next, and so on; every complete,
     * allowed set of boards is added to $found.
     *
     * @param list<SwissPlayer> $upper
     * @param list<SwissPlayer> $lower
     * @param list<array{0: SwissPlayer, 1: SwissPlayer}> $pairsSoFar
     * @param list<int> $usedLower  indexes of S2 already taken
     * @param list<array{0: list<array{0: SwissPlayer, 1: SwissPlayer}>, 1: list<SwissPlayer>}> $found
     */
    private function collectTranspositions(array $upper, array $lower, array $pairsSoFar, array $usedLower, array &$found): void
    {
        if (count($found) >= self::CANDIDATES_PER_SIZE || $this->workLeft <= 0) {
            return;
        }
        $next = count($pairsSoFar);
        if ($next === count($upper)) {
            $leftOver = [];
            foreach ($lower as $index => $player) {
                if (!in_array($index, $usedLower, true)) {
                    $leftOver[] = $player;
                }
            }
            $found[] = [$pairsSoFar, $leftOver];

            return;
        }
        foreach ($lower as $index => $opponent) {
            if (in_array($index, $usedLower, true)) {
                continue;
            }
            $this->workLeft--;
            if (!self::mayMeet($upper[$next], $opponent)) {
                continue;
            }
            $this->collectTranspositions($upper, $lower, [...$pairsSoFar, [$upper[$next], $opponent]], [...$usedLower, $index], $found);
        }
    }

    // --- The absolute rules (C.1, C.3) ---

    public static function mayMeet(SwissPlayer $a, SwissPlayer $b): bool
    {
        if ($a->hasMet($b->id) || $b->hasMet($a->id)) {
            return false;
        }
        $bothAbsolute = $a->preferenceStrength() === SwissPlayer::PREFERENCE_ABSOLUTE
            && $b->preferenceStrength() === SwissPlayer::PREFERENCE_ABSOLUTE;

        return !($bothAbsolute && $a->preferredColour() === $b->preferredColour());
    }

    // --- Colours (E) ---

    /**
     * How many colour wishes a set of boards breaks: 2 for a strong wish,
     * 1 for a mild one (absolute wishes are never broken, see mayMeet).
     *
     * @param list<array{0: SwissPlayer, 1: SwissPlayer}> $pairs
     */
    private function colourCost(array $pairs): int
    {
        $cost = 0;
        foreach ($pairs as [$a, $b]) {
            [$white, $black] = $this->colours($a, $b);
            foreach ([[$white, 'w'], [$black, 'b']] as [$player, $given]) {
                $wish = $player->preferredColour();
                if ($wish !== '' && $wish !== $given) {
                    $cost += $player->preferenceStrength() >= SwissPlayer::PREFERENCE_STRONG ? 2 : 1;
                }
            }
        }

        return $cost;
    }

    /**
     * @param list<array{0: SwissPlayer, 1: SwissPlayer}> $pairs
     * @return list<array{0: int, 1: int}>
     */
    private function withColours(array $pairs): array
    {
        // Boards are numbered from the highest-ranked player down.
        usort($pairs, fn (array $a, array $b): int => min($this->rankOf[$a[0]->id], $this->rankOf[$a[1]->id]) <=> min($this->rankOf[$b[0]->id], $this->rankOf[$b[1]->id]));
        $boards = [];
        foreach ($pairs as [$a, $b]) {
            [$white, $black] = $this->colours($a, $b);
            $boards[] = [$white->id, $black->id];
        }

        return $boards;
    }

    /**
     * Who gets White (E.1 to E.5).
     *
     * @return array{0: SwissPlayer, 1: SwissPlayer} [white, black]
     */
    private function colours(SwissPlayer $a, SwissPlayer $b): array
    {
        [$higher, $lower] = $this->rankOf[$a->id] <= $this->rankOf[$b->id] ? [$a, $b] : [$b, $a];
        $higherWish = $higher->preferredColour();
        $lowerWish = $lower->preferredColour();

        // E.1: both wishes can be granted (or one has no wish).
        if ($higherWish !== $lowerWish || $higherWish === '') {
            if ($higherWish !== '') {
                return $higherWish === 'w' ? [$higher, $lower] : [$lower, $higher];
            }
            if ($lowerWish !== '') {
                return $lowerWish === 'w' ? [$lower, $higher] : [$higher, $lower];
            }

            // E.5: nobody has a wish (round 1): the higher-ranked player gets
            // White on odd pairing numbers, so boards alternate.
            return $higher->pairingNumber % 2 === 1 ? [$higher, $lower] : [$lower, $higher];
        }

        // E.2: the stronger wish wins.
        $higherStrength = $higher->preferenceStrength();
        $lowerStrength = $lower->preferenceStrength();
        if ($higherStrength !== $lowerStrength) {
            $winner = $higherStrength > $lowerStrength ? $higher : $lower;
        } elseif (abs($higher->colourDifference()) !== abs($lower->colourDifference())) {
            $winner = abs($higher->colourDifference()) > abs($lower->colourDifference()) ? $higher : $lower;
        } else {
            // E.3: alternate from the most recent round where their colours differed.
            $winner = self::alternationWinner($higher, $lower) ?? $higher;
        }
        $loser = $winner === $higher ? $lower : $higher;

        return $higherWish === 'w' ? [$winner, $loser] : [$loser, $winner];
    }

    /**
     * E.3: going back from the last game, the first round where the two had
     * different colours; whoever had the "wrong" colour then gets the wish now.
     */
    private static function alternationWinner(SwissPlayer $a, SwissPlayer $b): ?SwissPlayer
    {
        $aColours = $a->colours;
        $bColours = $b->colours;
        $aIndex = count($aColours) - 1;
        $bIndex = count($bColours) - 1;
        while ($aIndex >= 0 && $bIndex >= 0) {
            if ($aColours[$aIndex] !== $bColours[$bIndex]) {
                // The one who had the wished colour back then now gives way.
                $wish = $a->preferredColour();

                return $aColours[$aIndex] === $wish ? $b : $a;
            }
            $aIndex--;
            $bIndex--;
        }

        return null;
    }
}
