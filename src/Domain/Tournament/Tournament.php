<?php

declare(strict_types=1);

namespace PhpChess\Domain\Tournament;

use PhpChess\Domain\Play\Game;
use PhpChess\Domain\Play\TimeControl;

/**
 * One tournament, as read from the tournaments table (T-01, A97).
 *
 * Every format uses this one model: Round Robin, Swiss, knockout and League
 * (T-02 to T-05). The fields are the organiser's settings; the status says
 * where the tournament is in its life:
 *
 *   registration -> running -> finished      (or cancelled at any time)
 *
 * The calendar fields (A114) and rule options (A113) come from migration 13.
 *
 * Scores are in half points: a win is 2, a draw 1 (bye_halves 2 = a full point).
 * Built by TournamentRepository; read by the tournament services and pages.
 */
final class Tournament
{
    // --- Formats (T-02 to T-05) ---
    public const string FORMAT_ROUND_ROBIN = 'round_robin';
    public const string FORMAT_SWISS = 'swiss';
    public const string FORMAT_KNOCKOUT = 'knockout';
    public const string FORMAT_LEAGUE = 'league';
    public const array FORMATS = [self::FORMAT_ROUND_ROBIN, self::FORMAT_SWISS, self::FORMAT_KNOCKOUT, self::FORMAT_LEAGUE];

    // --- Status ---
    public const string STATUS_REGISTRATION = 'registration';
    public const string STATUS_RUNNING = 'running';
    public const string STATUS_FINISHED = 'finished';
    public const string STATUS_CANCELLED = 'cancelled';

    // --- Who may join (A97) ---
    /** Any member may join; anyone on the site may watch the games (A106). */
    public const string REGISTRATION_OPEN = 'open';
    /** Members with the invitation link may join; only its members may watch. */
    public const string REGISTRATION_INVITE = 'invite';
    /** Only the organiser adds players; only its members may watch. */
    public const string REGISTRATION_ORGANISER = 'organiser';
    public const array REGISTRATIONS = [self::REGISTRATION_OPEN, self::REGISTRATION_INVITE, self::REGISTRATION_ORGANISER];

