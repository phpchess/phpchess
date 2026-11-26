<?php

/**
 * Migration 11: study themes are picked from Lichess's sections and topics (A95).
 *
 * study_themes gets two columns:
 * - section: a key of StudyTopics::SECTIONS ("checkmates", "openings" ...);
 *   themes made before this update get '' and are shown as "Other themes";
 * - topic:   a topic of that section ("fork"), or '' where the admin typed
 *   the name (Openings, Famous games); the name stays in "title".
 *
 * Only adds columns, so the admin login and pages keep working before it runs (A54).
 */

declare(strict_types=1);

use PhpChess\Infra\Migration\Migration;
use PhpChess\Infra\Migration\MigrationStep;

return new Migration(11, 'Study themes get Lichess sections and topics', [
    new MigrationStep(
        'Add section and topic to study_themes',
        'ALTER TABLE {study_themes}'
        . ' ADD COLUMN section VARCHAR(30) NOT NULL DEFAULT \'\','
        . ' ADD COLUMN topic VARCHAR(40) NOT NULL DEFAULT \'\'',
    ),
]);
