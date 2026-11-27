<?php

/**
 * Migration 14: study boards, moves with side lines (C6-1, A121, A132).
 *
 * study_boards: one study: a member's analysis, and later a game review
 * (C6-2), a ladder lesson (C6-3) or a lesson game (C6-4).
 * - owner_id:      the member who made it;
 * - title:         the name shown in the lists;
 * - kind:          'study', 'review', 'ladder' or 'lesson' (StudyBoards::KINDS);
 * - visibility:    'private', 'class' or 'public' (A133);
 * - is_approved:   1 when an admin approved a public board (A133);
 * - source:        where it came from: '' (new), 'game' (an own game), 'study' (a study game), 'pgn' (a paste);
 * - source_id:     that game's id, or 0;
 * - start_fen:     the start position;
 * - result:        "1-0", "0-1", "1/2-1/2" or "*";
 * - tags:          the kept PGN tags, as JSON (White, Black, Event ...);
 * - start_comment, start_drawings: the comment and drawings before the first move.
 *
 * study_moves: the moves of a board, as a tree (MoveTree).
 * - node:          the move's number within its board (1, 2, 3 ...);
 * - parent:        the node it is played after, 0 = the start position;
 * - sibling_order: 0 = the main line at that point, 1, 2 ... = side lines;
 * - san, uci, fen_after: the move and the position after it;
 * - symbols:       the annotation codes, "1,14" ($1 and $14);
 * - comment:       plain text (A131, A135);
 * - drawings:      "Ge2e4,Rd4" ([%cal] and [%csl], A129);
 * - author_id:     who added the move (lesson games: both sides, C6-4), or NULL.
 *
 * New tables only, so everything keeps working before it runs (A54).
 */

declare(strict_types=1);

use PhpChess\Infra\Migration\Migration;
use PhpChess\Infra\Migration\MigrationStep;

$tableOptions = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

return new Migration(14, 'Study boards with side lines, symbols and drawings', [
    new MigrationStep(
        'Create study_boards',
        'CREATE TABLE {study_boards} ('
        . ' id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,'
        . ' owner_id INT UNSIGNED NOT NULL,'
        . ' title VARCHAR(100) NOT NULL,'
        . " kind VARCHAR(10) NOT NULL DEFAULT 'study',"
        . " visibility VARCHAR(10) NOT NULL DEFAULT 'private',"
        . ' is_approved TINYINT(1) NOT NULL DEFAULT 0,'
        . " source VARCHAR(10) NOT NULL DEFAULT '',"
        . ' source_id INT UNSIGNED NOT NULL DEFAULT 0,'
        . ' start_fen VARCHAR(100) NOT NULL,'
        . " result VARCHAR(7) NOT NULL DEFAULT '*',"
        . ' tags TEXT NOT NULL,'
        . ' start_comment TEXT NOT NULL,'
        . ' start_drawings VARCHAR(1000) NOT NULL,'
        . ' created_at DATETIME(3) NOT NULL,'
        . ' updated_at DATETIME(3) NOT NULL,'
        . ' KEY study_boards_owner (owner_id),'
        . ' KEY study_boards_visibility (visibility, is_approved)'
        . ') ' . $tableOptions,
    ),
    new MigrationStep(
        'Create study_moves',
        'CREATE TABLE {study_moves} ('
        . ' board_id INT UNSIGNED NOT NULL,'
        . ' node INT UNSIGNED NOT NULL,'
        . ' parent INT UNSIGNED NOT NULL,'
        . ' sibling_order SMALLINT UNSIGNED NOT NULL,'
        . ' san VARCHAR(10) NOT NULL,'
        . ' uci VARCHAR(5) NOT NULL,'
        . ' fen_after VARCHAR(100) NOT NULL,'
        . ' symbols VARCHAR(30) NOT NULL,'
        . ' comment TEXT NOT NULL,'
        . ' drawings VARCHAR(1000) NOT NULL,'
        . ' author_id INT UNSIGNED NULL,'
        . ' PRIMARY KEY (board_id, node)'
        . ') ' . $tableOptions,
    ),
]);
