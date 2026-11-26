<?php

declare(strict_types=1);

namespace PhpChess\Infra\Tournament;

use PhpChess\Domain\Tournament\Pairing;
use PhpChess\Domain\Tournament\Tournament;
use PhpChess\Domain\Tournament\TournamentPlayer;
use PhpChess\Infra\Repository;

/**
 * The tournament tables (C4-1, migration 12): tournaments, organisers,
 * players, rounds, pairings, absences, disputes and the tournament log.
 * Also the two tournament columns of users: can_organise and guest_tournament_id.
 *
 * Plain reads and writes with prepared statements (K5); the rules live in
 * the services of Domain/Tournament. A pairing's score is written only by
 * TournamentResults (from a game's result, K3) and the round services
 * (byes and forfeits).
 */
final class TournamentRepository extends Repository
{
    /** The tournament columns, in the order fromRow() reads them. */
    private const string TOURNAMENT_COLUMNS = 'id, name, description, format, status, is_paused, registration, invite_token, max_players,'
        . ' pace, days_per_move, time_control, round_days, rounds_planned, double_round, knockout_games, third_place, is_rated,'
        . ' allow_computers, confirm_pairings, forfeit_rule, bye_halves, absence_bye_halves, tie_breaks, divisions, promote_count,'
        . ' relegate_count, season, previous_season_id, current_round, created_by, created_at, started_at, finished_at';

    /** The settings an organiser may change, with their column names (fixed text, never from input). */
    public const array SETTING_COLUMNS = [
        'name', 'description', 'format', 'registration', 'max_players', 'pace', 'days_per_move', 'time_control', 'round_days',
        'rounds_planned', 'double_round', 'knockout_games', 'third_place', 'is_rated', 'allow_computers', 'confirm_pairings',
        'forfeit_rule', 'bye_halves', 'absence_bye_halves', 'tie_breaks', 'divisions', 'promote_count', 'relegate_count',
    ];

    private const string PAIRING_COLUMNS = 'id, tournament_id, round, division, board, match_number, game_number, is_armageddon,'
        . ' white_id, black_id, game_id, state, outcome, white_halves, black_halves, deadline_at';

    // --- Tournaments ---

    /**
     * Creates a tournament from its settings and returns its id.
     *
     * @param array<string, string|int|bool> $settings  keys from SETTING_COLUMNS
     */
    public function create(array $settings, int $createdBy, int $season, ?int $previousSeasonId, int $now): int
    {
        $columns = [];
        $values = [];
        foreach (self::SETTING_COLUMNS as $column) {
            if (array_key_exists($column, $settings)) {
                $columns[] = $column;
                $values[] = is_bool($settings[$column]) ? ($settings[$column] ? 1 : 0) : $settings[$column];
            }
        }
        $columns = [...$columns, 'season', 'previous_season_id', 'created_by', 'created_at'];
        $values = [...$values, $season, $previousSeasonId, $createdBy, self::dateTime($now)];
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $this->database->run(
            'INSERT INTO ' . $this->table('tournaments') . ' (' . implode(', ', $columns) . ') VALUES (' . $placeholders . ')',
            $values,
        );

        return $this->database->lastInsertId();
    }

    /**
     * Saves changed settings. Only columns from SETTING_COLUMNS are written.
     *
     * @param array<string, string|int|bool> $settings
     */
    public function updateSettings(int $tournamentId, array $settings): void
    {
        $assignments = [];
        $values = [];
        foreach (self::SETTING_COLUMNS as $column) {
            if (array_key_exists($column, $settings)) {
                $assignments[] = $column . ' = ?';
                $values[] = is_bool($settings[$column]) ? ($settings[$column] ? 1 : 0) : $settings[$column];
            }
        }
        if ($assignments === []) {
            return;
        }
        $values[] = $tournamentId;
        $this->database->run('UPDATE ' . $this->table('tournaments') . ' SET ' . implode(', ', $assignments) . ' WHERE id = ?', $values);
    }

