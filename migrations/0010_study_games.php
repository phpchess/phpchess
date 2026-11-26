<?php

/**
 * Migration 10: study themes and study games (C5-1, L-03, A78, A88).
 *
 * study_themes: what the admin's collections are about ("Kasparov",
 * "Four Knights", "Capablanca's endgames").
 * - title:      the name members see;
 * - created_by: the admin who made it.
 *
 * study_games: games brought in from Lichess or from an admin's PGN upload,
 * after the content scanner passed them (A81).
 * - theme_id:  the theme it belongs to; NULL for a player's own import,
 *              which only that player sees (A88);
 * - owner_id:  the player who imported it (own imports), or NULL;
 * - white, black, event, played_on, result, opening: from the PGN tags, for the lists;
 * - pgn:       the clean PGN the scanner wrote (allowed tags and moves only);
 * - source:    'lichess' or 'upload';
 * - source_id: the Lichess game id, or '' (an upload), to link back to lichess.org.
 *
 * New tables only, so everything keeps working before it runs (A54).
 */

declare(strict_types=1);

use PhpChess\Infra\Migration\Migration;
use PhpChess\Infra\Migration\MigrationStep;

$tableOptions = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

return new Migration(10, 'Study themes and games from Lichess or uploads', [
    new MigrationStep(
        'Create study_themes',
        'CREATE TABLE {study_themes} ('
        . ' id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,'
        . ' title VARCHAR(100) NOT NULL,'
        . ' created_by INT UNSIGNED NOT NULL,'
        . ' created_at DATETIME(3) NOT NULL'
        . ') ' . $tableOptions,
    ),
    new MigrationStep(
        'Create study_games',
        'CREATE TABLE {study_games} ('
        . ' id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,'
        . ' theme_id INT UNSIGNED NULL,'
        . ' owner_id INT UNSIGNED NULL,'
        . ' white VARCHAR(100) NOT NULL,'
        . ' black VARCHAR(100) NOT NULL,'
        . ' event VARCHAR(100) NOT NULL,'
        . ' played_on VARCHAR(10) NOT NULL,'
        . ' result VARCHAR(7) NOT NULL,'
        . ' opening VARCHAR(100) NOT NULL,'
        . ' pgn MEDIUMTEXT NOT NULL,'
        . ' source VARCHAR(10) NOT NULL,'
        . ' source_id VARCHAR(12) NOT NULL,'
        . ' imported_by INT UNSIGNED NOT NULL,'
        . ' is_kept TINYINT(1) NOT NULL DEFAULT 0,'
        . ' created_at DATETIME(3) NOT NULL,'
        . ' KEY study_games_theme (theme_id),'
        . ' KEY study_games_owner (owner_id)'
        . ') ' . $tableOptions,
    ),
]);