    // --- Pace (A104) ---
    public const string PACE_CORRESPONDENCE = 'correspondence';
    public const string PACE_TIMED = 'timed';

    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $description,
        public readonly string $format,
        public readonly string $status,
        public readonly bool $isPaused,
        public readonly string $registration,
        public readonly ?string $inviteToken,
        public readonly int $maxPlayers,
        public readonly string $pace,
        public readonly int $daysPerMove,
        public readonly string $timeControl,
        public readonly int $roundDays,
        public readonly int $roundsPlanned,
        public readonly bool $isDoubleRound,
        public readonly int $knockoutGames,
        public readonly bool $hasThirdPlaceMatch,
        public readonly bool $isRated,
        public readonly bool $allowsComputers,
        public readonly bool $confirmsPairings,
        public readonly string $forfeitRule,
        public readonly int $byeHalves,
        public readonly int $absenceByeHalves,
        /** @var list<string> */
        public readonly array $tieBreaks,
        public readonly int $divisions,
        public readonly int $promoteCount,
        public readonly int $relegateCount,
        public readonly int $season,
        public readonly ?int $previousSeasonId,
        public readonly int $currentRound,
        public readonly int $createdBy,
        public readonly int $createdAt,
        public readonly ?int $startedAt,
        public readonly ?int $finishedAt,
        // --- Calendar (A114, A115) ---
        public readonly ?int $startsAt = null,
        public readonly int $roundGapDays = 0,
        /** @var list<int> ISO weekdays without play, 1 = Monday */
        public readonly array $restWeekdays = [],
        /** @var list<string> single days without play, "2026-12-24" */
        public readonly array $blockedDates = [],
        public readonly bool $hasEarlyRounds = false,
        // --- Rule options (A113) ---
        public readonly bool $forfeitsRated = false,
        public readonly bool $armageddonHigherSeedBlack = true,
        public readonly bool $withdrawalRemovesResults = true,
        // --- Check-in (A117): minutes before each round of a timed tournament, 0 = none ---
        public readonly int $checkInMinutes = 0,
    ) {
    }

    public function isRunning(): bool
    {
        return $this->status === self::STATUS_RUNNING;
    }

    public function isOpenForRegistration(): bool
    {
        return $this->status === self::STATUS_REGISTRATION;
    }

    public function isOver(): bool
    {
        return $this->status === self::STATUS_FINISHED || $this->status === self::STATUS_CANCELLED;
    }

    /**
     * A closed tournament: only its players and organisers may watch its
     * games (A106). Open tournaments may be watched by every member.
     */
    public function isClosed(): bool
    {
        return $this->registration !== self::REGISTRATION_OPEN;
    }

    public function isTimed(): bool
    {
        return $this->pace === self::PACE_TIMED;
    }

    /**
     * A tournament not saved yet, from checked settings
     * (Tournaments::checkedSettings): for the calendar preview on the
     * create page (A114).
     *
     * @param array<string, string|int|bool|null> $settings
     */
    public static function preview(array $settings): self
    {
        $text = fn (string $key): string => (string) ($settings[$key] ?? '');
        $number = fn (string $key): int => (int) ($settings[$key] ?? 0);
        $flag = fn (string $key): bool => (bool) ($settings[$key] ?? false);
        $startsAt = $settings['starts_at'] ?? null;

        return new self(
            0, $text('name'), $text('description'), $text('format'), self::STATUS_REGISTRATION, false,
            $text('registration'), null, $number('max_players'), $text('pace'), $number('days_per_move'),
            $text('time_control'), max(1, $number('round_days')), $number('rounds_planned'), $flag('double_round'),
            max(1, $number('knockout_games')), $flag('third_place'), $flag('is_rated'), $flag('allow_computers'),
            $flag('confirm_pairings'), $text('forfeit_rule'), $number('bye_halves'), $number('absence_bye_halves'),
            explode(',', $text('tie_breaks')), max(1, $number('divisions')), $number('promote_count'),
            $number('relegate_count'), 1, null, 0, 0, 0, null, null,
            is_int($startsAt) ? $startsAt : null,
            $number('round_gap_days'),
            TournamentCalendar::weekdaysFrom($text('rest_weekdays')),
            TournamentCalendar::datesFrom($text('blocked_dates')),
            $flag('early_rounds'), $flag('forfeits_rated'), $flag('armageddon_higher_black'),
            $flag('withdrawal_removes'), $number('check_in_minutes'),
        );
    }

    /**
     * The settings as the settings form shows them (templates/tournaments/settings_fields.php).
     *
     * @return array<string, string|int|bool>
     */
    public function settingsValues(): array
    {
        return [
            'name' => $this->name, 'description' => $this->description, 'format' => $this->format,
            'registration' => $this->registration, 'max_players' => $this->maxPlayers, 'pace' => $this->pace,
            'days_per_move' => $this->daysPerMove, 'time_control' => $this->timeControl, 'round_days' => $this->roundDays,
            'rounds_planned' => $this->roundsPlanned, 'double_round' => $this->isDoubleRound, 'knockout_games' => $this->knockoutGames,
            'third_place' => $this->hasThirdPlaceMatch, 'is_rated' => $this->isRated, 'allow_computers' => $this->allowsComputers,
            'confirm_pairings' => $this->confirmsPairings, 'forfeit_rule' => $this->forfeitRule, 'bye_halves' => $this->byeHalves,
            'absence_bye_halves' => $this->absenceByeHalves, 'tie_breaks' => implode(',', $this->tieBreaks),
            'divisions' => $this->divisions, 'promote_count' => $this->promoteCount, 'relegate_count' => $this->relegateCount,
            // The planned start as the date-and-time box wants it: "2026-12-01T18:00".
            'starts_at' => $this->startsAt === null ? '' : date('Y-m-d', $this->startsAt) . 'T' . date('H:i', $this->startsAt),
            'round_gap_days' => $this->roundGapDays, 'rest_weekdays' => implode(',', $this->restWeekdays),
            'blocked_dates' => implode(', ', $this->blockedDates), 'early_rounds' => $this->hasEarlyRounds,
            'forfeits_rated' => $this->forfeitsRated, 'armageddon_higher_black' => $this->armageddonHigherSeedBlack,
            'withdrawal_removes' => $this->withdrawalRemovesResults, 'check_in_minutes' => $this->checkInMinutes,
        ];
    }

    /** Check-in before each round (A117): timed Swiss tournaments with check-in minutes set. */
    public function usesCheckIn(): bool
    {
        return $this->checkInMinutes > 0 && $this->isTimed() && $this->format === self::FORMAT_SWISS;
    }

    /** The rating list this tournament's games count for: correspondence, or the speed of its time control. */
    public function ratingSpeed(): string
    {
        $control = $this->isTimed() ? TimeControl::fromText($this->timeControl) : null;

        return $control === null ? Game::SPEED_CORRESPONDENCE : $control->speed();
    }

    /** The calendar of this tournament: which days are playable (A114). */
    public function calendar(): TournamentCalendar
    {
        return new TournamentCalendar($this->restWeekdays, $this->blockedDates);
    }

    /** Round Robin and League are played from fixed tables, so all their games are known at the start. */
    public function hasFixedTables(): bool
    {
        return $this->format === self::FORMAT_ROUND_ROBIN || $this->format === self::FORMAT_LEAGUE;
    }
}