    public function find(int $tournamentId): ?Tournament
    {
        $row = $this->database->fetchRow('SELECT ' . self::TOURNAMENT_COLUMNS . ' FROM ' . $this->table('tournaments') . ' WHERE id = ?', [$tournamentId]);

        return $row === null ? null : self::tournamentFrom($row);
    }

    public function findByInviteToken(string $token): ?Tournament
    {
        $row = $this->database->fetchRow('SELECT ' . self::TOURNAMENT_COLUMNS . ' FROM ' . $this->table('tournaments') . ' WHERE invite_token = ?', [$token]);

        return $row === null ? null : self::tournamentFrom($row);
    }

    /**
     * Tournaments with one of the given states, newest first.
     *
     * @param list<string> $statuses
     * @return list<Tournament>
     */
    public function listByStatus(array $statuses, int $limit = 100): array
    {
        if ($statuses === []) {
            return [];
        }
        $placeholders = implode(', ', array_fill(0, count($statuses), '?'));
        $rows = $this->database->fetchAll(
            'SELECT ' . self::TOURNAMENT_COLUMNS . ' FROM ' . $this->table('tournaments')
            . ' WHERE status IN (' . $placeholders . ') ORDER BY id DESC LIMIT ' . max(1, min(1000, $limit)),
            $statuses,
        );

        return array_map(fn (array $row): Tournament => self::tournamentFrom($row), $rows);
    }

    /** @return list<int> */
    public function runningTournamentIds(): array
    {
        $rows = $this->database->fetchAll('SELECT id FROM ' . $this->table('tournaments') . " WHERE status = 'running' AND is_paused = 0 ORDER BY id");

        return array_map(fn (array $row): int => self::intOf($row['id']), $rows);
    }

    public function setStatus(int $tournamentId, string $status, int $now): void
    {
        $timeColumn = match ($status) {
            Tournament::STATUS_RUNNING => 'started_at',
            Tournament::STATUS_FINISHED, Tournament::STATUS_CANCELLED => 'finished_at',
            default => null,
        };
        if ($timeColumn === null) {
            $this->database->run('UPDATE ' . $this->table('tournaments') . ' SET status = ? WHERE id = ?', [$status, $tournamentId]);

            return;
        }
        $this->database->run(
            'UPDATE ' . $this->table('tournaments') . ' SET status = ?, ' . $timeColumn . ' = ? WHERE id = ?',
            [$status, self::dateTime($now), $tournamentId],
        );
    }

    public function setPaused(int $tournamentId, bool $isPaused): void
    {
        $this->database->run('UPDATE ' . $this->table('tournaments') . ' SET is_paused = ? WHERE id = ?', [$isPaused ? 1 : 0, $tournamentId]);
    }

    public function setCurrentRound(int $tournamentId, int $round): void
    {
        $this->database->run('UPDATE ' . $this->table('tournaments') . ' SET current_round = ? WHERE id = ?', [$round, $tournamentId]);
    }

    public function setInviteToken(int $tournamentId, ?string $token): void
    {
        $this->database->run('UPDATE ' . $this->table('tournaments') . ' SET invite_token = ? WHERE id = ?', [$token, $tournamentId]);
    }

    /** The next season of a League, if one was made already. */
    public function nextSeasonId(int $tournamentId): ?int
    {
        return self::intOrNull($this->database->fetchValue('SELECT id FROM ' . $this->table('tournaments') . ' WHERE previous_season_id = ?', [$tournamentId]));
    }

    // --- Organisers (A98) ---

    public function addOrganiser(int $tournamentId, int $userId): void
    {
        $this->database->run('INSERT IGNORE INTO ' . $this->table('tournament_organisers') . ' (tournament_id, user_id) VALUES (?, ?)', [$tournamentId, $userId]);
    }

    public function removeOrganiser(int $tournamentId, int $userId): void
    {
        $this->database->run('DELETE FROM ' . $this->table('tournament_organisers') . ' WHERE tournament_id = ? AND user_id = ?', [$tournamentId, $userId]);
    }

