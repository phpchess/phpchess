<?php

declare(strict_types=1);

namespace PhpChess\Domain\Tournament;

/**
 * The tournament calendar (A114, A115): which days are playable, and when
 * each round starts and ends.
 *
 * A day is playable unless it is a rest weekday (for example Saturday and
 * Sunday) or a blocked date (for example a school holiday). There is no
 * built-in holiday list: holidays differ by country and region, so the
 * organiser lists them (A16: no outside requests).
 *
 * Round days and breaks count playable days only: a 5-day round that
 * starts on a Monday with weekends off ends the next Monday.
 *
 * Two ways in:
 * - plan():    start, round count, round days and break  ->  every round's dates;
 * - propose(): start, finish and round count             ->  round days and break.
 *
 * Times are Unix timestamps; days follow the server's time zone. Pure code,
 * no database.
 */
final class TournamentCalendar
{
    /** Days looked ahead at most when searching for a playable day (a year: no endless loop). */
    private const int MOST_DAYS_AHEAD = 366;

    /**
     * @param list<int> $restWeekdays  ISO weekdays without play, 1 = Monday .. 7 = Sunday
     * @param list<string> $blockedDates  single days without play, "2026-12-24"
     */
    public function __construct(
        private readonly array $restWeekdays = [],
        private readonly array $blockedDates = [],
    ) {
    }

    // --- Playable days ---

    public function isPlayable(int $time): bool
    {
        if (in_array((int) date('N', $time), $this->restWeekdays, true)) {
            return false;
        }

        return !in_array(date('Y-m-d', $time), $this->blockedDates, true);
    }

    /** The same time of day on the first playable day from $time on (itself when playable). */
    public function firstPlayableFrom(int $time): int
    {
        for ($day = 0; $day < self::MOST_DAYS_AHEAD && !$this->isPlayable($time); $day++) {
            $time = self::sameTimeNextDay($time);
        }

        return $time;
    }

    /** $time moved on by $days playable days (rest days in between do not count). */
    public function addPlayableDays(int $time, int $days): int
    {
        $counted = 0;
        for ($step = 0; $counted < $days && $step < $days + self::MOST_DAYS_AHEAD; $step++) {
            $time = self::sameTimeNextDay($time);
            if ($this->isPlayable($time)) {
                $counted++;
            }
        }

        return $time;
    }

    /** Playable days from $from up to (not including) $until. */
    public function playableDaysBetween(int $from, int $until): int
    {
        $count = 0;
        for ($time = $from; $time < $until && $count <= 10 * self::MOST_DAYS_AHEAD; $time = self::sameTimeNextDay($time)) {
            if ($this->isPlayable($time)) {
                $count++;
            }
        }

        return $count;
    }

    // --- The plan ---

    /**
     * Every round's start and deadline. Round 1 starts on the first playable
     * day from $start; each deadline is $roundDays playable days after its
     * start; the next round starts $gapDays playable days after that.
     *
     * @return list<array{round: int, start: int, deadline: int}>
     */
    public function plan(int $start, int $roundCount, int $roundDays, int $gapDays): array
    {
        $rounds = [];
        $roundStart = $this->firstPlayableFrom($start);
        for ($round = 1; $round <= $roundCount; $round++) {
            $deadline = $this->addPlayableDays($roundStart, max(1, $roundDays));
            $rounds[] = ['round' => $round, 'start' => $roundStart, 'deadline' => $deadline];
            $roundStart = $this->nextRoundStart($deadline, $gapDays);
        }

        return $rounds;
    }

    /** When the round after one ending at $deadline starts: after the break, on a playable day. */
    public function nextRoundStart(int $deadline, int $gapDays): int
    {
        return $this->firstPlayableFrom($gapDays > 0 ? $this->addPlayableDays($deadline, $gapDays) : $deadline);
    }

    /**
     * Round days and a break that fit $roundCount rounds between $start and
     * $finish; null when there are fewer playable days than rounds.
     * Short rounds get no break; rounds of a week or more get one day.
     *
     * @return array{roundDays: int, gapDays: int}|null
     */
    public function propose(int $start, int $finish, int $roundCount): ?array
    {
        $available = $this->playableDaysBetween($this->firstPlayableFrom($start), $finish);
        if ($roundCount < 1 || $available < $roundCount) {
            return null;
        }
        $roundDays = intdiv($available, $roundCount);
        if ($roundDays < 7) {
            return ['roundDays' => $roundDays, 'gapDays' => 0];
        }
        // With a one-day break between rounds: rounds * days + (rounds - 1) breaks must fit.
        $roundDays = intdiv($available + 1, $roundCount) - 1;

        return ['roundDays' => $roundDays, 'gapDays' => 1];
    }

    // --- How many rounds ---

    /**
     * The number of rounds a tournament will have with $playerCount players
     * (the calendar needs it before pairing). Knockout: one round per
     * stage; extra match games are played inside the round.
     */
    public static function roundCount(Tournament $tournament, int $playerCount): int
    {
        if ($playerCount < 2) {
            return 0;
        }
        $roundRobinRounds = fn (int $players): int => ($players % 2 === 0 ? $players - 1 : $players) * ($tournament->isDoubleRound ? 2 : 1);

        return match ($tournament->format) {
            Tournament::FORMAT_SWISS => $tournament->roundsPlanned > 0
                ? $tournament->roundsPlanned
                : max(1, min($playerCount - 1, (int) ceil(log($playerCount, 2)) + 1)),
            Tournament::FORMAT_KNOCKOUT => (int) ceil(log($playerCount, 2)), // the match for third place is played in the final's round
            // League: the largest division decides the length of the season.
            Tournament::FORMAT_LEAGUE => $roundRobinRounds((int) ceil($playerCount / max(1, $tournament->divisions))),
            default => $roundRobinRounds($playerCount),
        };
    }

    // --- Reading the settings text ---

    /**
     * "6,7" -> [6, 7]; anything that is not a weekday number is dropped.
     *
     * @return list<int>
     */
    public static function weekdaysFrom(string $text): array
    {
        $weekdays = [];
        foreach (explode(',', $text) as $part) {
            $day = (int) trim($part);
            if ($day >= 1 && $day <= 7 && !in_array($day, $weekdays, true)) {
                $weekdays[] = $day;
            }
        }
        sort($weekdays);

        return $weekdays;
    }

    /**
     * Dates separated by commas, spaces or new lines -> ["2026-12-24", ...];
     * anything that is not a real date is dropped.
     *
     * @return list<string>
     */
    public static function datesFrom(string $text): array
    {
        $dates = [];
        foreach (preg_split('/[\s,;]+/', $text) ?: [] as $part) {
            if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $part, $match) === 1
                && checkdate((int) $match[2], (int) $match[3], (int) $match[1])
                && !in_array($part, $dates, true)) {
                $dates[] = $part;
            }
        }
        sort($dates);

        return $dates;
    }

    /** The same clock time one day later (correct across summer-time changes). */
    private static function sameTimeNextDay(int $time): int
    {
        return strtotime('+1 day', $time);
    }
}
