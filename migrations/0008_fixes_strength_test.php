<?php

/**
 * Migration 8: fixes chunk F1 (B15 strength test, B30 computer move delay).
 *
 * user_preferences:
 * - strength_band:       the band from the strength test (a key of
 *                        Ratings::STRENGTH_BANDS), NULL until a test is done.
 * - strength_tested_at:  when the last test was finished (one test per 30 days, A60).
 * - strength_test_offered: 1 once the pop-up after the first login was shown.
 * - computer_delay:      how long computer players wait before moving
 *                        (none, short, normal, long; A65).
 *
 * Only adds columns, so login and the admin pages keep working before it runs (A54).
 */

declare(strict_types=1);

use PhpChess\Infra\Migration\Migration;
use PhpChess\Infra\Migration\MigrationStep;

return new Migration(8, 'Fixes: strength test and computer delay', [
    new MigrationStep('Add strength_band to user_preferences', 'ALTER TABLE {user_preferences} ADD COLUMN strength_band VARCHAR(20) NULL'),
    new MigrationStep('Add strength_tested_at to user_preferences', 'ALTER TABLE {user_preferences} ADD COLUMN strength_tested_at DATETIME(3) NULL'),
    new MigrationStep('Add strength_test_offered to user_preferences', 'ALTER TABLE {user_preferences} ADD COLUMN strength_test_offered TINYINT(1) NOT NULL DEFAULT 0'),
    new MigrationStep('Add computer_delay to user_preferences', "ALTER TABLE {user_preferences} ADD COLUMN computer_delay VARCHAR(10) NOT NULL DEFAULT 'normal'"),
]);
