<?php

declare(strict_types=1);

namespace PhpChess\Domain\Play;

/**
 * The arithmetic of the chess clock in timed games (G-14, G-15, K12, A46).
 *
 * All times are milliseconds. The server owns the clock: the browser only
 * shows what the server says.
 *
 * How a turn is timed
 * -------------------
 * When a move is saved, the opponent's turn begins. Their page learns of the
 * move at its next poll, up to a second later. That wait is not their
 * thinking time, so their clock starts when their page first receives the
 * move (the "delivery"). So a page that never asks can not stop the clock,
 * the start is at most MAX_DELIVERY_WAIT_MS after the move was saved.
 *
 * On top of that, every move is given back up to LAG_ALLOWANCE_MS for the
 * time the move itself needed to travel to the server.
 *
 *   effective start = the earlier of (delivery, saved + 2 s)
 *   time used       = now - effective start - up to 300 ms
 *   time left       = time left before - time used (+ increment after a move)
 *
 * These are plain calculations without the database, so they are easy to test.
 */
final class TimedClock
{
    /** The longest wait for the move to reach the opponent's page that does not count. */
    public const int MAX_DELIVERY_WAIT_MS = 2000;

    /** The most time per move given back for the network (lag compensation, capped: K12). */
    public const int LAG_ALLOWANCE_MS = 300;

    /**
     * The moment the player's clock started running for this turn.
     *
     * @param int      $turnSavedMs     when the opponent's move was saved (or the game started)
     * @param int|null $deliveredMs     when the player's page first received it, null when not yet
     */
    public static function effectiveStart(int $turnSavedMs, ?int $deliveredMs): int
    {
        $latestStart = $turnSavedMs + self::MAX_DELIVERY_WAIT_MS;
        if ($deliveredMs === null) {
            return $latestStart;
        }

        return min(max($deliveredMs, $turnSavedMs), $latestStart);
    }

    /** The thinking time used so far in this turn, after the network allowance. */
    public static function timeUsed(int $turnSavedMs, ?int $deliveredMs, int $nowMs): int
    {
        $elapsed = $nowMs - self::effectiveStart($turnSavedMs, $deliveredMs);

        return max(0, $elapsed - self::LAG_ALLOWANCE_MS);
    }

    /** The time left on the running clock now (may be below zero: the flag has fallen). */
    public static function timeLeftNow(int $timeLeftAtTurnStart, int $turnSavedMs, ?int $deliveredMs, int $nowMs): int
    {
        return $timeLeftAtTurnStart - self::timeUsed($turnSavedMs, $deliveredMs, $nowMs);
    }

    /** The time left after a move: what was left, minus the time used, plus the increment. */
    public static function timeLeftAfterMove(int $timeLeftAtTurnStart, int $timeUsed, int $incrementMs): int
    {
        return $timeLeftAtTurnStart - $timeUsed + $incrementMs;
    }

    /**
     * The latest moment the flag can fall in this turn, for the clock job:
     * the player received the move as late as possible and used every
     * allowance.
     */
    public static function latestFlagMs(int $timeLeftAtTurnStart, int $turnSavedMs): int
    {
        return $turnSavedMs + self::MAX_DELIVERY_WAIT_MS + self::LAG_ALLOWANCE_MS + max(0, $timeLeftAtTurnStart);
    }
}
