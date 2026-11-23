<?php

/**
 * Migration 4: chess play (C2-1): games, moves, challenges, vacations,
 * ratings, notices and presence.
 *
 * - games:          one row per game, for every pace (G-07, A19). The row is
 *                   locked for each move (K2); only Games::end() writes the
 *                   result (K3).
 * - game_moves:     one row per half-move; (game_id, ply) is unique, a second
 *                   guard against a move being stored twice (K2).
 * - challenges:     open, direct and invite-link challenges (G-05, G-21, A35).
 * - vacation_days:  whole days off from correspondence play (G-08, A29).
 * - ratings:        one rating per user and speed (R-01, R-02, A30).
 * - rating_history: one row per (game, user); the unique key makes applying a
 *                   rating twice impossible (K4, R-03).
 * - notices:        in-app notices with the badge count, and the move-email
 *                   queue through emailed_at (N-01, N-02, A34).
 * - users.last_seen_at: presence, kept apart from the account state (ID-12).
 * - user_preferences: the move-email choice and the remembered "Play" defaults (G-20).
 *
 * Times are UTC with milliseconds (K12).
 */

declare(strict_types=1);

use PhpChess\Infra\Migration\Migration;
use PhpChess\Infra\Migration\MigrationStep;

$tableOptions = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

return new Migration(4, 'Play: games, challenges, ratings, notices', [
    new MigrationStep('Add last_seen_at to users', 'ALTER TABLE {users} ADD COLUMN last_seen_at DATETIME(3) NULL'),

    new MigrationStep('Add move_email to user_preferences', "
        ALTER TABLE {user_preferences} ADD COLUMN move_email VARCHAR(20) NOT NULL DEFAULT ''"),
    new MigrationStep('Add play_days_per_move to user_preferences', '
        ALTER TABLE {user_preferences} ADD COLUMN play_days_per_move SMALLINT UNSIGNED NOT NULL DEFAULT 3'),
    new MigrationStep('Add play_rated to user_preferences', '
        ALTER TABLE {user_preferences} ADD COLUMN play_rated TINYINT(1) NOT NULL DEFAULT 1'),

    new MigrationStep('Create games', "
        CREATE TABLE IF NOT EXISTS {games} (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            white_id INT UNSIGNED NOT NULL,
            black_id INT UNSIGNED NOT NULL,
            speed VARCHAR(20) NOT NULL,
            days_per_move SMALLINT UNSIGNED NULL,
            is_rated TINYINT(1) NOT NULL,
            start_fen VARCHAR(100) NOT NULL,
            current_fen VARCHAR(100) NOT NULL,
            ply INT UNSIGNED NOT NULL DEFAULT 0,
            state VARCHAR(20) NOT NULL DEFAULT 'running',
            result VARCHAR(20) NULL,
            end_reason VARCHAR(40) NULL,
            draw_offered_by INT UNSIGNED NULL,
            rematch_offered_by INT UNSIGNED NULL,
            rematch_game_id INT UNSIGNED NULL,
            started_at DATETIME(3) NOT NULL,
            last_move_at DATETIME(3) NULL,
            move_deadline_at DATETIME(3) NULL,
            ended_at DATETIME(3) NULL,
            white_seen_at DATETIME(3) NULL,
            black_seen_at DATETIME(3) NULL,
            KEY games_by_white (white_id, state),
            KEY games_by_black (black_id, state),
            KEY games_by_deadline (state, move_deadline_at),
            CONSTRAINT {games_fk_white} FOREIGN KEY (white_id) REFERENCES {users} (id),
            CONSTRAINT {games_fk_black} FOREIGN KEY (black_id) REFERENCES {users} (id)
        ) {$tableOptions}"),

    new MigrationStep('Create game_moves', "
        CREATE TABLE IF NOT EXISTS {game_moves} (
            game_id INT UNSIGNED NOT NULL,
            ply INT UNSIGNED NOT NULL,
            uci VARCHAR(5) NOT NULL,
            san VARCHAR(10) NOT NULL,
            fen_after VARCHAR(100) NOT NULL,
            played_at DATETIME(3) NOT NULL,
            PRIMARY KEY (game_id, ply),
            CONSTRAINT {game_moves_fk_game} FOREIGN KEY (game_id) REFERENCES {games} (id) ON DELETE CASCADE
        ) {$tableOptions}"),

    new MigrationStep('Create challenges', "
        CREATE TABLE IF NOT EXISTS {challenges} (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            kind VARCHAR(10) NOT NULL,
            challenger_id INT UNSIGNED NOT NULL,
            opponent_id INT UNSIGNED NULL,
            invite_token_hash CHAR(64) NULL,
            colour VARCHAR(10) NOT NULL,
            days_per_move SMALLINT UNSIGNED NOT NULL,
            is_rated TINYINT(1) NOT NULL,
            start_fen VARCHAR(100) NULL,
            state VARCHAR(20) NOT NULL DEFAULT 'waiting',
            game_id INT UNSIGNED NULL,
            created_at DATETIME(3) NOT NULL,
            expires_at DATETIME(3) NOT NULL,
            UNIQUE KEY challenges_invite (invite_token_hash),
            KEY challenges_by_state (state, kind),
            CONSTRAINT {challenges_fk_challenger} FOREIGN KEY (challenger_id) REFERENCES {users} (id) ON DELETE CASCADE,
            CONSTRAINT {challenges_fk_opponent} FOREIGN KEY (opponent_id) REFERENCES {users} (id) ON DELETE CASCADE
        ) {$tableOptions}"),

    new MigrationStep('Create vacation_days', "
        CREATE TABLE IF NOT EXISTS {vacation_days} (
            user_id INT UNSIGNED NOT NULL,
            day DATE NOT NULL,
            PRIMARY KEY (user_id, day),
            CONSTRAINT {vacation_days_fk_user} FOREIGN KEY (user_id) REFERENCES {users} (id) ON DELETE CASCADE
        ) {$tableOptions}"),

    new MigrationStep('Create ratings', "
        CREATE TABLE IF NOT EXISTS {ratings} (
            user_id INT UNSIGNED NOT NULL,
            speed VARCHAR(20) NOT NULL,
            rating INT NOT NULL,
            games_played INT UNSIGNED NOT NULL DEFAULT 0,
            wins INT UNSIGNED NOT NULL DEFAULT 0,
            draws INT UNSIGNED NOT NULL DEFAULT 0,
            losses INT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (user_id, speed),
            KEY ratings_list (speed, rating),
            CONSTRAINT {ratings_fk_user} FOREIGN KEY (user_id) REFERENCES {users} (id) ON DELETE CASCADE
        ) {$tableOptions}"),

    new MigrationStep('Create rating_history', "
        CREATE TABLE IF NOT EXISTS {rating_history} (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            game_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            speed VARCHAR(20) NOT NULL,
            rating_before INT NOT NULL,
            rating_after INT NOT NULL,
            created_at DATETIME(3) NOT NULL,
            UNIQUE KEY rating_history_once (game_id, user_id),
            KEY rating_history_by_user (user_id, speed, id),
            CONSTRAINT {rating_history_fk_game} FOREIGN KEY (game_id) REFERENCES {games} (id) ON DELETE CASCADE,
            CONSTRAINT {rating_history_fk_user} FOREIGN KEY (user_id) REFERENCES {users} (id) ON DELETE CASCADE
        ) {$tableOptions}"),

    new MigrationStep('Create notices', "
        CREATE TABLE IF NOT EXISTS {notices} (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            kind VARCHAR(30) NOT NULL,
            game_id INT UNSIGNED NULL,
            challenge_id INT UNSIGNED NULL,
            from_user_id INT UNSIGNED NULL,
            created_at DATETIME(3) NOT NULL,
            read_at DATETIME(3) NULL,
            emailed_at DATETIME(3) NULL,
            KEY notices_by_user (user_id, read_at),
            KEY notices_for_email (emailed_at, read_at),
            CONSTRAINT {notices_fk_user} FOREIGN KEY (user_id) REFERENCES {users} (id) ON DELETE CASCADE
        ) {$tableOptions}"),
]);