    /** @return list<int> */
    public function organiserIds(int $tournamentId): array
    {
        $rows = $this->database->fetchAll('SELECT user_id FROM ' . $this->table('tournament_organisers') . ' WHERE tournament_id = ? ORDER BY user_id', [$tournamentId]);

        return array_map(fn (array $row): int => self::intOf($row['user_id']), $rows);
    }

    // --- Players ---

    public function addPlayer(int $tournamentId, int $userId, int $seed, int $division, int $rating, int $joinedRound, int $now): void
    {
        $this->database->run(
            'INSERT INTO ' . $this->table('tournament_players')
            . ' (tournament_id, user_id, state, seed, division, rating, joined_round, joined_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [$tournamentId, $userId, TournamentPlayer::STATE_ACTIVE, $seed, $division, $rating, $joinedRound, self::dateTime($now)],
        );
    }

    public function removePlayer(int $tournamentId, int $userId): void
    {
        $this->database->run('DELETE FROM ' . $this->table('tournament_players') . ' WHERE tournament_id = ? AND user_id = ?', [$tournamentId, $userId]);
    }

    /**
     * Every player of a tournament, by seed (then name).
     *
     * @return list<TournamentPlayer>
     */
    public function players(int $tournamentId): array
    {
        $rows = $this->database->fetchAll(
            'SELECT p.tournament_id, p.user_id, u.username, p.state, p.seed, p.division, p.rating, p.joined_round, p.withdrawn_round,'
            . ' u.computer_player_id, u.guest_tournament_id'
            . ' FROM ' . $this->table('tournament_players') . ' p JOIN ' . $this->table('users') . ' u ON u.id = p.user_id'
            . ' WHERE p.tournament_id = ? ORDER BY p.division, p.seed, u.username',
            [$tournamentId],
        );

        return array_map(fn (array $row): TournamentPlayer => new TournamentPlayer(
            self::intOf($row['tournament_id']),
            self::intOf($row['user_id']),
            self::textOf($row['username']),
            self::textOf($row['state']),
            self::intOf($row['seed']),
            self::intOf($row['division']),
            self::intOf($row['rating']),
            self::intOf($row['joined_round']),
            self::intOrNull($row['withdrawn_round']),
            $row['computer_player_id'] !== null,
            $row['guest_tournament_id'] !== null,
        ), $rows);
    }

    public function setSeedAndDivision(int $tournamentId, int $userId, int $seed, int $division): void
    {
        $this->database->run(
            'UPDATE ' . $this->table('tournament_players') . ' SET seed = ?, division = ? WHERE tournament_id = ? AND user_id = ?',
            [$seed, $division, $tournamentId, $userId],
        );
    }

    public function setRating(int $tournamentId, int $userId, int $rating): void
    {
        $this->database->run('UPDATE ' . $this->table('tournament_players') . ' SET rating = ? WHERE tournament_id = ? AND user_id = ?', [$rating, $tournamentId, $userId]);
    }

    /** Marks a player as withdrawn or removed (TournamentPlayer::STATE_*), from a round on. */
    public function markWithdrawn(int $tournamentId, int $userId, int $round, string $state): void
    {
        $this->database->run(
            'UPDATE ' . $this->table('tournament_players') . ' SET state = ?, withdrawn_round = ? WHERE tournament_id = ? AND user_id = ?',
            [$state, $round, $tournamentId, $userId],
        );
    }

    /**
     * Puts a new player in the place of another: in the player list and in
     * every pairing that has no game yet (A108, replacement).
     */
    public function replacePlayer(int $tournamentId, int $oldUserId, int $newUserId): void
    {
        $this->database->run(
            'UPDATE ' . $this->table('tournament_players') . ' SET user_id = ?, state = ?, withdrawn_round = NULL WHERE tournament_id = ? AND user_id = ?',
            [$newUserId, TournamentPlayer::STATE_ACTIVE, $tournamentId, $oldUserId],
        );
        foreach (['white_id', 'black_id'] as $column) {
            $this->database->run(
                'UPDATE ' . $this->table('tournament_pairings') . ' SET ' . $column . ' = ? WHERE tournament_id = ? AND ' . $column . ' = ? AND game_id IS NULL',
                [$newUserId, $tournamentId, $oldUserId],
            );
        }
    }

