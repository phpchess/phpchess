<?php

declare(strict_types=1);

namespace PhpChess\Domain\Tournament;

use PhpChess\Domain\ActionRefused;
use PhpChess\Domain\Computer\ComputerGames;
use PhpChess\Domain\Identity\User;
use PhpChess\Domain\Play\Game;
use PhpChess\Domain\Play\Games;
use PhpChess\Domain\Play\PlayRefused;
use PhpChess\Infra\Identity\UserRepository;
use PhpChess\Infra\Tournament\TournamentRepository;

/**
 * Setting up a tournament (T-01, ADM-06, A97, A98, A105, A109): creating it
 * with simple defaults, its settings, joining and leaving, the organisers,
 * computer players, guest logins and cancelling.
 *
 * What happens once it runs (pairing, rounds, deadlines) is in
 * TournamentRounds; the organiser's tools for real life (absence,
 * withdrawal, replacement, disputes, corrections) are in TournamentDesk.
 *
 * Every method that changes something takes the acting user and checks
 * their right again (K14), throws ActionRefused with a language key when
 * it refuses, and writes a line to the tournament log.
 */
final class Tournaments
{
    /** Guest logins made at once, at most (A109). */
    public const int MOST_GUESTS = 40;

    /** Players in one tournament, at most (keeps pairing and pages quick on shared hosting). */
    public const int MOST_PLAYERS = 200;

    public function __construct(
        private readonly TournamentRepository $tournaments,
        private readonly TournamentAccess $access,
        private readonly Games $games,
        private readonly UserRepository $users,
        private readonly ?ComputerGames $computerGames = null,
    ) {
    }

    // --- Creating and changing ---

    /**
     * Creates a tournament from the organiser's form and returns its id.
     * Everything not filled in gets a sensible default (A107: easy creation).
     *
     * @param array<string, string> $input  the form fields
     */
    public function create(User $organiser, array $input, int $now): int
    {
        if (!$this->access->mayCreate($organiser)) {
            throw new ActionRefused('tournament.not_allowed');
        }
        $settings = $this->checkedSettings($input);
        $tournamentId = $this->tournaments->create($settings, $organiser->id, 1, null, $now);
        if ($settings['registration'] === Tournament::REGISTRATION_INVITE) {
            $this->tournaments->setInviteToken($tournamentId, self::newToken());
        }
        $this->tournaments->log($tournamentId, $organiser->id, 'created', (string) $settings['name'], $now);

        return $tournamentId;
    }

    /**
     * Saves changed settings. Allowed until the tournament starts; after
     * that only the name, description and calendar may change.
     *
     * @param array<string, string> $input
     */
    public function updateSettings(Tournament $tournament, User $manager, array $input, int $now): void
    {
        $this->checkManager($tournament, $manager);
        if ($tournament->isOver()) {
            throw new ActionRefused('tournament.is_over');
        }
        $settings = $this->checkedSettings($input + ['format' => $tournament->format]);
        if (!$tournament->isOpenForRegistration()) {
            // Once it runs: the name, the description and the calendar of the rounds still to come (A114).
            $settings = array_intersect_key($settings, array_flip(['name', 'description', 'round_days', 'round_gap_days', 'rest_weekdays', 'blocked_dates', 'early_rounds']));
        }
        $this->tournaments->updateSettings($tournament->id, $settings);
        if (($settings['registration'] ?? '') === Tournament::REGISTRATION_INVITE && $tournament->inviteToken === null) {
            $this->tournaments->setInviteToken($tournament->id, self::newToken());
        }
        $this->tournaments->log($tournament->id, $manager->id, 'settings', 'Settings saved.', $now);
    }

