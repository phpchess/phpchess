<?php

declare(strict_types=1);

namespace PhpChess\Domain\Play;

use PhpChess\Infra\Play\VacationRepository;

/**
 * Vacation in correspondence play (G-08, A29).
 *
 * A player takes whole days off, from today or later, up to 30 days in a
 * calendar year. Vacation days pause the clocks in all of the player's
 * correspondence games: Clocks::deadline() skips them. Timed games (C3-1)
 * never pause. After a change, the deadlines of the player's games are
 * worked out again.
 */
final class Vacations
{
    public const int DAYS_PER_YEAR = 30;

    public function __construct(
        private readonly VacationRepository $vacations,
        private readonly Games $games,
    ) {
    }

    /**
     * Takes $numberOfDays days off starting on $firstDay ("YYYY-MM-DD").
     * Throws PlayRefused for a day in the past, a wrong date, or more than
     * 30 days in one year.
     */
    public function take(int $userId, string $firstDay, int $numberOfDays, int $now): void
    {
        $start = strtotime($firstDay . ' 00:00:00 UTC');
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $firstDay) !== 1 || $start === false || gmdate('Y-m-d', $start) !== $firstDay) {
            throw new PlayRefused('vacation.date_invalid');
        }
        if ($firstDay < gmdate('Y-m-d', $now)) {
            throw new PlayRefused('vacation.in_the_past');
        }
        if ($numberOfDays < 1 || $numberOfDays > self::DAYS_PER_YEAR) {
            throw new PlayRefused('vacation.too_many_days', ['max' => self::DAYS_PER_YEAR]);
        }

        // Count the new days per calendar year, a vacation may cross New Year.
        $newDays = [];
        for ($index = 0; $index < $numberOfDays; $index++) {
            $newDays[] = gmdate('Y-m-d', $start + $index * Clocks::SECONDS_PER_DAY);
        }
        $alreadyTaken = $this->vacations->daysFrom($userId, $firstDay);
        $perYear = [];
        foreach (array_diff($newDays, $alreadyTaken) as $day) {
            $year = (int) substr($day, 0, 4);
            $perYear[$year] = ($perYear[$year] ?? 0) + 1;
        }
        foreach ($perYear as $year => $count) {
            if ($this->vacations->countInYear($userId, $year) + $count > self::DAYS_PER_YEAR) {
                throw new PlayRefused('vacation.too_many_days', ['max' => self::DAYS_PER_YEAR]);
            }
        }

        foreach ($newDays as $day) {
            $this->vacations->addDay($userId, $day);
        }
        $this->games->recomputeDeadlinesOf($userId, $now);
    }

    /** Cancels the planned vacation days from tomorrow on; today stays (it has begun). */
    public function cancelPlanned(int $userId, int $now): void
    {
        $this->vacations->removeFrom($userId, gmdate('Y-m-d', $now + Clocks::SECONDS_PER_DAY));
        $this->games->recomputeDeadlinesOf($userId, $now);
    }

    /**
     * The player's vacation days from today on.
     *
     * @return list<string>
     */
    public function upcoming(int $userId, int $now): array
    {
        return $this->vacations->daysFrom($userId, gmdate('Y-m-d', $now));
    }

    /** Days still free this calendar year. */
    public function daysLeftThisYear(int $userId, int $now): int
    {
        return max(0, self::DAYS_PER_YEAR - $this->vacations->countInYear($userId, (int) gmdate('Y', $now)));
    }
}