    /**
     * The tournaments a user plays in or organises, newest first.
     *
     * @return list<int>
     */
    public function tournamentIdsOf(int $userId): array
    {
        $rows = $this->database->fetchAll(
            'SELECT tournament_id FROM ' . $this->table('tournament_players') . ' WHERE user_id = ?'
            . ' UNION SELECT tournament_id FROM ' . $this->table('tournament_organisers') . ' WHERE user_id = ?'
            . ' UNION SELECT id FROM ' . $this->table('tournaments') . ' WHERE created_by = ?'
            . ' ORDER BY 1 DESC',
            [$userId, $userId, $userId],
        );

        return array_map(fn (array $row): int => self::intOf($row['tournament_id']), $rows);
    }

    // --- Rounds ---

    public function createRound(int $tournamentId, int $number, string $state): void
    {
        $this->database->run(
            'INSERT INTO ' . $this->table('tournament_rounds') . ' (tournament_id, number, state) VALUES (?, ?, ?)',
            [$tournamentId, $number, $state],
        );
    }

    public function deleteRound(int $tournamentId, int $number): void
    {
        $this->database->run('DELETE FROM ' . $this->table('tournament_pairings') . ' WHERE tournament_id = ? AND round = ? AND game_id IS NULL', [$tournamentId, $number]);
        $this->database->run('DELETE FROM ' . $this->table('tournament_rounds') . ' WHERE tournament_id = ? AND number = ?', [$tournamentId, $number]);
    }

    public function startRound(int $tournamentId, int $number, int $now, ?int $deadline): void
    {
        $this->database->run(
            'UPDATE ' . $this->table('tournament_rounds') . " SET state = 'running', started_at = ?, deadline_at = ? WHERE tournament_id = ? AND number = ?",
            [self::dateTime($now), $deadline === null ? null : self::dateTime($deadline), $tournamentId, $number],
        );
    }

    public function closeRound(int $tournamentId, int $number, int $now): void
    {
        $this->database->run(
            'UPDATE ' . $this->table('tournament_rounds') . " SET state = 'closed', closed_at = ? WHERE tournament_id = ? AND number = ?",
            [self::dateTime($now), $tournamentId, $number],
        );
    }

    /**
     * The rounds of a tournament.
     *
     * @return list<array{number: int, state: string, startedAt: ?int, deadlineAt: ?int, closedAt: ?int}>
     */
    public function rounds(int $tournamentId): array
    {
        $rows = $this->database->fetchAll(
            'SELECT number, state, started_at, deadline_at, closed_at FROM ' . $this->table('tournament_rounds') . ' WHERE tournament_id = ? ORDER BY number',
            [$tournamentId],
        );

        return array_map(fn (array $row): array => [
            'number' => self::intOf($row['number']),
            'state' => self::textOf($row['state']),
            'startedAt' => self::timestampOrNull($row['started_at']),
            'deadlineAt' => self::timestampOrNull($row['deadline_at']),
            'closedAt' => self::timestampOrNull($row['closed_at']),
        ], $rows);
    }

    // --- Pairings ---

    public function addPairing(int $tournamentId, int $round, int $division, int $board, int $whiteId, ?int $blackId, int $matchNumber = 0, int $gameNumber = 1, bool $isArmageddon = false): int
    {
        $this->database->run(
            'INSERT INTO ' . $this->table('tournament_pairings')
            . ' (tournament_id, round, division, board, match_number, game_number, is_armageddon, white_id, black_id, state)'
            . ' VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$tournamentId, $round, $division, $board, $matchNumber, $gameNumber, $isArmageddon ? 1 : 0, $whiteId, $blackId, Pairing::STATE_WAITING],
        );

        return $this->database->lastInsertId();
    }

    /** @return list<Pairing> */
    public function pairings(int $tournamentId): array
    {
        $rows = $this->database->fetchAll(
            'SELECT ' . self::PAIRING_COLUMNS . ' FROM ' . $this->table('tournament_pairings')
            . ' WHERE tournament_id = ? ORDER BY round, division, match_number, game_number, board',
            [$tournamentId],
        );

        return array_map(fn (array $row): Pairing => self::pairingFrom($row), $rows);
    }