    /**
     * Turns the form fields into checked settings, with defaults.
     *
     * @param array<string, string> $input
     * @return array<string, string|int|bool|null>
     */
    public function checkedSettings(array $input): array
    {
        $name = trim($input['name'] ?? '');
        if ($name === '' || mb_strlen($name) > 100) {
            throw new ActionRefused('tournament.name_needed');
        }
        $format = $input['format'] ?? Tournament::FORMAT_SWISS;
        if (!in_array($format, Tournament::FORMATS, true)) {
            throw new ActionRefused('tournament.format_unknown');
        }
        $registration = $input['registration'] ?? Tournament::REGISTRATION_OPEN;
        if (!in_array($registration, Tournament::REGISTRATIONS, true)) {
            $registration = Tournament::REGISTRATION_OPEN;
        }

        // --- Pace (A104) ---
        $pace = ($input['pace'] ?? '') === Tournament::PACE_TIMED ? Tournament::PACE_TIMED : Tournament::PACE_CORRESPONDENCE;
        $timeControl = '';
        if ($pace === Tournament::PACE_TIMED) {
            try {
                $timeControl = $this->games->checkedTimeControl(trim($input['time_control'] ?? ''))->text();
            } catch (PlayRefused $refusal) {
                throw new ActionRefused($refusal->languageKey, $refusal->values);
            }
        }
        $daysPerMove = self::between((int) ($input['days_per_move'] ?? 3), 1, $this->games->maximumDays());

        // --- Tie-breaks: the organiser's order, or the format's defaults (A101) ---
        $tieBreaks = [];
        foreach (explode(',', $input['tie_breaks'] ?? '') as $key) {
            $key = trim($key);
            if (in_array($key, TieBreaks::ALL, true) && !in_array($key, $tieBreaks, true)) {
                $tieBreaks[] = $key;
            }
        }
        if ($tieBreaks === []) {
            $tieBreaks = TieBreaks::defaultsFor($format);
        }

        $forfeitRule = $input['forfeit_rule'] ?? TournamentRounds::FORFEIT_LOSS_ON_TIME;
        if (!in_array($forfeitRule, TournamentRounds::FORFEIT_RULES, true)) {
            $forfeitRule = TournamentRounds::FORFEIT_LOSS_ON_TIME;
        }

        // --- Calendar (A114): the planned start is a date and time in the server's time zone ---
        $startsAtText = trim($input['starts_at'] ?? '');
        $startsAt = null;
        if ($startsAtText !== '') {
            $parsed = strtotime(str_replace('T', ' ', $startsAtText));
            if ($parsed === false) {
                throw new ActionRefused('tournament.start_date_unknown');
            }
            $startsAt = $parsed;
        }

        return [
            'name' => $name,
            'description' => mb_substr(trim($input['description'] ?? ''), 0, 2000),
            'format' => $format,
            'registration' => $registration,
            'max_players' => self::between((int) ($input['max_players'] ?? 0), 0, self::MOST_PLAYERS),
            'pace' => $pace,
            'days_per_move' => $daysPerMove,
            'time_control' => $timeControl,
            'round_days' => self::between((int) ($input['round_days'] ?? 14), 1, 120),
            'rounds_planned' => self::between((int) ($input['rounds_planned'] ?? 0), 0, 30),
            'double_round' => ($input['double_round'] ?? '') === 'yes',
            'knockout_games' => ($input['knockout_games'] ?? '1') === '2' ? 2 : 1,
            'third_place' => ($input['third_place'] ?? '') === 'yes',
            'is_rated' => ($input['is_rated'] ?? 'yes') === 'yes',
            'allow_computers' => ($input['allow_computers'] ?? '') === 'yes',
            'confirm_pairings' => ($input['confirm_pairings'] ?? '') === 'yes',
            'forfeit_rule' => $forfeitRule,
            'bye_halves' => self::between((int) ($input['bye_halves'] ?? 2), 0, 2),
            'absence_bye_halves' => self::between((int) ($input['absence_bye_halves'] ?? 1), 0, 2),
            'tie_breaks' => implode(',', $tieBreaks),
            'divisions' => self::between((int) ($input['divisions'] ?? 1), 1, 20),
            'promote_count' => self::between((int) ($input['promote_count'] ?? 2), 0, 10),
            'relegate_count' => self::between((int) ($input['relegate_count'] ?? 2), 0, 10),
            // --- Calendar (A114, A115) ---
            'starts_at' => $startsAt,
            'round_gap_days' => self::between((int) ($input['round_gap_days'] ?? 0), 0, 60),
            'rest_weekdays' => implode(',', TournamentCalendar::weekdaysFrom($input['rest_weekdays'] ?? '')),
            'blocked_dates' => mb_substr(implode(',', TournamentCalendar::datesFrom($input['blocked_dates'] ?? '')), 0, 2000),
            'early_rounds' => ($input['early_rounds'] ?? '') === 'yes',
            // --- Rule options (A113); the form always sends them, a missing field keeps the default ---
            'forfeits_rated' => ($input['forfeits_rated'] ?? 'no') === 'yes',
            'armageddon_higher_black' => ($input['armageddon_higher_black'] ?? 'yes') === 'yes',
            'withdrawal_removes' => ($input['withdrawal_removes'] ?? 'yes') === 'yes',
            // --- Check-in (A117): timed tournaments only ---
            'check_in_minutes' => ($pace === Tournament::PACE_TIMED && $format === Tournament::FORMAT_SWISS) ? self::between((int) ($input['check_in_minutes'] ?? 0), 0, 120) : 0,
        ];
    }

