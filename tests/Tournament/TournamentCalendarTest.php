<?php

declare(strict_types=1);

namespace PhpChess\Tests\Tournament;

use PhpChess\Domain\Tournament\IcsCalendar;
use PhpChess\Domain\Tournament\PlayerQuery;
use PhpChess\Domain\Tournament\Tournament;
use PhpChess\Domain\Tournament\TournamentCalendar;
use PHPUnit\Framework\TestCase;

/**
 * The tournament calendar (A114), the player query's place in the field
 * (A116) and the .ics file (A117). Pure code, no database.
 *
 * Every test runs in UTC, so the weekdays below are fixed:
 * 2026-12-07 is a Monday.
 */
final class TournamentCalendarTest extends TestCase
{
    private string $oldTimeZone = 'UTC';

    protected function setUp(): void
    {
        $this->oldTimeZone = date_default_timezone_get();
        date_default_timezone_set('UTC');
    }

    protected function tearDown(): void
    {
        date_default_timezone_set($this->oldTimeZone);
    }

    // --- Playable days ---

    public function testRestWeekdaysAndBlockedDatesAreNotPlayable(): void
    {
        $calendar = new TournamentCalendar([6, 7], ['2026-12-09']);

        $this->assertTrue($calendar->isPlayable(self::at('2026-12-07 18:00')));
        $this->assertFalse($calendar->isPlayable(self::at('2026-12-09 18:00')));
        $this->assertFalse($calendar->isPlayable(self::at('2026-12-12 18:00')));
        $this->assertSame(self::at('2026-12-14 18:00'), $calendar->firstPlayableFrom(self::at('2026-12-12 18:00')));
    }

    public function testRoundDaysCountOnlyPlayableDays(): void
    {
        $calendar = new TournamentCalendar([6, 7]);

        // Five playable days from Monday: Tue, Wed, Thu, Fri, (weekend), Mon.
        $this->assertSame(self::at('2026-12-14 18:00'), $calendar->addPlayableDays(self::at('2026-12-07 18:00'), 5));
    }

    // --- The plan ---

    public function testThePlanListsEveryRoundWithBreaks(): void
    {
        $calendar = new TournamentCalendar();

        $plan = $calendar->plan(self::at('2026-12-07 18:00'), 3, 2, 1);

        $this->assertSame([
            ['round' => 1, 'start' => self::at('2026-12-07 18:00'), 'deadline' => self::at('2026-12-09 18:00')],
            ['round' => 2, 'start' => self::at('2026-12-10 18:00'), 'deadline' => self::at('2026-12-12 18:00')],
            ['round' => 3, 'start' => self::at('2026-12-13 18:00'), 'deadline' => self::at('2026-12-15 18:00')],
        ], $plan);
    }

    public function testAProposalFitsBetweenStartAndFinish(): void
    {
        $calendar = new TournamentCalendar();

        // 28 days, 4 rounds: 6 days each with a one-day break (4 x 6 + 3 = 27).
        $this->assertSame(['roundDays' => 6, 'gapDays' => 0], $calendar->propose(self::at('2026-12-07 18:00'), self::at('2027-01-01 00:00'), 4));
        $proposal = $calendar->propose(self::at('2026-12-07 18:00'), self::at('2027-01-04 18:00'), 4);
        $this->assertSame(['roundDays' => 6, 'gapDays' => 1], $proposal);
        $plan = $calendar->plan(self::at('2026-12-07 18:00'), 4, 6, 1);
        $this->assertLessThanOrEqual(self::at('2027-01-04 18:00'), $plan[3]['deadline']);
    }

    public function testNoProposalWhenThereAreFewerDaysThanRounds(): void
    {
        $calendar = new TournamentCalendar([6, 7]);

        $this->assertNull($calendar->propose(self::at('2026-12-07 18:00'), self::at('2026-12-09 18:00'), 5));
    }

