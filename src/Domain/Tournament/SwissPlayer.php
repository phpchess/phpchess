<?php

declare(strict_types=1);

namespace PhpChess\Domain\Tournament;

/**
 * What the Swiss pairing needs to know about one player before a round (T-03).
 *
 * - pairingNumber: the starting rank, 1 for the highest rating;
 * - halves:        the score so far in half points (a win is 2);
 * - colours:       the colour of every game really played, oldest first ("w" or "b");
 *                  byes and forfeits are left out, as FIDE C.04.3 says;
 * - opponents:     everyone this player has played (a forfeit does not count), never paired again;
 * - hadPairingBye: a player gets the pairing-bye at most once;
 * - unplayedRounds: rounds without a game (byes, absences, forfeits); the
 *                  bye goes to the player with the fewest (FIDE C.9);
 * - floatLastRound, floatTwoRoundsAgo: "down", "up" or "" (FIDE 1.4): who
 *                  played a lower-scored opponent (down) or a higher one (up),
 *                  or scored without playing (down); used by C14 to C21.
 *
 * Built by TournamentRounds from the pairings; read by DutchPairing.
 */
final class SwissPlayer
{
    // --- Colour preference strength (FIDE C.04.3, A.6) ---
    public const int PREFERENCE_NONE = 0;
    public const int PREFERENCE_MILD = 1;
    public const int PREFERENCE_STRONG = 2;
    public const int PREFERENCE_ABSOLUTE = 3;

    // --- Floats (FIDE 1.4) ---
    public const string FLOAT_DOWN = 'down';
    public const string FLOAT_UP = 'up';

    /**
     * @param list<string> $colours
     * @param list<int> $opponents
     */
    public function __construct(
        public readonly int $id,
        public readonly int $pairingNumber,
        public readonly int $halves,
        public readonly array $colours,
        public readonly array $opponents,
        public readonly bool $hadPairingBye,
        public readonly int $unplayedRounds = 0,
        public readonly string $floatLastRound = '',
        public readonly string $floatTwoRoundsAgo = '',
    ) {
    }

    /** Whites minus blacks of the games played. */
    public function colourDifference(): int
    {
        $difference = 0;
        foreach ($this->colours as $colour) {
            $difference += $colour === 'w' ? 1 : -1;
        }

        return $difference;
    }

    /** The colour this player should get next: "w", "b", or "" when they have no wish. */
    public function preferredColour(): string
    {
        if ($this->colours === []) {
            return '';
        }
        $difference = $this->colourDifference();
        if (abs($difference) > 1) {
            return $difference < 0 ? 'w' : 'b';
        }
        // The same colour in the last two games: the other one, whatever the difference (FIDE 1.7.1).
        $count = count($this->colours);
        if ($count >= 2 && $this->colours[$count - 1] === $this->colours[$count - 2]) {
            return $this->colours[$count - 1] === 'w' ? 'b' : 'w';
        }
        if ($difference !== 0) {
            return $difference < 0 ? 'w' : 'b';
        }

        // Even colours: the opposite of the last game.
        return $this->lastColour() === 'w' ? 'b' : 'w';
    }

    /**
     * How strong the wish is (A.6): absolute when the colour difference is
     * beyond one, or the last two games had the same colour; strong at a
     * difference of one; mild when even.
     */
    public function preferenceStrength(): int
    {
        if ($this->colours === []) {
            return self::PREFERENCE_NONE;
        }
        $difference = $this->colourDifference();
        $count = count($this->colours);
        $lastTwoSame = $count >= 2 && $this->colours[$count - 1] === $this->colours[$count - 2];
        if (abs($difference) > 1 || $lastTwoSame) {
            return self::PREFERENCE_ABSOLUTE;
        }
        if (abs($difference) === 1) {
            return self::PREFERENCE_STRONG;
        }

        return self::PREFERENCE_MILD;
    }

    public function lastColour(): string
    {
        return $this->colours === [] ? '' : $this->colours[count($this->colours) - 1];
    }

    public function hasMet(int $otherId): bool
    {
        return in_array($otherId, $this->opponents, true);
    }
}