    public function findPairing(int $pairingId): ?Pairing
    {
        $row = $this->database->fetchRow('SELECT ' . self::PAIRING_COLUMNS . ' FROM ' . $this->table('tournament_pairings') . ' WHERE id = ?', [$pairingId]);

        return $row === null ? null : self::pairingFrom($row);
    }

    public function findPairingByGame(int $gameId): ?Pairing
    {
        $row = $this->database->fetchRow('SELECT ' . self::PAIRING_COLUMNS . ' FROM ' . $this->table('tournament_pairings') . ' WHERE game_id = ?', [$gameId]);

        return $row === null ? null : self::pairingFrom($row);
    }

    public function setPairingGame(int $pairingId, int $gameId, ?int $deadline): void
    {
        $this->database->run(
            'UPDATE ' . $this->table('tournament_pairings') . ' SET game_id = ?, state = ?, outcome = ?, white_halves = 0, black_halves = 0, deadline_at = ? WHERE id = ?',
            [$gameId, Pairing::STATE_PLAYING, '', $deadline === null ? null : self::dateTime($deadline), $pairingId],
        );
    }

    /** Writes how a board was decided and its score in half points. */
    public function writeOutcome(int $pairingId, string $outcome, int $whiteHalves, int $blackHalves): void
    {
        $this->database->run(
            'UPDATE ' . $this->table('tournament_pairings') . ' SET state = ?, outcome = ?, white_halves = ?, black_halves = ? WHERE id = ?',
            [Pairing::STATE_DONE, $outcome, $whiteHalves, $blackHalves, $pairingId],
        );
    }

    public function setPairingState(int $pairingId, string $state): void
    {
        $this->database->run('UPDATE ' . $this->table('tournament_pairings') . ' SET state = ? WHERE id = ?', [$state, $pairingId]);
    }

    public function setPairingDeadline(int $pairingId, ?int $deadline): void
    {
        $this->database->run(
            'UPDATE ' . $this->table('tournament_pairings') . ' SET deadline_at = ? WHERE id = ?',
            [$deadline === null ? null : self::dateTime($deadline), $pairingId],
        );
    }

    /** Takes a pairing back to "waiting" with no game, for a replay (A108). */
    public function resetPairing(int $pairingId): void
    {
        $this->database->run(
            'UPDATE ' . $this->table('tournament_pairings') . ' SET game_id = NULL, state = ?, outcome = ?, white_halves = 0, black_halves = 0 WHERE id = ?',
            [Pairing::STATE_WAITING, '', $pairingId],
        );
    }

    /**
     * The tournament a game belongs to, or null. Used by the game pages for
     * watching (A106) and by Games::end() through TournamentResults. Before
     * migration 12 has run the table does not exist; then no game belongs
     * to a tournament, and the game pages keep working (A54).
     */
    public function tournamentIdOfGame(int $gameId): ?int
    {
        try {
            return self::intOrNull($this->database->fetchValue('SELECT tournament_id FROM ' . $this->table('tournament_pairings') . ' WHERE game_id = ?', [$gameId]));
        } catch (\PDOException) {
            return null;
        }
    }

    // --- Absences (A108) ---

    public function addAbsence(int $tournamentId, int $userId, int $round): void
    {
        $this->database->run('INSERT IGNORE INTO ' . $this->table('tournament_absences') . ' (tournament_id, user_id, round) VALUES (?, ?, ?)', [$tournamentId, $userId, $round]);
    }

    public function removeAbsence(int $tournamentId, int $userId, int $round): void
    {
        $this->database->run('DELETE FROM ' . $this->table('tournament_absences') . ' WHERE tournament_id = ? AND user_id = ? AND round = ?', [$tournamentId, $userId, $round]);
    }

