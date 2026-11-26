<?php

/**
 * Migration 12: tournaments (C4-1: T-01 to T-08, ADM-06; decisions A97 to A110).
 *
 * - tournaments:            one row per tournament, for every format (A97):
 *                           round_robin, swiss, knockout, league. Settings,
 *                           status and the forfeit rule (A102) live here.
 * - tournament_organisers:  the co-organisers of a tournament (A98).
 * - tournament_players:     who plays, with seed, division (League), state
 *                           (active, withdrawn) and the round they joined.
 * - tournament_rounds:      one row per round: paired, running or closed.
 * - tournament_pairings:    one row per board (or bye) in a round. Every
 *                           played board points to a real game; its score
 *                           is copied from the game's result by
 *                           TournamentResults, called from Games::end (K3).
 * - tournament_absences:    rounds a player said they will miss (A108).
 * - tournament_disputes:    games a player asked the organiser to check (A108).
 * - tournament_log:         every organiser and admin action, shown on the page.
 * - users.can_organise:     members an admin allows to create tournaments (A98).
 * - users.guest_tournament_id: guest logins that belong to one tournament (A109).
 *
 * Scores are counted in half points (a win is 2, a draw 1), so no fractions
 * are stored. Only adds tables and columns, so everything else keeps working
 * before it runs (A54).
 */

declare(strict_types=1);

use PhpChess\Infra\Migration\Migration;
use PhpChess\Infra\Migration\MigrationStep;

$tableOptions = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