    // --- Joining and leaving ---

    /**
     * A member joins: an open tournament, or an invite-only one with the
     * right invitation token. Swiss also takes late entries while running
     * (A99); they start with no points.
     */
    public function join(Tournament $tournament, User $member, int $now, string $inviteToken = ''): void
    {
        if ($tournament->registration === Tournament::REGISTRATION_ORGANISER) {
            throw new ActionRefused('tournament.only_organiser_adds');
        }
        if ($tournament->registration === Tournament::REGISTRATION_INVITE && ($tournament->inviteToken === null || !hash_equals($tournament->inviteToken, $inviteToken))) {
            throw new ActionRefused('tournament.invitation_needed');
        }
        $guestOf = $this->tournaments->guestTournamentOf($member->id);
        if ($guestOf !== null && $guestOf !== $tournament->id) {
            throw new ActionRefused('tournament.guest_only_tournament');
        }
        $this->addPlayer($tournament, $member->id, $now);
        $this->tournaments->log($tournament->id, $member->id, 'joined', $member->username, $now);
    }

    /** A member leaves before the start. Once it runs, leaving is a withdrawal (TournamentDesk). */
    public function leave(Tournament $tournament, User $member, int $now): void
    {
        if (!$tournament->isOpenForRegistration()) {
            throw new ActionRefused('tournament.already_started');
        }
        $this->tournaments->removePlayer($tournament->id, $member->id);
        $this->tournaments->log($tournament->id, $member->id, 'left', $member->username, $now);
    }

    /** The organiser adds a member by name. */
    public function addPlayerByName(Tournament $tournament, User $manager, string $username, int $now): void
    {
        $this->checkManager($tournament, $manager);
        $user = $this->users->findByUsername(trim($username));
        if ($user === null || $user->state !== User::STATE_ACTIVE) {
            throw new ActionRefused('tournament.player_unknown');
        }
        $this->addPlayer($tournament, $user->id, $now);
        $this->tournaments->log($tournament->id, $manager->id, 'player_added', $user->username, $now);
    }

    /**
     * The organiser adds a computer player (A105): only when the tournament
     * allows it, never in a League, and at most one, so a computer never
     * has to play a computer (their moves are made in the opponent's browser).
     */
    public function addComputer(Tournament $tournament, User $manager, int $computerPlayerId, int $now): void
    {
        $this->checkManager($tournament, $manager);
        if (!$tournament->allowsComputers || $tournament->format === Tournament::FORMAT_LEAGUE || $this->computerGames === null) {
            throw new ActionRefused('tournament.computers_not_allowed');
        }
        foreach ($this->tournaments->players($tournament->id) as $player) {
            if ($player->isComputer) {
                throw new ActionRefused('tournament.one_computer_only');
            }
        }
        $computer = $this->computerGames->findPlayer($computerPlayerId);
        if ($computer === null) {
            throw new ActionRefused('computer.player_not_found');
        }
        $this->addPlayer($tournament, $this->computerGames->accountFor($computer), $now);
        $this->tournaments->log($tournament->id, $manager->id, 'computer_added', $computer->name, $now);
    }