    /**
     * user id => list of rounds they will miss.
     *
     * @return array<int, list<int>>
     */
    public function absences(int $tournamentId): array
    {
        $rows = $this->database->fetchAll('SELECT user_id, round FROM ' . $this->table('tournament_absences') . ' WHERE tournament_id = ? ORDER BY round', [$tournamentId]);
        $absences = [];
        foreach ($rows as $row) {
            $absences[self::intOf($row['user_id'])][] = self::intOf($row['round']);
        }

        return $absences;
    }

    // --- Disputes (A108) ---

    public function createDispute(int $tournamentId, int $pairingId, int $raisedBy, string $reason, int $now): int
    {
        $this->database->run(
            'INSERT INTO ' . $this->table('tournament_disputes') . ' (tournament_id, pairing_id, raised_by, reason, decision_note, created_at) VALUES (?, ?, ?, ?, ?, ?)',
            [$tournamentId, $pairingId, $raisedBy, $reason, '', self::dateTime($now)],
        );

        return $this->database->lastInsertId();
    }

    /**
     * Disputes, the open ones first; for one tournament, or all when $tournamentId is null.
     *
     * @return list<array{id: int, tournamentId: int, pairingId: int, raisedBy: int, reason: string, state: string, decision: string, decisionNote: string, decidedBy: ?int, createdAt: ?int}>
     */
    public function disputes(?int $tournamentId, bool $onlyOpen = false, int $limit = 100): array
    {
        $where = [];
        $values = [];
        if ($tournamentId !== null) {
            $where[] = 'tournament_id = ?';
            $values[] = $tournamentId;
        }
        if ($onlyOpen) {
            $where[] = "state = 'open'";
        }
        $rows = $this->database->fetchAll(
            'SELECT id, tournament_id, pairing_id, raised_by, reason, state, decision, decision_note, decided_by, created_at FROM '
            . $this->table('tournament_disputes') . ($where === [] ? '' : ' WHERE ' . implode(' AND ', $where))
            . " ORDER BY state = 'open' DESC, id DESC LIMIT " . max(1, min(500, $limit)),
            $values,
        );

        return array_map(fn (array $row): array => [
            'id' => self::intOf($row['id']),
            'tournamentId' => self::intOf($row['tournament_id']),
            'pairingId' => self::intOf($row['pairing_id']),
            'raisedBy' => self::intOf($row['raised_by']),
            'reason' => self::textOf($row['reason']),
            'state' => self::textOf($row['state']),
            'decision' => self::textOf($row['decision']),
            'decisionNote' => self::textOf($row['decision_note']),
            'decidedBy' => self::intOrNull($row['decided_by']),
            'createdAt' => self::timestampOrNull($row['created_at']),
        ], $rows);
    }

    public function decideDispute(int $disputeId, string $decision, string $note, int $decidedBy, int $now): void
    {
        $this->database->run(
            'UPDATE ' . $this->table('tournament_disputes') . " SET state = 'decided', decision = ?, decision_note = ?, decided_by = ?, decided_at = ? WHERE id = ? AND state = 'open'",
            [$decision, $note, $decidedBy, self::dateTime($now), $disputeId],
        );
    }

    // --- The tournament log ---

    public function log(int $tournamentId, ?int $userId, string $action, string $details, int $now): void
    {
        $this->database->run(
            'INSERT INTO ' . $this->table('tournament_log') . ' (tournament_id, user_id, action, details, created_at) VALUES (?, ?, ?, ?, ?)',
            [$tournamentId, $userId, $action, mb_substr($details, 0, 500), self::dateTime($now)],
        );
    }

    /**
     * The newest log lines of a tournament.
     *
     * @return list<array{userId: ?int, action: string, details: string, createdAt: ?int}>
     */
    public function logOf(int $tournamentId, int $limit = 50): array
    {
        $rows = $this->database->fetchAll(
            'SELECT user_id, action, details, created_at FROM ' . $this->table('tournament_log') . ' WHERE tournament_id = ? ORDER BY id DESC LIMIT ' . max(1, min(500, $limit)),
            [$tournamentId],
        );

        return array_map(fn (array $row): array => [
            'userId' => self::intOrNull($row['user_id']),
            'action' => self::textOf($row['action']),
            'details' => self::textOf($row['details']),
            'createdAt' => self::timestampOrNull($row['created_at']),
        ], $rows);
    }

