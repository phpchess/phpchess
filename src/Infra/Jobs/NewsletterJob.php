<?php

declare(strict_types=1);

namespace PhpChess\Infra\Jobs;

use PhpChess\Domain\System\FeatureSwitches;
use PhpChess\Infra\Content\NewsletterRepository;
use PhpChess\Infra\Mail\Email;
use PhpChess\Infra\Mail\MailFailed;
use PhpChess\Infra\Mail\Mailer;
use PhpChess\Infra\System\SettingsRepository;

/**
 * The newsletter job (ADM-07, A41): sends a few queued newsletter emails
 * each run, so a big list never floods the mail server or the host's
 * sending limit. "newsletter.per_run" (default 50) is set by the admin;
 * the job runs every 5 minutes.
 */
final class NewsletterJob implements Job
{
    public const int DEFAULT_PER_RUN = 50;

    public function __construct(
        private readonly NewsletterRepository $newsletters,
        private readonly Mailer $mailer,
        private readonly FeatureSwitches $switches,
        private readonly SettingsRepository $settings,
        private readonly ?int $fixedNow = null,
    ) {
    }

    public function name(): string
    {
        return 'newsletter';
    }

    public function intervalSeconds(): int
    {
        return 300;
    }

    public function run(): string
    {
        if (!$this->switches->isOn(FeatureSwitches::EMAIL_REQUIREMENT)) {
            return 'email is switched off';
        }
        $perRun = max(1, $this->settings->getInt('newsletter.per_run', self::DEFAULT_PER_RUN));
        $now = $this->fixedNow ?? time();
        $sentCount = 0;
        foreach ($this->newsletters->nextToSend($perRun) as $letter) {
            // Marked first: a failed send is not retried, so nobody gets it twice.
            $this->newsletters->markSent($letter['newsletter_id'], $letter['user_id'], $now);
            try {
                $this->mailer->send(new Email($letter['email'], '', $letter['subject'], $letter['body']));
                $sentCount++;
            } catch (MailFailed) {
                continue;
            }
        }

        return "{$sentCount} newsletter emails sent";
    }
}
