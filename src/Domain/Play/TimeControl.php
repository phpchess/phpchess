<?php

declare(strict_types=1);

namespace PhpChess\Domain\Play;

/**
 * One time control for timed play: base minutes plus seconds added per move
 * (G-14, A12, A46).
 *
 * Players choose from a fixed list (A46): 5+3, 10+0, 10+5, 15+10, 30+0,
 * 30+20 and the standard 120+40. The admin's floor (A12) hides the faster
 * ones: with the floor at 10+0, 5+3 can not be chosen.
 *
 * A time control is written as text "minutes+seconds", e.g. "5+3". That text
 * is what the database stores (games.time_control, challenges.time_control).
 *
 * The rating category (speed) of a timed game comes from its estimated
 * length: base + 40 × increment, the time for a 40-move game.
 *   below 8 minutes   blitz
 *   below 25 minutes  rapid
 *   25 and more       classical
 */
final class TimeControl
{
    /** Every time control a player can choose, fastest first (A46). */
    public const array ALL = ['5+3', '10+0', '10+5', '15+10', '30+0', '30+20', '120+40'];

    /** The two floors the admin can choose (A12). */
    public const array FLOORS = ['5+3', '10+0'];

    public const string DEFAULT_FLOOR = '5+3';

    // --- Rating categories (speeds) of timed games ---
    public const string SPEED_BLITZ = 'blitz';
    public const string SPEED_RAPID = 'rapid';
    public const string SPEED_CLASSICAL = 'classical';

    private function __construct(
        public readonly int $baseMinutes,
        public readonly int $incrementSeconds,
    ) {
    }

    /** Reads "5+3"; null when the text is not one of the time controls in ALL. */
    public static function fromText(string $text): ?self
    {
        if (!in_array($text, self::ALL, true)) {
            return null;
        }
        [$minutes, $seconds] = explode('+', $text);

        return new self((int) $minutes, (int) $seconds);
    }

    /** "5+3" */
    public function text(): string
    {
        return $this->baseMinutes . '+' . $this->incrementSeconds;
    }

    public function baseMs(): int
    {
        return $this->baseMinutes * 60_000;
    }

    public function incrementMs(): int
    {
        return $this->incrementSeconds * 1000;
    }

    /** The estimated length of a 40-move game for one player, in seconds. */
    public function estimatedSeconds(): int
    {
        return $this->baseMinutes * 60 + 40 * $this->incrementSeconds;
    }

    /** The rating category of games with this time control. */
    public function speed(): string
    {
        $minutes = $this->estimatedSeconds() / 60;
        if ($minutes < 8) {
            return self::SPEED_BLITZ;
        }
        if ($minutes < 25) {
            return self::SPEED_RAPID;
        }

        return self::SPEED_CLASSICAL;
    }

    /**
     * The time controls allowed with the admin's floor: those at least as
     * long as the floor.
     *
     * @return list<string>
     */
    public static function allowed(string $floor): array
    {
        $floorControl = self::fromText(in_array($floor, self::FLOORS, true) ? $floor : self::DEFAULT_FLOOR) ?? new self(5, 3);
        $allowed = [];
        foreach (self::ALL as $text) {
            $control = self::fromText($text);
            if ($control !== null && $control->estimatedSeconds() >= $floorControl->estimatedSeconds()) {
                $allowed[] = $text;
            }
        }

        return $allowed;
    }
}
