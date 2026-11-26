<?php

declare(strict_types=1);

namespace PhpChess\Domain\Tournament;

use Generator;

/**
 * The Swiss pairing, FIDE Dutch system (T-03, A99, A112), as in FIDE
 * Handbook C.04.3 in force from 1 February 2026. The article numbers below
 * are the handbook's.
 *
 * Input: the players still in the tournament (SwissPlayer) with their
 * score, colours, past opponents and floats. Output: the boards of the next
 * round and the player who gets the pairing-allocated bye (PAB).
 *
 * How a round is paired:
 *
 * 1. **Ranking (1.2)**: by score, then by pairing number.
 * 2. **Brackets (1.3)**: players with the same score form a bracket, from
 *    the top. Players left unpaired in a bracket "float down" into the next
 *    one as moved-down players (MDPs) and are paired first there.
 * 3. **Candidates (3.2 to 3.7, 4)**: in each bracket the upper half S1 meets
 *    the lower half S2: S1[1] - S2[1], S1[2] - S2[2] ... Other candidates
 *    come in FIDE's fixed order: S2 reordered (transpositions), then players
 *    swapped between S1 and S2 (exchanges); in a bracket with MDPs, also the
 *    choice of which MDPs are paired.
 * 4. **Absolute criteria (C1 to C3)**: no rematch, no second PAB, and no two
 *    non-topscorers who both must have the same colour.
 * 5. **Completion (C4)**: a candidate is only taken when everyone still
 *    unpaired can be paired after it. MaximumMatching answers that exactly.
 * 6. **Quality (C5 to C21)**: the bye to the lowest score possible; as few
 *    floaters as possible, with the lowest scores; colours as wished; and
 *    nobody floating twice in a row. C8 (what the floaters leave the next
 *    bracket) counts the boards the next bracket cannot make. The first candidate that meets them
 *    all is taken; if none does, the best one (3.8): compared criterion by
 *    criterion, the earlier one on a tie.
 * 7. **Colours (5)**: both wishes where possible, else the stronger one,
 *    then alternation, then the higher-ranked player's wish.
 *
 * A work limit per bracket keeps a page quick: in a very large bracket the
 * best candidate found within the limit is taken. Pure code, no database.
 */
final class DutchPairing
{
    /** The most candidates looked at in one bracket. */
    private const int CANDIDATES_PER_BRACKET = 40_000;

    /** The colour of the higher-ranked player with an odd pairing number when nothing else decides (5.1, 5.2.5). */
    private const string INITIAL_COLOUR = 'w';

    /** @var array<int, int> player id => rank (0 = top), set for one call of pair() */
    private array $rankOf = [];

    /** @var array<int, bool> player id => is a topscorer (1.8) */
    private array $isTopscorer = [];

    /** The highest score a PAB may go to this round (C5), in half points. */
    private int $byeScoreLimit = 0;

    private int $workLeft = 0;

    /**
     * Pairs one round.
     *
     * @param list<SwissPlayer> $players
     * @param int $roundCount     the tournament's number of rounds (for topscorers, 1.8)
     * @param bool $isFinalRound  true when this is the last round
     * @return array{boards: list<array{0: int, 1: int}>, byeId: ?int}|null  boards as [white id, black id]; null when no pairing is possible
     */
    public function pair(array $players, int $roundCount = 0, bool $isFinalRound = false): ?array
    {
        $ranked = self::ranked($players);
        $this->rankOf = [];
        $this->isTopscorer = [];
        foreach ($ranked as $rank => $player) {
            $this->rankOf[$player->id] = $rank;
            // More than half the most points possible, when pairing the final round.
            $this->isTopscorer[$player->id] = $isFinalRound && $player->halves > $roundCount;
        }

        // --- C5: the lowest score the bye can go to, so that everyone else can be paired ---
        if (count($ranked) % 2 === 1) {
            $limit = $this->lowestPossibleByeScore($ranked);
            if ($limit === null) {
                return null;
            }
            $this->byeScoreLimit = $limit;
        } elseif (!$this->canComplete($ranked)) {
            return null;
        }

        // --- Bracket by bracket, from the top (1.9.2) ---
        $brackets = self::scoreGroups($ranked);
        $pairs = [];
        $floaters = [];
        foreach ($brackets as $index => $residents) {
            $lower = [];
            foreach (array_slice($brackets, $index + 1) as $group) {
                $lower = [...$lower, ...$group];
            }
            $bracket = [...$floaters, ...$residents];
            $nextResidents = $brackets[$index + 1] ?? [];
            [$bracketPairs, $floaters] = $this->pairBracket($bracket, count($floaters), $lower, $nextResidents, $index === count($brackets) - 1);
            $pairs = [...$pairs, ...$bracketPairs];
        }

        return ['boards' => $this->boardsWithColours($pairs), 'byeId' => $floaters === [] ? null : $floaters[0]->id];
    }

