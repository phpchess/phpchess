<?php

declare(strict_types=1);

namespace PhpChess\Tests\Play;

use PhpChess\Domain\Play\TimeControl;
use PhpChess\Domain\Play\TimedClock;
use PHPUnit\Framework\TestCase;

/**
 * Tests the clock arithmetic and the time controls (G-14, G-15, K12, A12,
 * A46) without a database: delivery decides when a turn starts (capped at
 * 2 s), up to 300 ms per move is given back for the network, and the
 * increment is added after the move.
 */
final class TimedClockTest extends TestCase
{
    private const int SAVED = 1_800_000_000_000;

    // --- When the turn starts ---

    public function testTheTurnStartsWhenThePageReceivedTheMove(): void
    {
        self::assertSame(self::SAVED + 800, TimedClock::effectiveStart(self::SAVED, self::SAVED + 800));
    }

    public function testThePageCanNotHoldTheClockLongerThanTwoSeconds(): void
    {
        self::assertSame(self::SAVED + 2000, TimedClock::effectiveStart(self::SAVED, self::SAVED + 9000));
        self::assertSame(self::SAVED + 2000, TimedClock::effectiveStart(self::SAVED, null));
    }

    public function testADeliveryBeforeTheMoveCountsFromTheMove(): void
    {
        self::assertSame(self::SAVED, TimedClock::effectiveStart(self::SAVED, self::SAVED - 50));
    }

    // --- Time used and lag compensation ---

    public function testUpTo300MsAreGivenBackForTheNetwork(): void
    {
        // Received at +500, moved at +10500: 10 000 ms on the clock, 300 back.
        self::assertSame(9700, TimedClock::timeUsed(self::SAVED, self::SAVED + 500, self::SAVED + 10_500));
    }

    public function testAQuickMoveUsesNoTimeButNeverLessThanNone(): void
    {
        self::assertSame(0, TimedClock::timeUsed(self::SAVED, self::SAVED + 100, self::SAVED + 250));
    }

    public function testTheIncrementIsAddedAfterTheMove(): void
    {
        self::assertSame(300_000 - 9700 + 3000, TimedClock::timeLeftAfterMove(300_000, 9700, 3000));
    }

    public function testTimeLeftNowCanFallBelowZero(): void
    {
        // 1 s left, received at once, 1.5 s later: 1 500 - 300 = 1 200 used.
        self::assertSame(-200, TimedClock::timeLeftNow(1000, self::SAVED, self::SAVED, self::SAVED + 1500));
    }

    public function testTheLatestFlagMomentAddsEveryAllowance(): void
    {
        self::assertSame(self::SAVED + 2000 + 300 + 60_000, TimedClock::latestFlagMs(60_000, self::SAVED));
    }

    // --- Time controls (A12, A46) ---

    public function testTimeControlsAreReadAndTheirSpeedFollowsTheLength(): void
    {
        $blitz = TimeControl::fromText('5+3');
        self::assertNotNull($blitz);
        self::assertSame(300_000, $blitz->baseMs());
        self::assertSame(3000, $blitz->incrementMs());
        self::assertSame(TimeControl::SPEED_BLITZ, $blitz->speed());
        self::assertSame(TimeControl::SPEED_RAPID, TimeControl::fromText('15+10')?->speed());
        self::assertSame(TimeControl::SPEED_CLASSICAL, TimeControl::fromText('120+40')?->speed());
    }

    public function testOnlyTheListedTimeControlsExist(): void
    {
        self::assertNull(TimeControl::fromText('3+0'));
        self::assertNull(TimeControl::fromText('1+0'));
        self::assertNull(TimeControl::fromText('5+3 '));
    }

    public function testTheAdminFloorHidesTheFasterControls(): void
    {
        self::assertSame(TimeControl::ALL, TimeControl::allowed('5+3'));
        $allowed = TimeControl::allowed('10+0');
        self::assertNotContains('5+3', $allowed);
        self::assertContains('10+0', $allowed);
        self::assertContains('120+40', $allowed);
        self::assertSame(TimeControl::ALL, TimeControl::allowed('nonsense'));
    }
}
