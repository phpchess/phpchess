<?php

/**
 * Migration 9: the computer keeps the pace in correspondence games (F2 review, A86).
 *
 * computer_held_moves: the computer's reply, decided in the player's browser
 * right after the player's move, but played only later by the job runner
 * (cron or pseudo-cron), so the computer answers like a correspondence player.
 * - game_id:  the game (one held move per game at most);
 * - ply:      the move number the reply is for; a reply for an old ply is dropped;
 * - move_uci: the move, for example "e7e5";
 * - play_at:  when the job runner plays it (UTC, K12).
 *
 * A new table only, so everything keeps working before it runs (A54).
 */

declare(strict_types=1);

use PhpChess\Infra\Migration\Migration;
use PhpChess\Infra\Migration\MigrationStep;

$tableOptions = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

return new Migration(9, 'Computer replies held to the correspondence pace', [
    new MigrationStep(
        'Create computer_held_moves',
        'CREATE TABLE {computer_held_moves} ('
        . ' game_id INT UNSIGNED NOT NULL PRIMARY KEY,'
        . ' ply INT UNSIGNED NOT NULL,'
        . ' move_uci VARCHAR(5) NOT NULL,'
        . ' play_at DATETIME(3) NOT NULL,'
        . ' KEY computer_held_moves_due (play_at)'
        . ') ' . $tableOptions,
    ),
]);