    // --- Ranking and score groups (1.2, 1.3) ---

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
     * @param list<SwissPlayer> $ranked
     * @return list<list<SwissPlayer>>
     */
    private static function scoreGroups(array $ranked): array
    {
        $groups = [];
        foreach ($ranked as $player) {
            $groups[$player->halves][] = $player;
        }
        krsort($groups);

        return array_values($groups);
    }

    // --- Absolute criteria (C1 to C3) ---

    /** May these two be paired at all? */
    public function mayMeet(SwissPlayer $a, SwissPlayer $b): bool
    {
        if ($a->hasMet($b->id) || $b->hasMet($a->id)) {
            return false;
        }
        $bothAbsolute = $a->preferenceStrength() === SwissPlayer::PREFERENCE_ABSOLUTE
            && $b->preferenceStrength() === SwissPlayer::PREFERENCE_ABSOLUTE;
        $neitherTopscorer = !($this->isTopscorer[$a->id] ?? false) && !($this->isTopscorer[$b->id] ?? false);

        return !($bothAbsolute && $neitherTopscorer && $a->preferredColour() === $b->preferredColour());
    }

    /** May this player get the bye this round (C2, C5)? */
    private function mayGetBye(SwissPlayer $player, int $scoreLimit): bool
    {
        return !$player->hadPairingBye && $player->halves <= $scoreLimit;
    }

    // --- Completion (C4) and the bye's score (C5) ---

    /**
     * Can all these players be paired, with one bye if their number is odd?
     *
     * @param list<SwissPlayer> $players
     */
    private function canComplete(array $players, ?int $byeScoreLimit = null): bool
    {
        $count = count($players);
        if ($count === 0) {
            return true;
        }
        $mayMeet = $this->graphOf($players);
        if ($count % 2 === 1) {
            // A stand-in opponent for the bye: whoever may get the bye may "meet" it.
            $limit = $byeScoreLimit ?? $this->byeScoreLimit;
            $byeNode = $count;
            $mayMeet[$byeNode] = [];
            foreach ($players as $index => $player) {
                if ($this->mayGetBye($player, $limit)) {
                    $mayMeet[$index][] = $byeNode;
                    $mayMeet[$byeNode][] = $index;
                }
            }
            $count++;
        }

        return MaximumMatching::isPerfect($count, $mayMeet);
    }

    /**
     * @param list<SwissPlayer> $ranked
     */
    private function lowestPossibleByeScore(array $ranked): ?int
    {
        $scores = array_values(array_unique(array_map(fn (SwissPlayer $player): int => $player->halves, $ranked)));
        sort($scores);
        foreach ($scores as $score) {
            if ($this->canComplete($ranked, $score)) {
                return $score;
            }
        }

        return null;
    }

    /**
     * Who may meet whom, as numbered neighbours for MaximumMatching.
     *
     * @param list<SwissPlayer> $players
     * @return array<int, list<int>>
     */
    private function graphOf(array $players): array
    {
        $graph = array_fill(0, count($players), []);
        foreach ($players as $a => $first) {
            for ($b = $a + 1; $b < count($players); $b++) {
                if ($this->mayMeet($first, $players[$b])) {
                    $graph[$a][] = $b;
                    $graph[$b][] = $a;
                }
            }
        }

        return $graph;
    }

    // --- One bracket (3) ---

