<?php

declare(strict_types=1);

namespace PhpChess\Domain\Tournament;

/**
 * One tournament, as read from the tournaments table (T-01, A97).
 *
 * Every format uses this one model: Round Robin, Swiss, knockout and League
 * (T-02 to T-05). The fields are the organiser's settings; the status says
 * where the tournament is in its life:
 *
 *   registration -> running -> finished      (or cancelled at any time)
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

    /** Round Robin and League are played from fixed tables, so all their games are known at the start. */
    public function hasFixedTables(): bool
    {
        return $this->format === self::FORMAT_ROUND_ROBIN || $this->format === self::FORMAT_LEAGUE;
    }
}
