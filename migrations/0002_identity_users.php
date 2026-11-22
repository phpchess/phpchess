<?php

/**
 * Migration 2: the first accounts table of the Identity module (INS-02, ARCHITECTURE.md §3).
 *
 * Only what the installer needs to create the admin account (step 4 of §10).
 * Chunk C1-2 (accounts, roles) extends it with its own migrations; it never
 * changes this file.
 */

declare(strict_types=1);

use PhpChess\Infra\Migration\Migration;
use PhpChess\Infra\Migration\MigrationStep;

return new Migration(2, 'Identity: users', [
    new MigrationStep('Create users', "
        CREATE TABLE IF NOT EXISTS {users} (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(40) NOT NULL,
            email VARCHAR(190) NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            role VARCHAR(20) NOT NULL DEFAULT 'member',
            state VARCHAR(20) NOT NULL DEFAULT 'active',
            created_at DATETIME(3) NOT NULL,
            UNIQUE KEY users_username (username),
            UNIQUE KEY users_email (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"),
]);
