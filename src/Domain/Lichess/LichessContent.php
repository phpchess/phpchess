<?php

declare(strict_types=1);

namespace PhpChess\Domain\Lichess;

use PhpChess\Domain\System\FeatureSwitches;
use PhpChess\Infra\System\SettingsRepository;

/**
 * Which kinds of Lichess content the admin turned on (A96).
 *
 * Everything from lichess.org needs the "external content" switch first.
 * Under it, the admin picks on the Lichess page what players see:
 *   live       the games on Lichess TV now, as a list, watched on our board
 *   broadcasts official events now and soon, with their games
 *   daily      Lichess's puzzle of the day
 *   imports    players import their own games, admins import games and studies
 * Each one is on unless the admin turns it off.
 */
final class LichessContent
{
    public const string LIVE = 'live';
    public const string BROADCASTS = 'broadcasts';
    public const string DAILY = 'daily';
    public const string IMPORTS = 'imports';

    public const array KINDS = [self::LIVE, self::BROADCASTS, self::DAILY, self::IMPORTS];

    public function __construct(
        private readonly FeatureSwitches $switches,
        private readonly SettingsRepository $settings,
    ) {
    }

    /** True when "external content" is on and the admin didn't turn this kind off. */
    public function isShown(string $kind): bool
    {
        return $this->switches->isOn(FeatureSwitches::EXTERNAL_CONTENT) && $this->isChosen($kind);
    }

    /** The admin's checkbox for this kind, whatever the main switch says. */
    public function isChosen(string $kind): bool
    {
        return $this->settings->getBool(self::settingName($kind), true);
    }

    public function choose(string $kind, bool $isOn): void
    {
        $this->settings->set(self::settingName($kind), $isOn);
    }

    private static function settingName(string $kind): string
    {
        return 'lichess.show_' . $kind;
    }
}
