<?php

declare(strict_types=1);

namespace PhpChess\Infra\Jobs;

use PhpChess\Domain\Notify\MoveEmails;
use PhpChess\Domain\System\FeatureSwitches;
use PhpChess\Infra\System\SettingsRepository;

/**
 * The move-email job: sends the move and challenge emails that are due
 * (N-02, A34). The rules are in MoveEmails.
 *
 * Runs every minute. Without email (the "email requirement" switch off),
 * nothing is sent and the notices stay in the app. The links in the
 * emails use the site address the Kernel last saw (setting site.base_url),
 * because a cron run has no web request to take it from.
 */
final class MoveEmailJob implements Job
{
    public function __construct(
        private readonly MoveEmails $moveEmails,
        private readonly FeatureSwitches $switches,
        private readonly SettingsRepository $settings,
        private readonly ?int $fixedNow = null,
    ) {
    }

    public function name(): string
    {
        return 'move_emails';
    }

    public function intervalSeconds(): int
    {
        return 60;
    }

    public function run(): string
    {
        if (!$this->switches->isOn(FeatureSwitches::EMAIL_REQUIREMENT)) {
            return 'email is switched off';
        }
        $sentCount = $this->moveEmails->sendDue($this->fixedNow ?? time(), $this->settings->getString('site.base_url'));

        return "{$sentCount} emails sent";
    }
}
