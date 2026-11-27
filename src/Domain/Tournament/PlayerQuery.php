<?php

declare(strict_types=1);

namespace PhpChess\Domain\Tournament;

/**
 * A search for players to add to a tournament (A116): by what they are,
 * not by name. Every filter is optional; an empty query finds everyone.
 *
 * - ratingFrom, ratingTo:   rating range (the tournament's speed; unrated players count with the start rating);
 * - place:                  place in the field by rating, among everyone the other filters find:
 *                           top or bottom half, top or bottom quarter;
 * - gamesFrom, gamesTo:     games played at that speed;
 * - joinedAfter, joinedBefore: member since, "2026-01-31";
 * - activeDays:             seen on the site in the last N days;
 * - classId:                in this school class;
 * - nameContains:           letters in the user name.
 *
 * The database part (TournamentRepository::findPlayers) applies all filters
 * but the place; apply() then keeps the wanted part of the field.
 * Built from the query box's fields by fromInput(). Pure code, no database.
 */
final class PlayerQuery
{
    public const string PLACE_ALL = '';
    public const string PLACE_TOP_HALF = 'top_half';
    public const string PLACE_BOTTOM_HALF = 'bottom_half';
    public const string PLACE_TOP_QUARTER = 'top_quarter';
    public const string PLACE_BOTTOM_QUARTER = 'bottom_quarter';
    public const array PLACES = [self::PLACE_ALL, self::PLACE_TOP_HALF, self::PLACE_BOTTOM_HALF, self::PLACE_TOP_QUARTER, self::PLACE_BOTTOM_QUARTER];

    /** The query box's field names, for links and hidden fields. */
    public const array FIELDS = ['rating_from', 'rating_to', 'place', 'games_from', 'games_to', 'joined_after', 'joined_before', 'active_days', 'class_id', 'name_contains'];

    public function __construct(
        public readonly ?int $ratingFrom = null,
        public readonly ?int $ratingTo = null,
        public readonly string $place = self::PLACE_ALL,
        public readonly ?int $gamesFrom = null,
        public readonly ?int $gamesTo = null,
        public readonly ?string $joinedAfter = null,
        public readonly ?string $joinedBefore = null,
        public readonly ?int $activeDays = null,
        public readonly ?int $classId = null,
        public readonly string $nameContains = '',
    ) {
    }

    /**
     * Reads the query box. Empty or unreadable fields are no filter.
     *
     * @param array<string, string> $input
     */
    public static function fromInput(array $input): self
    {
        $number = function (string $key, int $most) use ($input): ?int {
            $text = trim($input[$key] ?? '');

            return preg_match('/^\d{1,6}$/', $text) === 1 ? min($most, (int) $text) : null;
        };
        $date = function (string $key) use ($input): ?string {
            $text = trim($input[$key] ?? '');
            if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $text, $match) !== 1 || !checkdate((int) $match[2], (int) $match[3], (int) $match[1])) {
                return null;
            }

            return $text;
        };
        $place = $input['place'] ?? '';

        return new self(
            $number('rating_from', 4000),
            $number('rating_to', 4000),
            in_array($place, self::PLACES, true) ? $place : self::PLACE_ALL,
            $number('games_from', 1000000),
            $number('games_to', 1000000),
            $date('joined_after'),
            $date('joined_before'),
            $number('active_days', 3650),
            $number('class_id', 4000000000),
            mb_substr(trim($input['name_contains'] ?? ''), 0, 40),
        );
    }

    /**
     * The query box's fields as they were filled in (for the form and the "Add all" button).
     *
     * @return array<string, string>
     */
    public function toInput(): array
    {
        return [
            'rating_from' => (string) ($this->ratingFrom ?? ''),
            'rating_to' => (string) ($this->ratingTo ?? ''),
            'place' => $this->place,
            'games_from' => (string) ($this->gamesFrom ?? ''),
            'games_to' => (string) ($this->gamesTo ?? ''),
            'joined_after' => $this->joinedAfter ?? '',
            'joined_before' => $this->joinedBefore ?? '',
            'active_days' => (string) ($this->activeDays ?? ''),
            'class_id' => (string) ($this->classId ?? ''),
            'name_contains' => $this->nameContains,
        ];
    }

    /**
     * Keeps the wanted place in the field. $found must be sorted by rating,
     * strongest first (as findPlayers returns it). A half of an odd number
     * rounds up: the bottom half of 7 players is 4 players.
     *
     * @template T of array{rating: int}
     * @param list<T> $found
     * @return list<T>
     */
    public function apply(array $found): array
    {
        $count = count($found);

        return match ($this->place) {
            self::PLACE_TOP_HALF => array_slice($found, 0, (int) ceil($count / 2)),
            self::PLACE_BOTTOM_HALF => array_slice($found, $count - (int) ceil($count / 2)),
            self::PLACE_TOP_QUARTER => array_slice($found, 0, (int) ceil($count / 4)),
            self::PLACE_BOTTOM_QUARTER => array_slice($found, $count - (int) ceil($count / 4)),
            default => $found,
        };
    }
}
