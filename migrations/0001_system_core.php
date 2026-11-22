<?php

/**
 * Migration 1: the core tables of the System module (INS-05, ARCHITECTURE.md §3, §6).
 *
 * - settings:   typed key/value settings; feature switches are settings too
 * - jobs:       one row per background job: when it last ran and how it went (INS-08)
 * - audit_log:  who did what, for admin actions
 * - rate_limits: hit counters per bucket and time window (API-04)
 *
 * The table "schema_migrations" is created by the migration runner itself.
 * Table names are in braces; the runner adds the install's prefix (K11).
 */

declare(strict_types=1);

use PhpChess\Infra\Migration\Migration;
use PhpChess\Infra\Migration\MigrationStep;

$tableOptions = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

return new Migration(1, 'System core', [
    new MigrationStep('Create settings', "
        CREATE TABLE IF NOT EXISTS {settings} (
            name VARCHAR(100) NOT NULL PRIMARY KEY,
            value TEXT NOT NULL,
            value_type VARCHAR(10) NOT NULL DEFAULT 'string',
            updated_at DATETIME(3) NOT NULL
        ) {$tableOptions}"),

    new MigrationStep('Create jobs', "
        CREATE TABLE IF NOT EXISTS {jobs} (
            name VARCHAR(64) NOT NULL PRIMARY KEY,
            last_started_at DATETIME(3) NULL,
            last_finished_at DATETIME(3) NULL,
            last_result VARCHAR(255) NOT NULL DEFAULT '',
            last_failed TINYINT(1) NOT NULL DEFAULT 0
        ) {$tableOptions}"),

    new MigrationStep('Create audit_log', "
        CREATE TABLE IF NOT EXISTS {audit_log} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            created_at DATETIME(3) NOT NULL,
            user_id INT UNSIGNED NULL,
            action VARCHAR(100) NOT NULL,
            details TEXT NOT NULL,
            ip_address VARCHAR(45) NOT NULL DEFAULT '',
            KEY audit_by_time (created_at)
        ) {$tableOptions}"),

    new MigrationStep('Create rate_limits', "
        CREATE TABLE IF NOT EXISTS {rate_limits} (
            bucket VARCHAR(190) NOT NULL,
            window_start DATETIME NOT NULL,
            hits INT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (bucket, window_start),
            KEY rate_limits_by_window (window_start)
        ) {$tableOptions}"),
]);