    /** The organiser takes a player off the list before the start. */
    public function removePlayer(Tournament $tournament, User $manager, int $userId, int $now): void
    {
        $this->checkManager($tournament, $manager);
        if (!$tournament->isOpenForRegistration()) {
            throw new ActionRefused('tournament.already_started');
        }
        $this->tournaments->removePlayer($tournament->id, $userId);
        $this->tournaments->log($tournament->id, $manager->id, 'player_removed', (string) ($this->users->usernamesOf([$userId])[$userId] ?? $userId), $now);
    }

    /** Puts a player on the list, checking the state and the size of the tournament. */
    private function addPlayer(Tournament $tournament, int $userId, int $now): void
    {
        $lateSwiss = $tournament->isRunning() && $tournament->format === Tournament::FORMAT_SWISS;
        if (!$tournament->isOpenForRegistration() && !$lateSwiss) {
            throw new ActionRefused('tournament.registration_closed');
        }
        $players = $this->tournaments->players($tournament->id);
        foreach ($players as $player) {
            if ($player->userId === $userId) {
                throw new ActionRefused('tournament.already_joined');
            }
        }
        $limit = $tournament->maxPlayers > 0 ? min($tournament->maxPlayers, self::MOST_PLAYERS) : self::MOST_PLAYERS;
        if (count($players) >= $limit) {
            throw new ActionRefused('tournament.full');
        }
        // The seed is the joining order for now; TournamentRounds::start() sets it from the ratings.
        $joinedRound = $lateSwiss ? $tournament->currentRound + 1 : 1;
        $this->tournaments->addPlayer($tournament->id, $userId, count($players) + 1, 0, 0, $joinedRound, $now);
    }

    // --- Finding and adding many players (A116) ---

    /**
     * The members a player query finds (strongest first), not yet in the
     * tournament, with the place in the field applied.
     *
     * @return list<array{id: int, username: string, rating: int, games: int, joinedAt: int}>
     */
    public function findPlayers(Tournament $tournament, User $manager, PlayerQuery $query, int $startRating, int $now): array
    {
        $this->checkManager($tournament, $manager);

        return $query->apply($this->tournaments->findPlayers($query, $tournament->id, $tournament->ratingSpeed(), $startRating, $now));
    }

    /**
     * Adds the given members; stops quietly when the tournament is full.
     * Returns how many were added and how many were not (already in, full).
     *
     * @param list<int> $userIds
     * @return array{added: int, skipped: int}
     */
    public function addPlayers(Tournament $tournament, User $manager, array $userIds, int $now): array
    {
        $this->checkManager($tournament, $manager);
        $added = 0;
        $skipped = 0;
        foreach (array_unique($userIds) as $userId) {
            try {
                $this->addPlayer($tournament, $userId, $now);
                $added++;
            } catch (ActionRefused) {
                $skipped++;
            }
        }
        $this->tournaments->log($tournament->id, $manager->id, 'players_added', $added . ' players from a query', $now);

        return ['added' => $added, 'skipped' => $skipped];
    }

    // --- Check-in (A117) ---

    /**
     * A player checks in for the next round of a timed tournament with
     * check-in. Open from check_in_minutes before the round until it starts.
     */
    public function checkIn(Tournament $tournament, User $player, int $round, int $now): void
    {
        if (!$tournament->usesCheckIn()) {
            throw new ActionRefused('tournament.no_check_in');
        }
        $isPlayer = false;
        foreach ($this->tournaments->players($tournament->id) as $entry) {
            $isPlayer = $isPlayer || ($entry->userId === $player->id && $entry->isActive());
        }
        if (!$isPlayer) {
            throw new ActionRefused('tournament.not_a_player');
        }
        $this->tournaments->checkIn($tournament->id, $player->id, $round, $now);
    }

    // --- Organisers (A98) ---

    public function addOrganiser(Tournament $tournament, User $manager, string $username, int $now): void
    {
        $this->checkManager($tournament, $manager);
        $user = $this->users->findByUsername(trim($username));
        if ($user === null || $user->state !== User::STATE_ACTIVE) {
            throw new ActionRefused('tournament.player_unknown');
        }
        $this->tournaments->addOrganiser($tournament->id, $user->id);
        $this->tournaments->log($tournament->id, $manager->id, 'organiser_added', $user->username, $now);
    }