    // --- Who may organise; guest logins (A98, A109) ---

    public function canOrganise(int $userId): bool
    {
        return self::intOf($this->database->fetchValue('SELECT can_organise FROM ' . $this->table('users') . ' WHERE id = ?', [$userId])) === 1;
    }

    public function setCanOrganise(int $userId, bool $canOrganise): void
    {
        $this->database->run('UPDATE ' . $this->table('users') . ' SET can_organise = ? WHERE id = ?', [$canOrganise ? 1 : 0, $userId]);
    }

    /** @return array<int, string> user id => name of everyone allowed to organise */
    public function organiserNames(): array
    {
        $rows = $this->database->fetchAll('SELECT id, username FROM ' . $this->table('users') . ' WHERE can_organise = 1 ORDER BY username');
        $names = [];
        foreach ($rows as $row) {
            $names[self::intOf($row['id'])] = self::textOf($row['username']);
        }

        return $names;
    }

    public function setGuestTournament(int $userId, int $tournamentId): void
    {
        $this->database->run('UPDATE ' . $this->table('users') . ' SET guest_tournament_id = ? WHERE id = ?', [$tournamentId, $userId]);
    }

    /** The tournament a guest login belongs to, or null for a normal member. */
    public function guestTournamentOf(int $userId): ?int
    {
        return self::intOrNull($this->database->fetchValue('SELECT guest_tournament_id FROM ' . $this->table('users') . ' WHERE id = ?', [$userId]));
    }

    // --- Turning rows into objects ---

    /**
     * @param array<string, mixed> $row
     */
    private static function tournamentFrom(array $row): Tournament
    {
        $tieBreaks = array_values(array_filter(explode(',', self::textOf($row['tie_breaks'])), fn (string $key): bool => $key !== ''));

        return new Tournament(
            self::intOf($row['id']),
            self::textOf($row['name']),
            self::textOf($row['description']),
            self::textOf($row['format']),
            self::textOf($row['status']),
            self::intOf($row['is_paused']) === 1,
            self::textOf($row['registration']),
            $row['invite_token'] === null ? null : self::textOf($row['invite_token']),
            self::intOf($row['max_players']),
            self::textOf($row['pace']),
            self::intOf($row['days_per_move']),
            self::textOf($row['time_control']),
            self::intOf($row['round_days']),
            self::intOf($row['rounds_planned']),
            self::intOf($row['double_round']) === 1,
            self::intOf($row['knockout_games']),
            self::intOf($row['third_place']) === 1,
            self::intOf($row['is_rated']) === 1,
            self::intOf($row['allow_computers']) === 1,
            self::intOf($row['confirm_pairings']) === 1,
            self::textOf($row['forfeit_rule']),
            self::intOf($row['bye_halves']),
            self::intOf($row['absence_bye_halves']),
            $tieBreaks,
            max(1, self::intOf($row['divisions'])),
            self::intOf($row['promote_count']),
            self::intOf($row['relegate_count']),
            self::intOf($row['season']),
            self::intOrNull($row['previous_season_id']),
            self::intOf($row['current_round']),
            self::intOf($row['created_by']),
            self::timestampOrNull($row['created_at']) ?? 0,
            self::timestampOrNull($row['started_at']),
            self::timestampOrNull($row['finished_at']),
        );
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function pairingFrom(array $row): Pairing
    {
        return new Pairing(
            self::intOf($row['id']),
            self::intOf($row['tournament_id']),
            self::intOf($row['round']),
            self::intOf($row['division']),
            self::intOf($row['board']),
            self::intOf($row['match_number']),
            self::intOf($row['game_number']),
            self::intOf($row['is_armageddon']) === 1,
            self::intOf($row['white_id']),
            self::intOrNull($row['black_id']),
            self::intOrNull($row['game_id']),
            self::textOf($row['state']),
            self::textOf($row['outcome']),
            self::intOf($row['white_halves']),
            self::intOf($row['black_halves']),
            self::timestampOrNull($row['deadline_at']),
        );
    }
}
