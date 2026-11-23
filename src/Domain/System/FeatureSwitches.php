<?php

declare(strict_types=1);

namespace PhpChess\Domain\System;

use PhpChess\Infra\System\SettingsRepository;

/**
 * The feature switches (SCH-01, contract K8).
 *
 * Every switch from FEATURES.md is listed here once, with its plain-language
 * label and its default. A switch is stored as the setting "switch.<name>".
 * A switched-off feature answers 404 to page and API alike: routes name
 * their switch in Routes.php and the Kernel refuses them (K8). Hiding a link
 * in the menu is only extra.
 *
 * The features behind most switches arrive in later chunks; the switches
 * exist now, so the admin page and the rule are in place from the start.
 */
final class FeatureSwitches
{
    public const string PUBLIC_REGISTRATION = 'public_registration';
    public const string EMAIL_REQUIREMENT = 'email_requirement';
    public const string PLAYER_LIST_VISIBILITY = 'player_list_visibility';
    public const string SCHOOL_MODE = 'school_mode';
    public const string GAME_CHAT = 'game_chat';
    public const string DIRECT_MESSAGES = 'direct_messages';
    public const string PUBLIC_PROFILES = 'public_profiles';
    public const string FILE_UPLOADS = 'file_uploads';
    public const string PUSH_NOTIFICATIONS = 'push_notifications';
    public const string EXTERNAL_CONTENT = 'external_content';

    /**
     * Every switch: name => on by default. Its label and explanation are in
     * the language file as "switch.<name>.label" and "switch.<name>.explain".
     * The two outside-request switches (K9) are off by default.
     */
    public const array ALL = [
        self::PUBLIC_REGISTRATION => true,
        self::EMAIL_REQUIREMENT => true,
        self::PLAYER_LIST_VISIBILITY => true,
        self::SCHOOL_MODE => false,
        self::GAME_CHAT => true,
        self::DIRECT_MESSAGES => true,
        self::PUBLIC_PROFILES => false,
        self::FILE_UPLOADS => true,
        self::PUSH_NOTIFICATIONS => false,
        self::EXTERNAL_CONTENT => false,
    ];

    public function __construct(private readonly SettingsRepository $settings)
    {
    }

    /** True when the switch is on. An unknown name counts as off. */
    public function isOn(string $name): bool
    {
        if (!isset(self::ALL[$name])) {
            return false;
        }

        return $this->settings->getBool('switch.' . $name, self::ALL[$name]);
    }

    /** Switches one feature on or off. */
    public function set(string $name, bool $isOn): void
    {
        if (isset(self::ALL[$name])) {
            $this->settings->set('switch.' . $name, $isOn);
        }
    }
}