    /**
     * Pairs one bracket: the best candidate that lets everyone below be
     * paired. Returns the pairs and the players floating down.
     *
     * @param list<SwissPlayer> $bracket  the MDPs first, then the residents, each in rank order
     * @param list<SwissPlayer> $lower          every player in the brackets below
     * @param list<SwissPlayer> $nextResidents  the residents of the next bracket (for C8)
     * @return array{0: list<array{0: SwissPlayer, 1: SwissPlayer}>, 1: list<SwissPlayer>}
     */
    private function pairBracket(array $bracket, int $mdpCount, array $lower, array $nextResidents, bool $isLast): array
    {
        $mostPairs = MaximumMatching::size(count($bracket), $this->graphOf($bracket));
        $mdps = array_slice($bracket, 0, $mdpCount);

        // C6: as many boards as possible; fewer only when the brackets below could not be completed.
        for ($pairCount = $mostPairs; $pairCount >= 0; $pairCount--) {
            $this->workLeft = self::CANDIDATES_PER_BRACKET;
            $ideal = self::idealQuality($bracket, $pairCount);
            $best = null;
            $bestQuality = null;
            foreach ($this->candidates($bracket, $mdpCount, $pairCount) as [$pairs, $floaters]) {
                $quality = $this->quality($pairs, $floaters, $mdps, $isLast, $nextResidents);
                $isBetter = $bestQuality === null || self::compareQuality($quality, $bestQuality) < 0;
                // In the last bracket nobody can float any further: at most the one bye is left.
                $isPossible = $isLast ? count($floaters) <= 1 : true;
                if ($isBetter && $isPossible && $this->canComplete([...$floaters, ...$lower])) {
                    $best = [$pairs, $floaters];
                    $bestQuality = $quality;
                    if (self::compareQuality($quality, $ideal) <= 0) {
                        break; // a perfect candidate (3.4): nothing later can be better
                    }
                }
                $this->workLeft--;
                if ($this->workLeft <= 0) {
                    break;
                }
            }
            if ($best !== null) {
                return $best;
            }
        }

        // MDPs that can only meet each other: pair the bracket once more as one plain group.
        if ($mdpCount > 0) {
            return $this->pairBracket($bracket, 0, $lower, $nextResidents, $isLast);
        }

        return [[], $bracket];
    }

    // --- Candidates in FIDE's order (3.6, 3.7, 4) ---

    /**
     * Every candidate of a bracket with $pairCount boards, in FIDE's order.
     * A candidate is [pairs, floaters]; pairs that break C1 or C3 are never made.
     *
     * (A "generator": each candidate is made only when the loop asks for
     * the next one, so the search can stop early without making them all.)
     *
     * @param list<SwissPlayer> $bracket
     * @return Generator<int, array{0: list<array{0: SwissPlayer, 1: SwissPlayer}>, 1: list<SwissPlayer>}>
     */
    private function candidates(array $bracket, int $mdpCount, int $pairCount): Generator
    {
        if ($mdpCount === 0) {
            yield from $this->homogeneousCandidates($bracket, $pairCount);

            return;
        }
        $mdps = array_slice($bracket, 0, $mdpCount);
        $residents = array_slice($bracket, $mdpCount);

        // M1 (3.1): how many MDPs can be paired with residents, at most.
        $pairableMdps = min($pairCount, $this->mostMdpPairs($mdps, $residents));
        $remainderPairs = $pairCount - $pairableMdps;
        if ($remainderPairs * 2 > count($residents) - $pairableMdps) {
            return;
        }

        // 3.7: for each choice of MDPs (4.4), each order of S2 (4.2), each remainder candidate (3.6).
        foreach (self::combinations(count($mdps), $pairableMdps) as $chosen) {
            $upper = array_map(fn (int $index): SwissPlayer => $mdps[$index], $chosen);
            $limbo = [];
            foreach ($mdps as $index => $mdp) {
                if (!in_array($index, $chosen, true)) {
                    $limbo[] = $mdp;
                }
            }
            foreach ($this->transpositions($upper, $residents) as [$mdpPairs, $remainder]) {
                foreach ($this->homogeneousCandidates($remainder, $remainderPairs) as [$remainderPairsMade, $remainderFloaters]) {
                    yield [[...$mdpPairs, ...$remainderPairsMade], [...$limbo, ...$remainderFloaters]];
                }
            }
        }
    }

