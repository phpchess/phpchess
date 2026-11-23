<?php

/**
 * Migration 6: timed play (C3-1): clocks, premoves, changing pace, the lobby.
 *
 * games (one game model for every pace, G-07):
 * - time_control:      "5+3" for a timed game, NULL for correspondence (G-14).
 * - increment_ms:      seconds added per move, in milliseconds.
 * - white_ms_left, black_ms_left: each clock at the start of the current turn.
 * - turn_saved_ms:     when the current turn began (the last move was saved), Unix ms.
 * - turn_delivered_ms: when the player to move first received that move (lag compensation, K12).
 * - premove_by, premove_uci, premove_ply: one waiting premove (G-17).
 * - pace_offered_by, pace_offer: a proposal to change the pace (G-23):
 *                      a time control "10+5" or days per move "3d".
 * challenges:
 * - time_control:      "5+3" for a timed challenge, NULL for correspondence.
 * - seen_at:           when the poster's Play page last asked for news; open
 *                      timed challenges show only while that is recent (G-16).
 *
 * Times are UTC (K12).
 */

declare(strict_types=1);

use PhpChess\Infra\Migration\Migration;
use PhpChess\Infra\Migration\MigrationStep;

return new Migration(6, 'Timed play: clocks, premoves, pace changes', [
    // --- The clock (G-14, G-15) ---
    new MigrationStep('Add time_control to games', 'ALTER TABLE {games} ADD COLUMN time_control VARCHAR(10) NULL'),
    new MigrationStep('Add increment_ms to games', 'ALTER TABLE {games} ADD COLUMN increment_ms INT UNSIGNED NULL'),
    new MigrationStep('Add white_ms_left to games', 'ALTER TABLE {games} ADD COLUMN white_ms_left INT NULL'),
    new MigrationStep('Add black_ms_left to games', 'ALTER TABLE {games} ADD COLUMN black_ms_left INT NULL'),
    new MigrationStep('Add turn_saved_ms to games', 'ALTER TABLE {games} ADD COLUMN turn_saved_ms BIGINT NULL'),
    new MigrationStep('Add turn_delivered_ms to games', 'ALTER TABLE {games} ADD COLUMN turn_delivered_ms BIGINT NULL'),
    new MigrationStep('Index running timed games', 'ALTER TABLE {games} ADD KEY games_timed (state, time_control)'),

    // --- Premoves (G-17) ---
    new MigrationStep('Add premove_by to games', 'ALTER TABLE {games} ADD COLUMN premove_by INT UNSIGNED NULL'),
    new MigrationStep('Add premove_uci to games', 'ALTER TABLE {games} ADD COLUMN premove_uci VARCHAR(5) NULL'),
    new MigrationStep('Add premove_ply to games', 'ALTER TABLE {games} ADD COLUMN premove_ply INT UNSIGNED NULL'),

    // --- Changing pace (G-23) ---
    new MigrationStep('Add pace_offered_by to games', 'ALTER TABLE {games} ADD COLUMN pace_offered_by INT UNSIGNED NULL'),
    new MigrationStep('Add pace_offer to games', 'ALTER TABLE {games} ADD COLUMN pace_offer VARCHAR(10) NULL'),

    // --- The lobby (G-16) ---
    new MigrationStep('Add time_control to challenges', 'ALTER TABLE {challenges} ADD COLUMN time_control VARCHAR(10) NULL'),
    new MigrationStep('Add seen_at to challenges', 'ALTER TABLE {challenges} ADD COLUMN seen_at DATETIME(3) NULL'),
]);
