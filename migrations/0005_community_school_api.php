<?php

/**
 * Migration 5: community, school mode and clients (C2-2).
 *
 * - users.email may be empty (NULL): students made by a teacher need no
 *   email (SCH-03). A UNIQUE key allows many NULLs.
 * - profiles:              avatar and a short text about the player (C-01, C-02).
 * - buddies:               one row per (user, buddy) (C-03).
 * - messages:              direct messages between two members (C-04).
 * - game_chat:             chat lines in a game (G-12).
 * - content_items:         news, server messages, tips, FAQ entries and pages (C-06 to C-09, ADM-05).
 * - newsletters and newsletter_recipients: the newsletter and its sending queue (ADM-07).
 * - school_classes and school_class_members: classes and their members (SCH-02, SCH-05).
 * - api_tokens:            access tokens per device, revocable (ID-10).
 * - push_subscriptions:    one row per browser or phone that allowed push (N-03).
 * - notices.pushed_at:     the push queue, like emailed_at for email (N-03).
 * - user_preferences:      the per-kind choice (push, email, none), quiet hours and time zone (N-04).
 *
 * Times are UTC with milliseconds (K12).
 */

declare(strict_types=1);

use PhpChess\Infra\Migration\Migration;
use PhpChess\Infra\Migration\MigrationStep;

$tableOptions = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