    /**
     * A homogeneous bracket or a remainder (3.6): S1 is the first $pairCount
     * players; all transpositions of S2, then the next exchange, and so on.
     *
     * @param list<SwissPlayer> $players  in rank order (their BSNs are 1, 2, 3 ...)
     * @return Generator<int, array{0: list<array{0: SwissPlayer, 1: SwissPlayer}>, 1: list<SwissPlayer>}>
     */
    private function homogeneousCandidates(array $players, int $pairCount): Generator
    {
        if ($pairCount === 0) {
            yield [[], $players];

            return;
        }
        $upper = array_slice($players, 0, $pairCount);
        $lower = array_slice($players, $pairCount);
        foreach ($this->exchanges($upper, $lower) as [$newUpper, $newLower]) {
            yield from $this->transpositions($newUpper, $newLower);
        }
    }

    /**
     * The transpositions of S2 (4.2): S1[i] meets the i-th of S2 in every
     * order, the lowest order first (by BSN). The rest of S2 floats.
     *
     * @param list<SwissPlayer> $upper
     * @param list<SwissPlayer> $lower
     * @param array<int, bool> $used                                 indexes of S2 already taken
     * @param list<array{0: SwissPlayer, 1: SwissPlayer}> $pairs     the pairs made so far
     * @return Generator<int, array{0: list<array{0: SwissPlayer, 1: SwissPlayer}>, 1: list<SwissPlayer>}>
     */
    private function transpositions(array $upper, array $lower, int $depth = 0, array $used = [], array $pairs = []): Generator
    {
        if ($depth === count($upper)) {
            $rest = [];
            foreach ($lower as $index => $player) {
                if (!isset($used[$index])) {
                    $rest[] = $player;
                }
            }
            yield [$pairs, $rest];

            return;
        }
        foreach ($lower as $index => $opponent) {
            if (isset($used[$index]) || !$this->mayMeet($upper[$depth], $opponent)) {
                continue;
            }
            yield from $this->transpositions($upper, $lower, $depth + 1, $used + [$index => true], [...$pairs, [$upper[$depth], $opponent]]);
        }
    }

    /**
     * S1 and S2 as they are, then after each exchange in FIDE's order (4.3):
     * fewest players exchanged; then the smallest difference of the BSN sums;
     * then the highest BSNs leaving S1; then the lowest BSNs leaving S2.
     *
     * @param list<SwissPlayer> $upper
     * @param list<SwissPlayer> $lower
     * @return Generator<int, array{0: list<SwissPlayer>, 1: list<SwissPlayer>}>
     */
    private function exchanges(array $upper, array $lower): Generator
    {
        yield [$upper, $lower];

        $upperCount = count($upper);
        for ($size = 1; $size <= min($upperCount, count($lower)); $size++) {
            $choices = [];
            foreach (self::combinations($upperCount, $size) as $fromUpper) {
                foreach (self::combinations(count($lower), $size) as $fromLower) {
                    // BSNs: S1 holds 1 .. upperCount, S2 continues from there.
                    $leavingUpper = array_map(fn (int $index): int => $index + 1, $fromUpper);
                    $leavingLower = array_map(fn (int $index): int => $upperCount + $index + 1, $fromLower);
                    rsort($leavingUpper);
                    sort($leavingLower);
                    $choices[] = [
                        'key' => [abs(array_sum($leavingLower) - array_sum($leavingUpper)), array_map(fn (int $bsn): int => -$bsn, $leavingUpper), $leavingLower],
                        'fromUpper' => $fromUpper,
                        'fromLower' => $fromLower,
                    ];
                    if (count($choices) > self::CANDIDATES_PER_BRACKET) {
                        break 2;
                    }
                }
            }
            usort($choices, fn (array $a, array $b): int => $a['key'] <=> $b['key']);
            foreach ($choices as $choice) {
                $newUpper = $upper;
                $newLower = $lower;
                foreach ($choice['fromUpper'] as $position => $upperIndex) {
                    $lowerIndex = $choice['fromLower'][$position];
                    $newUpper[$upperIndex] = $lower[$lowerIndex];
                    $newLower[$lowerIndex] = $upper[$upperIndex];
                }
                yield [self::ranked(array_values($newUpper)), self::ranked(array_values($newLower))];
            }
        }
    }