    public function testTheRoundCountFollowsTheFormat(): void
    {
        $settings = ['format' => Tournament::FORMAT_ROUND_ROBIN, 'round_days' => 7];

        $this->assertSame(5, TournamentCalendar::roundCount(Tournament::preview($settings), 6));
        $this->assertSame(5, TournamentCalendar::roundCount(Tournament::preview($settings), 5));
        $this->assertSame(10, TournamentCalendar::roundCount(Tournament::preview($settings + ['double_round' => true]), 6));
        $this->assertSame(3, TournamentCalendar::roundCount(Tournament::preview(['format' => Tournament::FORMAT_KNOCKOUT] + $settings), 8));
        $this->assertSame(4, TournamentCalendar::roundCount(Tournament::preview(['format' => Tournament::FORMAT_SWISS] + $settings), 8));
        $this->assertSame(7, TournamentCalendar::roundCount(Tournament::preview(['format' => Tournament::FORMAT_SWISS, 'rounds_planned' => 7] + $settings), 8));
        // League: the largest division (12 players in 2 divisions: 6 each, 5 rounds).
        $this->assertSame(5, TournamentCalendar::roundCount(Tournament::preview(['format' => Tournament::FORMAT_LEAGUE, 'divisions' => 2] + $settings), 12));
    }

    public function testTheSettingsTextIsReadSafely(): void
    {
        $this->assertSame([6, 7], TournamentCalendar::weekdaysFrom('7, 6, 9, x, 6'));
        $this->assertSame(['2026-12-24', '2026-12-25'], TournamentCalendar::datesFrom("2026-12-25, 2026-12-24\n2026-02-30 nonsense"));
    }

    // --- The player query (A116) ---

    public function testThePlaceInTheFieldKeepsTheRightPart(): void
    {
        $field = array_map(fn (int $rating): array => ['rating' => $rating], [1900, 1700, 1500, 1300, 1100, 900, 700]);

        $this->assertSame([1300, 1100, 900, 700], self::ratings(PlayerQuery::fromInput(['place' => 'bottom_half'])->apply($field)));
        $this->assertSame([1900, 1700, 1500, 1300], self::ratings(PlayerQuery::fromInput(['place' => 'top_half'])->apply($field)));
        $this->assertSame([900, 700], self::ratings(PlayerQuery::fromInput(['place' => 'bottom_quarter'])->apply($field)));
        $this->assertCount(7, PlayerQuery::fromInput(['place' => 'nonsense'])->apply($field));
    }

    public function testTheQueryBoxIgnoresWhatIsNotAFilter(): void
    {
        $query = PlayerQuery::fromInput(['rating_from' => '1200', 'rating_to' => 'abc', 'joined_after' => '2026-02-30', 'class_id' => '', 'name_contains' => '  ann ']);

        $this->assertSame(1200, $query->ratingFrom);
        $this->assertNull($query->ratingTo);
        $this->assertNull($query->joinedAfter);
        $this->assertNull($query->classId);
        $this->assertSame('ann', $query->nameContains);
        $this->assertSame('1200', $query->toInput()['rating_from']);
    }

    // --- The .ics file (A117) ---

    public function testTheIcsFileHasOneEventPerRound(): void
    {
        $rounds = [
            ['round' => 1, 'start' => self::at('2026-12-07 18:00'), 'deadline' => self::at('2026-12-09 18:00')],
            ['round' => 2, 'start' => self::at('2026-12-10 18:00'), 'deadline' => self::at('2026-12-12 18:00')],
        ];

        $ics = IcsCalendar::of('Winter Cup, Class 7b', 12, $rounds, 'https://example.test/tournaments/12', 'example.test', self::at('2026-12-01 10:00'));

        $this->assertStringStartsWith("BEGIN:VCALENDAR\r\n", $ics);
        $this->assertSame(2, substr_count($ics, 'BEGIN:VEVENT'));
        $this->assertStringContainsString("DTSTART:20261207T180000Z\r\n", $ics);
        $this->assertStringContainsString('SUMMARY:Winter Cup\, Class 7b - round 2', $ics);
        $this->assertStringContainsString('UID:tournament-12-round-1@example.test', $ics);
        foreach (explode("\r\n", $ics) as $line) {
            $this->assertLessThanOrEqual(75, strlen($line));
        }
    }

    // --- Helpers ---

    private static function at(string $dateTime): int
    {
        return (int) strtotime($dateTime . ' UTC');
    }

    /**
     * @param list<array{rating: int}> $players
     * @return list<int>
     */
    private static function ratings(array $players): array
    {
        return array_map(fn (array $player): int => $player['rating'], $players);
    }
}