return new Migration(12, 'Tournaments', [
    new MigrationStep('Add can_organise and guest_tournament_id to users', '
        ALTER TABLE {users}
            ADD COLUMN can_organise TINYINT(1) NOT NULL DEFAULT 0,
            ADD COLUMN guest_tournament_id INT UNSIGNED NULL'),

    new MigrationStep('Create tournaments', "
        CREATE TABLE IF NOT EXISTS {tournaments} (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            description TEXT NOT NULL,
            format VARCHAR(20) NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'registration',
            is_paused TINYINT(1) NOT NULL DEFAULT 0,
            registration VARCHAR(20) NOT NULL DEFAULT 'open',
            invite_token VARCHAR(64) NULL,
            max_players SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            pace VARCHAR(20) NOT NULL DEFAULT 'correspondence',
            days_per_move SMALLINT UNSIGNED NOT NULL DEFAULT 3,
            time_control VARCHAR(20) NOT NULL DEFAULT '',
            round_days SMALLINT UNSIGNED NOT NULL DEFAULT 14,
            rounds_planned SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            double_round TINYINT(1) NOT NULL DEFAULT 0,
            knockout_games TINYINT UNSIGNED NOT NULL DEFAULT 1,
            third_place TINYINT(1) NOT NULL DEFAULT 0,
            is_rated TINYINT(1) NOT NULL DEFAULT 1,
            allow_computers TINYINT(1) NOT NULL DEFAULT 0,
            confirm_pairings TINYINT(1) NOT NULL DEFAULT 0,
            forfeit_rule VARCHAR(20) NOT NULL DEFAULT 'loss_on_time',
            bye_halves TINYINT UNSIGNED NOT NULL DEFAULT 2,
            absence_bye_halves TINYINT UNSIGNED NOT NULL DEFAULT 1,
            tie_breaks VARCHAR(200) NOT NULL DEFAULT '',
            divisions TINYINT UNSIGNED NOT NULL DEFAULT 1,
            promote_count TINYINT UNSIGNED NOT NULL DEFAULT 2,
            relegate_count TINYINT UNSIGNED NOT NULL DEFAULT 2,
            season SMALLINT UNSIGNED NOT NULL DEFAULT 1,
            previous_season_id INT UNSIGNED NULL,
            current_round SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            created_by INT UNSIGNED NOT NULL,
            created_at DATETIME(3) NOT NULL,
            started_at DATETIME(3) NULL,
            finished_at DATETIME(3) NULL,
            KEY tournaments_by_status (status),
            CONSTRAINT {tournaments_fk_creator} FOREIGN KEY (created_by) REFERENCES {users} (id)
        ) {$tableOptions}"),

    new MigrationStep('Create tournament_organisers', "
        CREATE TABLE IF NOT EXISTS {tournament_organisers} (
            tournament_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            PRIMARY KEY (tournament_id, user_id),
            CONSTRAINT {tournament_organisers_fk_tournament} FOREIGN KEY (tournament_id) REFERENCES {tournaments} (id),
            CONSTRAINT {tournament_organisers_fk_user} FOREIGN KEY (user_id) REFERENCES {users} (id)
        ) {$tableOptions}"),

    new MigrationStep('Create tournament_players', "
        CREATE TABLE IF NOT EXISTS {tournament_players} (
            tournament_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            state VARCHAR(20) NOT NULL DEFAULT 'active',
            seed SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            division TINYINT UNSIGNED NOT NULL DEFAULT 0,
            rating SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            joined_round SMALLINT UNSIGNED NOT NULL DEFAULT 1,
            withdrawn_round SMALLINT UNSIGNED NULL,
            joined_at DATETIME(3) NOT NULL,
            PRIMARY KEY (tournament_id, user_id),
            KEY tournament_players_by_user (user_id),
            CONSTRAINT {tournament_players_fk_tournament} FOREIGN KEY (tournament_id) REFERENCES {tournaments} (id),
            CONSTRAINT {tournament_players_fk_user} FOREIGN KEY (user_id) REFERENCES {users} (id)
        ) {$tableOptions}"),

    new MigrationStep('Create tournament_rounds', "
        CREATE TABLE IF NOT EXISTS {tournament_rounds} (
            tournament_id INT UNSIGNED NOT NULL,
            number SMALLINT UNSIGNED NOT NULL,
            state VARCHAR(20) NOT NULL DEFAULT 'paired',
            started_at DATETIME(3) NULL,
            deadline_at DATETIME(3) NULL,
            closed_at DATETIME(3) NULL,
            PRIMARY KEY (tournament_id, number),
            KEY tournament_rounds_by_deadline (state, deadline_at),
            CONSTRAINT {tournament_rounds_fk_tournament} FOREIGN KEY (tournament_id) REFERENCES {tournaments} (id)
        ) {$tableOptions}"),

    new MigrationStep('Create tournament_pairings', "
        CREATE TABLE IF NOT EXISTS {tournament_pairings} (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            tournament_id INT UNSIGNED NOT NULL,
            round SMALLINT UNSIGNED NOT NULL,
            division TINYINT UNSIGNED NOT NULL DEFAULT 0,
            board SMALLINT UNSIGNED NOT NULL,
            match_number SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            game_number TINYINT UNSIGNED NOT NULL DEFAULT 1,
            is_armageddon TINYINT(1) NOT NULL DEFAULT 0,
            white_id INT UNSIGNED NOT NULL,
            black_id INT UNSIGNED NULL,
            game_id INT UNSIGNED NULL,
            state VARCHAR(20) NOT NULL DEFAULT 'waiting',
            outcome VARCHAR(30) NOT NULL DEFAULT '',
            white_halves TINYINT UNSIGNED NOT NULL DEFAULT 0,
            black_halves TINYINT UNSIGNED NOT NULL DEFAULT 0,
            deadline_at DATETIME(3) NULL,
            KEY tournament_pairings_by_round (tournament_id, round),
            KEY tournament_pairings_by_game (game_id),
            CONSTRAINT {tournament_pairings_fk_tournament} FOREIGN KEY (tournament_id) REFERENCES {tournaments} (id),
            CONSTRAINT {tournament_pairings_fk_white} FOREIGN KEY (white_id) REFERENCES {users} (id),
            CONSTRAINT {tournament_pairings_fk_black} FOREIGN KEY (black_id) REFERENCES {users} (id)
        ) {$tableOptions}"),

    new MigrationStep('Create tournament_absences', "
        CREATE TABLE IF NOT EXISTS {tournament_absences} (
            tournament_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            round SMALLINT UNSIGNED NOT NULL,
            PRIMARY KEY (tournament_id, user_id, round),
            CONSTRAINT {tournament_absences_fk_tournament} FOREIGN KEY (tournament_id) REFERENCES {tournaments} (id)
        ) {$tableOptions}"),

    new MigrationStep('Create tournament_disputes', "
        CREATE TABLE IF NOT EXISTS {tournament_disputes} (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            tournament_id INT UNSIGNED NOT NULL,
            pairing_id INT UNSIGNED NOT NULL,
            raised_by INT UNSIGNED NOT NULL,
            reason TEXT NOT NULL,
            state VARCHAR(20) NOT NULL DEFAULT 'open',
            decision VARCHAR(20) NOT NULL DEFAULT '',
            decision_note TEXT NOT NULL,
            decided_by INT UNSIGNED NULL,
            created_at DATETIME(3) NOT NULL,
            decided_at DATETIME(3) NULL,
            KEY tournament_disputes_by_state (state),
            CONSTRAINT {tournament_disputes_fk_tournament} FOREIGN KEY (tournament_id) REFERENCES {tournaments} (id),
            CONSTRAINT {tournament_disputes_fk_pairing} FOREIGN KEY (pairing_id) REFERENCES {tournament_pairings} (id)
        ) {$tableOptions}"),

    new MigrationStep('Create tournament_log', "
        CREATE TABLE IF NOT EXISTS {tournament_log} (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            tournament_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NULL,
            action VARCHAR(40) NOT NULL,
            details VARCHAR(500) NOT NULL,
            created_at DATETIME(3) NOT NULL,
            KEY tournament_log_by_tournament (tournament_id, id),
            CONSTRAINT {tournament_log_fk_tournament} FOREIGN KEY (tournament_id) REFERENCES {tournaments} (id)
        ) {$tableOptions}"),
]);