    public function removeOrganiser(Tournament $tournament, User $manager, int $userId, int $now): void
    {
        $this->checkManager($tournament, $manager);
        $this->tournaments->removeOrganiser($tournament->id, $userId);
        $this->tournaments->log($tournament->id, $manager->id, 'organiser_removed', (string) $userId, $now);
    }

    /** A new invitation link; the old one stops working. */
    public function renewInvitation(Tournament $tournament, User $manager, int $now): void
    {
        $this->checkManager($tournament, $manager);
        $this->tournaments->setInviteToken($tournament->id, self::newToken());
        $this->tournaments->log($tournament->id, $manager->id, 'invitation_renewed', '', $now);
    }

    // --- Guest logins (A109) ---

    /**
     * Makes guest logins for this tournament, for example for a visiting
     * class: each gets a name and a password, is put on the player list and
     * can play only in this tournament. Returns name => password, shown once
     * to the organiser to hand out.
     *
     * @return array<string, string>
     */
    public function createGuests(Tournament $tournament, User $manager, string $namePrefix, int $count, int $now): array
    {
        $this->checkManager($tournament, $manager);
        if ($tournament->isOver()) {
            throw new ActionRefused('tournament.is_over');
        }
        $prefix = strtolower(preg_replace('/[^a-z0-9]/i', '', $namePrefix) ?? '');
        if ($prefix === '' || strlen($prefix) > 12) {
            throw new ActionRefused('tournament.guest_prefix_needed');
        }
        $count = self::between($count, 1, self::MOST_GUESTS);
        $logins = [];
        $number = 1;
        while (count($logins) < $count && $number < 1000) {
            $username = $prefix . '-' . $number;
            $number++;
            if ($this->users->usernameExists($username)) {
                continue;
            }
            $password = self::readablePassword();
            $userId = $this->users->create($username, null, password_hash($password, PASSWORD_DEFAULT), User::ROLE_PLAYER, User::STATE_ACTIVE);
            $this->tournaments->setGuestTournament($userId, $tournament->id);
            $this->addPlayer($tournament, $userId, $now);
            $logins[$username] = $password;
        }
        $this->tournaments->log($tournament->id, $manager->id, 'guests_created', count($logins) . ' guest logins (' . $prefix . ')', $now);

        return $logins;
    }

    // --- Cancelling ---

    /** Cancels the tournament; its running games end as aborted and count for nothing. */
    public function cancel(Tournament $tournament, User $manager, int $now): void
    {
        $this->checkManager($tournament, $manager);
        if ($tournament->isOver()) {
            throw new ActionRefused('tournament.is_over');
        }
        foreach ($this->tournaments->pairings($tournament->id) as $pairing) {
            if ($pairing->state === Pairing::STATE_PLAYING && $pairing->gameId !== null) {
                $this->games->end($pairing->gameId, Game::RESULT_ABORTED, 'tournament_cancelled', $now, false);
            }
        }
        $this->tournaments->setStatus($tournament->id, Tournament::STATUS_CANCELLED, $now);
        $this->tournaments->log($tournament->id, $manager->id, 'cancelled', '', $now);
    }

    // --- Helpers ---

    private function checkManager(Tournament $tournament, User $user): void
    {
        if (!$this->access->mayManage($tournament, $user)) {
            throw new ActionRefused('tournament.not_allowed');
        }
    }

    private static function between(int $value, int $lowest, int $highest): int
    {
        return max($lowest, min($highest, $value));
    }

    private static function newToken(): string
    {
        return bin2hex(random_bytes(16));
    }

    /** A password a child can type: letters and digits without look-alikes (0/O, 1/l). */
    private static function readablePassword(): string
    {
        $letters = 'abcdefghjkmnpqrstuvwxyz23456789';
        $password = '';
        for ($i = 0; $i < 10; $i++) {
            $password .= $letters[random_int(0, strlen($letters) - 1)];
        }

        return $password;
    }
}
