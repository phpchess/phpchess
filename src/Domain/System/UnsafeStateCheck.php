<?php

declare(strict_types=1);

namespace PhpChess\Domain\System;

use PhpChess\Infra\Migration\MigrationRunner;
use PhpChess\Infra\System\JobRepository;
use PhpChess\Infra\System\SettingsRepository;
use PhpChess\Kernel\Config;

/**
 * The unsafe-state check behind the banner on every admin page (INS-07).
 *
 * Lists what is wrong with the install in plain language. C1-1 builds the
 * check; chunk C1-2 shows its warnings as a banner on the admin pages.
 * It lives outside src/Install/ because the installer deletes itself (A23).
 *
 * Settings read:
 * - install.protection_result  the last self-test result ("protected", "unknown")
 * - mail.configured            set by the mail settings page (later chunk)
 */
final class UnsafeStateCheck
{
    private const int JOBS_SILENT_SECONDS = 86400;

    /**
     * The warning when database updates wait. The admin banner shows it with
     * a link to the button that makes the installer link (no expert needed).
     */
    public const string UPDATES_WAITING = 'Database updates are waiting. Step 1: create an installer link (button below). Step 2: open that link; the installer runs the updates.';

    public function __construct(
        private readonly string $appRoot,
        private readonly SettingsRepository $settings,
        private readonly JobRepository $jobRecords,
        private readonly MigrationRunner $migrations,
    ) {
    }

    /**
     * Returns one warning text per problem found; empty when all is well.
     *
     * @return list<string>
     */
    public function warnings(bool $isHttps, int $now): array
    {
        $warnings = [];

        if ($this->settings->getString('install.protection_result') !== 'protected') {
            $warnings[] = 'The private folders (config, storage) may be reachable from the web. Check that the .htaccess files were uploaded.';
        }
        if (is_dir($this->appRoot . '/src/Install') || is_dir($this->appRoot . '/templates/install')) {
            $warnings[] = 'The installer files are still on the server. Delete the folders src/Install and templates/install with your FTP program.';
        }
        if (is_writable(Config::filePath($this->appRoot))) {
            $warnings[] = 'The file config/config.php can be changed by the web server. Make it read-only (644 or 444).';
        }
        $displayErrors = strtolower((string) ini_get('display_errors'));
        if (in_array($displayErrors, ['1', 'on', 'stdout', 'stderr'], true)) {
            $warnings[] = 'PHP shows error messages to visitors (display_errors). Switch it off in your hosting control panel.';
        }
        if (!$isHttps) {
            $warnings[] = 'The site is not using HTTPS, so passwords are sent unprotected.';
        }
        // Only a server that sends email needs it set up (a school may switch email off).
        $needsMail = $this->settings->getBool('switch.' . FeatureSwitches::EMAIL_REQUIREMENT, true);
        if ($needsMail && !$this->settings->getBool('mail.configured', false)) {
            $warnings[] = 'Email is not set up yet, so players get no email notices or password resets.';
        }

        $lastJobRun = $this->jobRecords->lastFinishedAtOfAnyJob();
        if ($lastJobRun === null || $now - $lastJobRun > self::JOBS_SILENT_SECONDS) {
            $warnings[] = 'Background jobs have not run for more than 24 hours. Set up a cron job, or keep pseudo-cron switched on.';
        }

        if ($this->migrations->pendingStepCount() > 0) {
            $warnings[] = self::UPDATES_WAITING;
        }

        return $warnings;
    }
}
