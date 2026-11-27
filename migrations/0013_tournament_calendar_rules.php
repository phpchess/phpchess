<?php

/**
 * Migration 13: tournament calendar and rule options (A113 to A115).
 *
 * New columns of tournaments:
 * - starts_at:          the planned start; the round job starts the tournament then (A114);
 * - round_gap_days:     a break between rounds, in playable days;
 * - rest_weekdays:      weekdays without play, "6,7" = Saturday and Sunday (ISO numbers);
 * - blocked_dates:      single days without play, "2026-12-24,2026-12-25";
 * - early_rounds:       1 = the next round starts as soon as the last one ends (A115, default 0);
 * - forfeits_rated:     1 = boards decided at a deadline or by a withdrawal are rated (A113, default 0);
 * - armageddon_higher_black: 1 = in armageddon the higher seed plays Black (default 1);
 * - withdrawal_removes: 1 = an early withdrawal in Round Robin/League removes the results (default 1);
 * - check_in_minutes:   timed tournaments: players check in this long before each round, 0 = no check-in (A117).
 *
 * New tables (A117):
 * - tournament_check_ins:  who checked in for which round;
 * - tournament_reminders:  which tournament notices were sent (round paired, deadline
 *                          in 24 hours, starts in 1 hour), so each goes out once.
 *
 * Only adds columns with defaults and new tables, so everything keeps working before it runs (A54).
 */

declare(strict_types=1);

use PhpChess\Infra\Migration\Migration;
use PhpChess\Infra\Migration\MigrationStep;

return new Migration(13, 'Tournament calendar, rules and check-in', [
    new MigrationStep('Add calendar and rule columns to tournaments', "
        ALTER TABLE {tournaments}
            ADD COLUMN starts_at DATETIME(3) NULL,
            ADD COLUMN round_gap_days SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            ADD COLUMN rest_weekdays VARCHAR(20) NOT NULL DEFAULT '',
            ADD COLUMN blocked_dates VARCHAR(2000) NOT NULL DEFAULT '',
            ADD COLUMN early_rounds TINYINT(1) NOT NULL DEFAULT 0,
            ADD COLUMN forfeits_rated TINYINT(1) NOT NULL DEFAULT 0,
            ADD COLUMN armageddon_higher_black TINYINT(1) NOT NULL DEFAULT 1,
            ADD COLUMN withdrawal_removes TINYINT(1) NOT NULL DEFAULT 1,
            ADD COLUMN check_in_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 0"),

    new MigrationStep('Create tournament_check_ins', "
        CREATE TABLE IF NOT EXISTS {tournament_check_ins} (
            tournament_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            round SMALLINT UNSIGNED NOT NULL,
            created_at DATETIME(3) NOT NULL,
            PRIMARY KEY (tournament_id, user_id, round),
            CONSTRAINT {tournament_check_ins_fk_tournament} FOREIGN KEY (tournament_id) REFERENCES {tournaments} (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"),

    new MigrationStep('Create tournament_reminders', "
        CREATE TABLE IF NOT EXISTS {tournament_reminders} (
            tournament_id INT UNSIGNED NOT NULL,
            kind VARCHAR(30) NOT NULL,
            round SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            sent_at DATETIME(3) NOT NULL,
            PRIMARY KEY (tournament_id, kind, round),
            CONSTRAINT {tournament_reminders_fk_tournament} FOREIGN KEY (tournament_id) REFERENCES {tournaments} (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"),
]);