    /**
     * Every choice of $size items out of $count (as sorted index lists), in
     * lexicographic order: [0,1], [0,2], [1,2] ...
     *
     * @return list<list<int>>
     */
    private static function combinations(int $count, int $size): array
    {
        if ($size === 0) {
            return [[]];
        }
        if ($size > $count) {
            return [];
        }
        $result = [];
        $choice = range(0, $size - 1);
        while (true) {
            $result[] = array_values($choice);
            $position = $size - 1;
            while ($position >= 0 && $choice[$position] === $count - $size + $position) {
                $position--;
            }
            if ($position < 0 || count($result) > self::CANDIDATES_PER_BRACKET) {
                return $result;
            }
            $choice[$position]++;
            for ($next = $position + 1; $next < $size; $next++) {
                $choice[$next] = $choice[$next - 1] + 1;
            }
        }
    }

    /**
     * The most MDPs that can be paired with residents (M1 before the cap).
     *
     * @param list<SwissPlayer> $mdps
     * @param list<SwissPlayer> $residents
     */
    private function mostMdpPairs(array $mdps, array $residents): int
    {
        // Only MDP-resident edges: a bipartite graph for MaximumMatching.
        $graph = array_fill(0, count($mdps) + count($residents), []);
        foreach ($mdps as $a => $mdp) {
            foreach ($residents as $b => $resident) {
                if ($this->mayMeet($mdp, $resident)) {
                    $graph[$a][] = count($mdps) + $b;
                    $graph[count($mdps) + $b][] = $a;
                }
            }
        }

        return MaximumMatching::size(count($graph), $graph);
    }

    // --- Quality (C6 to C21) ---

    /**
     * How good a candidate is: one entry per criterion, lower is better,
     * compared in this order (2.4). Lists (C7, C18 to C21) are compared
     * from their biggest value down.
     *
     * @param list<array{0: SwissPlayer, 1: SwissPlayer}> $pairs
     * @param list<SwissPlayer> $floaters
     * @param list<SwissPlayer> $mdps
     * @param list<SwissPlayer> $nextResidents
     * @return list<int|list<int>>
     */
    private function quality(array $pairs, array $floaters, array $mdps, bool $isLast, array $nextResidents = []): array
    {
        $mdpIds = array_map(fn (SwissPlayer $player): int => $player->id, $mdps);

        $floaterScores = array_map(fn (SwissPlayer $player): int => $player->halves, $floaters);
        rsort($floaterScores);

        $colourBeyondTwo = 0;
        $threeInARow = 0;
        $wishMissed = 0;
        $strongWishMissed = 0;
        $mdpOpponentUpLast = 0;
        $mdpOpponentUpBefore = 0;
        $differencesDownLast = [];
        $differencesUpLast = [];
        $differencesDownBefore = [];
        $differencesUpBefore = [];

        foreach ($pairs as [$a, $b]) {
            [$white, $black] = $this->colours($a, $b);
            $hasTopscorer = ($this->isTopscorer[$a->id] ?? false) || ($this->isTopscorer[$b->id] ?? false);
            foreach ([[$white, 'w'], [$black, 'b']] as [$player, $given]) {
                $wish = $player->preferredColour();
                if ($wish !== '' && $wish !== $given) {
                    $wishMissed++;
                    if ($player->preferenceStrength() >= SwissPlayer::PREFERENCE_STRONG) {
                        $strongWishMissed++;
                    }
                }
                if ($hasTopscorer) {
                    $newColours = [...$player->colours, $given];
                    $difference = count(array_filter($newColours, fn (string $colour): bool => $colour === 'w')) * 2 - count($newColours);
                    $colourBeyondTwo += abs($difference) > 2 ? 1 : 0;
                    $count = count($newColours);
                    $threeInARow += $count >= 3 && $newColours[$count - 1] === $newColours[$count - 2] && $newColours[$count - 2] === $newColours[$count - 3] ? 1 : 0;
                }
            }

            // --- Floats in the MDP pairing (C15, C17 to C21) ---
            foreach ([[$a, $b], [$b, $a]] as [$mdp, $opponent]) {
                if (!in_array($mdp->id, $mdpIds, true) || in_array($opponent->id, $mdpIds, true)) {
                    continue;
                }
                $difference = $mdp->halves - $opponent->halves;
                if ($opponent->floatLastRound === SwissPlayer::FLOAT_UP) {
                    $mdpOpponentUpLast++;
                    $differencesUpLast[] = $difference;
                }
                if ($opponent->floatTwoRoundsAgo === SwissPlayer::FLOAT_UP) {
                    $mdpOpponentUpBefore++;
                    $differencesUpBefore[] = $difference;
                }
                if ($mdp->floatLastRound === SwissPlayer::FLOAT_DOWN) {
                    $differencesDownLast[] = $difference;
                }
                if ($mdp->floatTwoRoundsAgo === SwissPlayer::FLOAT_DOWN) {
                    $differencesDownBefore[] = $difference;
                }
            }
        }

        // --- Resident floaters who floated down before (C14, C16) ---
        $residentDownLast = 0;
        $residentDownBefore = 0;
        foreach ($floaters as $floater) {
            if (in_array($floater->id, $mdpIds, true)) {
                continue;
            }
            $residentDownLast += $floater->floatLastRound === SwissPlayer::FLOAT_DOWN ? 1 : 0;
            $residentDownBefore += $floater->floatTwoRoundsAgo === SwissPlayer::FLOAT_DOWN ? 1 : 0;
        }
        rsort($differencesDownLast);
        rsort($differencesUpLast);
        rsort($differencesDownBefore);
        rsort($differencesUpBefore);

        return [
            count($floaters),                                                       // C6
            $floaterScores,                                                         // C7
            $this->nextBracketShortfall($floaters, $nextResidents),                 // C8
            $isLast && count($floaters) === 1 ? $floaters[0]->unplayedRounds : 0,   // C9
            $colourBeyondTwo,                                                       // C10
            $threeInARow,                                                           // C11
            $wishMissed,                                                            // C12
            $strongWishMissed,                                                      // C13
            $residentDownLast,                                                      // C14
            $mdpOpponentUpLast,                                                     // C15
            $residentDownBefore,                                                    // C16
            $mdpOpponentUpBefore,                                                   // C17
            $differencesDownLast,                                                   // C18
            $differencesUpLast,                                                     // C19
            $differencesDownBefore,                                                 // C20
            $differencesUpBefore,                                                   // C21
        ];
    }

