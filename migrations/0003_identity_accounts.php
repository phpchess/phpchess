<?php

/**
 * Migration 3: accounts of the Identity module (ID-01 to ID-09, ID-13).
 *
 * - users:            gains verification, approval and login times
 * - user_tokens:      one-time tokens (email verification, password reset)
 *                     and remember-me tokens; only their hashes are stored
 * - login_attempts:   every login try, for the admin and for throttling (ID-04)
 * - user_preferences: language, colour scheme, board and pieces (ID-13)
 *
 * Account states are one field, users.state (ID-07): pending, active,
 * disabled, archived. Roles are users.role (ID-08): admin, teacher, student, player.
 */

declare(strict_types=1);

use PhpChess\Infra\Migration\Migration;
use PhpChess\Infra\Migration\MigrationStep;

$tableOptions = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

return new Migration(3, 'Identity: accounts', [
    new MigrationStep('Add email_verified_at to users', 'ALTER TABLE {users} ADD COLUMN email_verified_at DATETIME(3) NULL'),
    new MigrationStep('Add approved_at to users', 'ALTER TABLE {users} ADD COLUMN approved_at DATETIME(3) NULL'),
    new MigrationStep('Add last_login_at to users', 'ALTER TABLE {users} ADD COLUMN last_login_at DATETIME(3) NULL'),
    new MigrationStep('Add updated_at to users', 'ALTER TABLE {users} ADD COLUMN updated_at DATETIME(3) NULL'),

    new MigrationStep('Members are players, not "member"', "
        UPDATE {users} SET role = 'player' WHERE role = 'member'"),

    new MigrationStep('Admins made by the installer count as verified and approved', "
        UPDATE {users} SET email_verified_at = created_at, approved_at = created_at
        WHERE role = 'admin' AND approved_at IS NULL"),

    new MigrationStep('Create user_tokens', "
        CREATE TABLE IF NOT EXISTS {user_tokens} (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            token_type VARCHAR(20) NOT NULL,
            token_hash CHAR(64) NOT NULL,
            created_at DATETIME(3) NOT NULL,
            expires_at DATETIME(3) NOT NULL,
            used_at DATETIME(3) NULL,
            UNIQUE KEY user_tokens_hash (token_hash),
            KEY user_tokens_by_user (user_id, token_type),
            CONSTRAINT user_tokens_user FOREIGN KEY (user_id) REFERENCES {users} (id) ON DELETE CASCADE
        ) {$tableOptions}"),

    new MigrationStep('Create login_attempts', "
        CREATE TABLE IF NOT EXISTS {login_attempts} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            attempted_at DATETIME(3) NOT NULL,
            username_tried VARCHAR(190) NOT NULL,
            user_id INT UNSIGNED NULL,
            ip_address VARCHAR(45) NOT NULL,
            succeeded TINYINT(1) NOT NULL,
            KEY login_attempts_by_time (attempted_at)
        ) {$tableOptions}"),

    new MigrationStep('Create user_preferences', "
        CREATE TABLE IF NOT EXISTS {user_preferences} (
            user_id INT UNSIGNED NOT NULL PRIMARY KEY,
            language VARCHAR(10) NOT NULL DEFAULT 'en',
            colour_scheme VARCHAR(10) NOT NULL DEFAULT 'auto',
            board_theme VARCHAR(40) NOT NULL DEFAULT 'green',
            piece_set VARCHAR(40) NOT NULL DEFAULT 'standard',
            email_notices TINYINT(1) NOT NULL DEFAULT 1,
            updated_at DATETIME(3) NOT NULL,
            CONSTRAINT user_preferences_user FOREIGN KEY (user_id) REFERENCES {users} (id) ON DELETE CASCADE
        ) {$tableOptions}"),
]);