return new Migration(5, 'Community, school mode, API tokens and push', [
    // --- Accounts without email (SCH-03) ---
    new MigrationStep('Allow users without email', 'ALTER TABLE {users} MODIFY email VARCHAR(190) NULL'),

    // --- Profiles and avatars (C-01, C-02) ---
    new MigrationStep('Create profiles', "
        CREATE TABLE IF NOT EXISTS {profiles} (
            user_id INT UNSIGNED NOT NULL PRIMARY KEY,
            avatar VARCHAR(60) NOT NULL DEFAULT '',
            about VARCHAR(1000) NOT NULL DEFAULT '',
            updated_at DATETIME(3) NOT NULL,
            CONSTRAINT {profiles_fk_user} FOREIGN KEY (user_id) REFERENCES {users} (id) ON DELETE CASCADE
        ) {$tableOptions}"),

    // --- Buddies (C-03) ---
    new MigrationStep('Create buddies', "
        CREATE TABLE IF NOT EXISTS {buddies} (
            user_id INT UNSIGNED NOT NULL,
            buddy_id INT UNSIGNED NOT NULL,
            created_at DATETIME(3) NOT NULL,
            PRIMARY KEY (user_id, buddy_id),
            CONSTRAINT {buddies_fk_user} FOREIGN KEY (user_id) REFERENCES {users} (id) ON DELETE CASCADE,
            CONSTRAINT {buddies_fk_buddy} FOREIGN KEY (buddy_id) REFERENCES {users} (id) ON DELETE CASCADE
        ) {$tableOptions}"),

    // --- Direct messages (C-04) ---
    new MigrationStep('Create messages', "
        CREATE TABLE IF NOT EXISTS {messages} (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            from_user_id INT UNSIGNED NOT NULL,
            to_user_id INT UNSIGNED NOT NULL,
            body VARCHAR(2000) NOT NULL,
            created_at DATETIME(3) NOT NULL,
            read_at DATETIME(3) NULL,
            KEY messages_by_recipient (to_user_id, read_at),
            KEY messages_by_pair (from_user_id, to_user_id, id),
            KEY messages_by_time (created_at),
            CONSTRAINT {messages_fk_from} FOREIGN KEY (from_user_id) REFERENCES {users} (id) ON DELETE CASCADE,
            CONSTRAINT {messages_fk_to} FOREIGN KEY (to_user_id) REFERENCES {users} (id) ON DELETE CASCADE
        ) {$tableOptions}"),

    // --- Game chat (G-12) ---
    new MigrationStep('Create game_chat', "
        CREATE TABLE IF NOT EXISTS {game_chat} (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            game_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            body VARCHAR(500) NOT NULL,
            created_at DATETIME(3) NOT NULL,
            KEY game_chat_by_game (game_id, id),
            KEY game_chat_by_time (created_at),
            CONSTRAINT {game_chat_fk_user} FOREIGN KEY (user_id) REFERENCES {users} (id) ON DELETE CASCADE
        ) {$tableOptions}"),

    // --- Content (C-06 to C-09, ADM-05) ---
    new MigrationStep('Create content_items', "
        CREATE TABLE IF NOT EXISTS {content_items} (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            kind VARCHAR(20) NOT NULL,
            slug VARCHAR(60) NOT NULL DEFAULT '',
            title VARCHAR(200) NOT NULL,
            body TEXT NOT NULL,
            is_published TINYINT(1) NOT NULL DEFAULT 1,
            sort_order INT NOT NULL DEFAULT 0,
            created_at DATETIME(3) NOT NULL,
            updated_at DATETIME(3) NOT NULL,
            KEY content_by_kind (kind, is_published, sort_order)
        ) {$tableOptions}"),

    // --- Newsletter (ADM-07) ---
    new MigrationStep('Create newsletters', "
        CREATE TABLE IF NOT EXISTS {newsletters} (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            subject VARCHAR(200) NOT NULL,
            body TEXT NOT NULL,
            created_by INT UNSIGNED NULL,
            created_at DATETIME(3) NOT NULL
        ) {$tableOptions}"),
    new MigrationStep('Create newsletter_recipients', "
        CREATE TABLE IF NOT EXISTS {newsletter_recipients} (
            newsletter_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            sent_at DATETIME(3) NULL,
            PRIMARY KEY (newsletter_id, user_id),
            KEY newsletter_waiting (sent_at),
            CONSTRAINT {newsletter_recipients_fk_letter} FOREIGN KEY (newsletter_id) REFERENCES {newsletters} (id) ON DELETE CASCADE,
            CONSTRAINT {newsletter_recipients_fk_user} FOREIGN KEY (user_id) REFERENCES {users} (id) ON DELETE CASCADE
        ) {$tableOptions}"),

    // --- School mode (SCH-02 to SCH-06) ---
    new MigrationStep('Create school_classes', "
        CREATE TABLE IF NOT EXISTS {school_classes} (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(80) NOT NULL,
            teacher_id INT UNSIGNED NOT NULL,
            limit_to_class TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME(3) NOT NULL,
            KEY school_classes_by_teacher (teacher_id),
            CONSTRAINT {school_classes_fk_teacher} FOREIGN KEY (teacher_id) REFERENCES {users} (id)
        ) {$tableOptions}"),
    new MigrationStep('Create school_class_members', "
        CREATE TABLE IF NOT EXISTS {school_class_members} (
            class_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            PRIMARY KEY (class_id, user_id),
            KEY school_members_by_user (user_id),
            CONSTRAINT {school_members_fk_class} FOREIGN KEY (class_id) REFERENCES {school_classes} (id) ON DELETE CASCADE,
            CONSTRAINT {school_members_fk_user} FOREIGN KEY (user_id) REFERENCES {users} (id) ON DELETE CASCADE
        ) {$tableOptions}"),

    // --- API tokens (ID-10) ---
    new MigrationStep('Create api_tokens', "
        CREATE TABLE IF NOT EXISTS {api_tokens} (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            device_name VARCHAR(80) NOT NULL,
            token_hash CHAR(64) NOT NULL,
            created_at DATETIME(3) NOT NULL,
            last_used_at DATETIME(3) NULL,
            UNIQUE KEY api_tokens_hash (token_hash),
            KEY api_tokens_by_user (user_id),
            CONSTRAINT {api_tokens_fk_user} FOREIGN KEY (user_id) REFERENCES {users} (id) ON DELETE CASCADE
        ) {$tableOptions}"),

    // --- Web Push (N-03, N-04) ---
    new MigrationStep('Create push_subscriptions', "
        CREATE TABLE IF NOT EXISTS {push_subscriptions} (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            endpoint VARCHAR(500) NOT NULL,
            public_key VARCHAR(200) NOT NULL,
            auth_secret VARCHAR(100) NOT NULL,
            created_at DATETIME(3) NOT NULL,
            UNIQUE KEY push_by_endpoint (endpoint(250)),
            KEY push_by_user (user_id),
            CONSTRAINT {push_subscriptions_fk_user} FOREIGN KEY (user_id) REFERENCES {users} (id) ON DELETE CASCADE
        ) {$tableOptions}"),
    new MigrationStep('Add pushed_at to notices', 'ALTER TABLE {notices} ADD COLUMN pushed_at DATETIME(3) NULL'),
    new MigrationStep('Add notify_games to user_preferences', "
        ALTER TABLE {user_preferences} ADD COLUMN notify_games VARCHAR(10) NOT NULL DEFAULT 'email'"),
    new MigrationStep('Add notify_challenges to user_preferences', "
        ALTER TABLE {user_preferences} ADD COLUMN notify_challenges VARCHAR(10) NOT NULL DEFAULT 'email'"),
    new MigrationStep('Add notify_messages to user_preferences', "
        ALTER TABLE {user_preferences} ADD COLUMN notify_messages VARCHAR(10) NOT NULL DEFAULT 'email'"),
    new MigrationStep('Add quiet_from to user_preferences', 'ALTER TABLE {user_preferences} ADD COLUMN quiet_from TINYINT UNSIGNED NULL'),
    new MigrationStep('Add quiet_until to user_preferences', 'ALTER TABLE {user_preferences} ADD COLUMN quiet_until TINYINT UNSIGNED NULL'),
    new MigrationStep('Add time_zone to user_preferences', "
        ALTER TABLE {user_preferences} ADD COLUMN time_zone VARCHAR(60) NOT NULL DEFAULT 'UTC'"),
]);