    /**
     * C8: how many players the next bracket would have to float on with
     * these floaters (its own C6): fewer is better. Found exactly with
     * MaximumMatching on the next bracket.
     *
     * (The next bracket's C7, the scores of those players, is left out on
     * purpose: with it the pairings matched the reference engine less well.)
     *
     * @param list<SwissPlayer> $floaters
     * @param list<SwissPlayer> $nextResidents
     */
    private function nextBracketShortfall(array $floaters, array $nextResidents): int
    {
        if ($floaters === [] || $nextResidents === []) {
            return 0;
        }
        $nextBracket = [...$floaters, ...$nextResidents];

        return count($nextBracket) - 2 * MaximumMatching::size(count($nextBracket), $this->graphOf($nextBracket));
    }

    /**
     * The best quality a bracket could possibly reach with $pairCount boards:
     * the fewest floaters, being its lowest-ranked players, and nothing else
     * broken. A candidate this good is "perfect" (3.4).
     *
     * @param list<SwissPlayer> $bracket
     * @return list<int|list<int>>
     */
    private static function idealQuality(array $bracket, int $pairCount): array
    {
        $floaterCount = count($bracket) - 2 * $pairCount;
        $lowest = array_map(fn (SwissPlayer $player): int => $player->halves, array_slice($bracket, count($bracket) - $floaterCount));
        rsort($lowest);

        return [$floaterCount, $lowest, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, [], [], [], []];
    }

