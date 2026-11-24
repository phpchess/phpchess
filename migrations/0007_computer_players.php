<?php

/**
 * Migration 7: computer players (C3-2, AI-03, AI-06, decision A53).
 *
 * Each computer player from src/Domain/Computer/computer-players.json gets an
 * ordinary user account the first time someone plays it, so its games,
 * history and PGN need no special cases. Such an account:
 * - has computer_player_id set (the player's id in the JSON file);
 * - has the state "computer", so it can never log in and is left out of
 *   every list of members, ratings and newsletters (they all ask for "active").
 *
 * users:
 * - computer_player_id: the computer player this account plays as, or NULL
 *                       for every human member.
 */

declare(strict_types=1);

use PhpChess\Infra\Migration\Migration;
use PhpChess\Infra\Migration\MigrationStep;

return new Migration(7, 'Computer players', [
    new MigrationStep('Add computer_player_id to users', 'ALTER TABLE {users} ADD COLUMN computer_player_id SMALLINT UNSIGNED NULL'),
    new MigrationStep('One account per computer player', 'ALTER TABLE {users} ADD UNIQUE KEY users_computer_player (computer_player_id)'),
]);