    /**
     * Compares two quality lists: negative when $a is better.
     *
     * @param list<int|list<int>> $a
     * @param list<int|list<int>> $b
     */
    private static function compareQuality(array $a, array $b): int
    {
        foreach ($a as $index => $value) {
            $other = $b[$index] ?? 0;
            if (is_array($value) || is_array($other)) {
                $first = is_array($value) ? $value : [];
                $second = is_array($other) ? $other : [];
                $length = max(count($first), count($second));
                for ($i = 0; $i < $length; $i++) {
                    $difference = ($first[$i] ?? 0) <=> ($second[$i] ?? 0);
                    if ($difference !== 0) {
                        return $difference;
                    }
                }
                continue;
            }
            $difference = $value <=> $other;
            if ($difference !== 0) {
                return $difference;
            }
        }

        return 0;
    }

    // --- Colours (5) ---

    /**
     * @param list<array{0: SwissPlayer, 1: SwissPlayer}> $pairs
     * @return list<array{0: int, 1: int}>
     */
    private function boardsWithColours(array $pairs): array
    {
        // Boards are numbered by their higher-ranked player.
        usort($pairs, fn (array $a, array $b): int => min($this->rankOf[$a[0]->id], $this->rankOf[$a[1]->id]) <=> min($this->rankOf[$b[0]->id], $this->rankOf[$b[1]->id]));
        $boards = [];
        foreach ($pairs as [$a, $b]) {
            [$white, $black] = $this->colours($a, $b);
            $boards[] = [$white->id, $black->id];
        }

        return $boards;
    }

    /**
     * Who gets White (5.2.1 to 5.2.5).
     *
     * @return array{0: SwissPlayer, 1: SwissPlayer} [white, black]
     */
    private function colours(SwissPlayer $a, SwissPlayer $b): array
    {
        [$higher, $lower] = $this->rankOf[$a->id] <= $this->rankOf[$b->id] ? [$a, $b] : [$b, $a];
        $higherWish = $higher->preferredColour();
        $lowerWish = $lower->preferredColour();

        // 5.2.1: both wishes can be granted (or one has none, 1.7.4).
        if ($higherWish !== $lowerWish) {
            if ($higherWish !== '') {
                return $higherWish === 'w' ? [$higher, $lower] : [$lower, $higher];
            }

            return $lowerWish === 'w' ? [$lower, $higher] : [$higher, $lower];
        }
        if ($higherWish === '') {
            // 5.2.5: nobody has a wish: the higher-ranked player gets the initial colour on an odd pairing number.
            $higherColour = $higher->pairingNumber % 2 === 1 ? self::INITIAL_COLOUR : self::otherColour(self::INITIAL_COLOUR);

            return $higherColour === 'w' ? [$higher, $lower] : [$lower, $higher];
        }

        // 5.2.2: the stronger wish wins; two absolute wishes: the wider colour difference.
        $winner = null;
        $higherStrength = $higher->preferenceStrength();
        $lowerStrength = $lower->preferenceStrength();
        if ($higherStrength !== $lowerStrength) {
            $winner = $higherStrength > $lowerStrength ? $higher : $lower;
        } elseif ($higherStrength === SwissPlayer::PREFERENCE_ABSOLUTE && abs($higher->colourDifference()) !== abs($lower->colourDifference())) {
            $winner = abs($higher->colourDifference()) > abs($lower->colourDifference()) ? $higher : $lower;
        }

        // 5.2.3: alternate from the most recent round in which they had different colours.
        $winner ??= self::alternationWinner($higher, $lower);

        // 5.2.4: the higher-ranked player's wish.
        $winner ??= $higher;
        $loser = $winner === $higher ? $lower : $higher;

        return $higherWish === 'w' ? [$winner, $loser] : [$loser, $winner];
    }

    private static function otherColour(string $colour): string
    {
        return $colour === 'w' ? 'b' : 'w';
    }

    /**
     * 5.2.3: going back game by game, the first time the two had different
     * colours; the one who had their wished colour then gives way now.
     */
    private static function alternationWinner(SwissPlayer $a, SwissPlayer $b): ?SwissPlayer
    {
        $aIndex = count($a->colours) - 1;
        $bIndex = count($b->colours) - 1;
        $wish = $a->preferredColour();
        while ($aIndex >= 0 && $bIndex >= 0) {
            if ($a->colours[$aIndex] !== $b->colours[$bIndex]) {
                return $a->colours[$aIndex] === $wish ? $b : $a;
            }
            $aIndex--;
            $bIndex--;
        }

        return null;
    }
}
