<?php

declare(strict_types=1);

namespace PhpChess\Infra\Jobs;

use PhpChess\Domain\System\Archive;
use PhpChess\Infra\Identity\UserRepository;
use PhpChess\Infra\Mail\Email;
use PhpChess\Infra\Mail\MailFailed;
use PhpChess\Infra\Mail\Mailer;
use PhpChess\Infra\System\SettingsRepository;

/**
 * The daily performance check (A42, A43).
 *
 * Times the typical reads (Archive::check). When they are slower than the
 * admin's cut-off, the admin dashboard shows the suggestion, and the active
 * admins get an email about it, at most once a week, so nobody is flooded.
 */
final class PerformanceCheckJob implements Job
{
    private const int EMAIL_EVERY_SECONDS = 7 * 86400;

    public function __construct(
        private readonly Archive $archive,
        private readonly UserRepository $users,
        private readonly Mailer $mailer,
        private readonly SettingsRepository $settings,
        private readonly ?int $fixedNow = null,
    ) {
    }

    public function name(): string
    {
        return 'performance_check';
    }

    public function intervalSeconds(): int
    {
        return 86400;
    }

    public function run(): string
    {
        $now = $this->fixedNow ?? time();
        $result = $this->archive->check($now);
        if (!$result['too_slow']) {
            return "slowest read {$result['slowest_ms']} ms, fine";
        }
        if ($now - $this->settings->getInt('archive.emailed_at') >= self::EMAIL_EVERY_SECONDS) {
            $this->settings->set('archive.emailed_at', $now);
            $this->emailAdmins($result['slowest_ms'], $result['cutoff_ms']);
        }

        return "slowest read {$result['slowest_ms']} ms, archive suggested";
    }

    private function emailAdmins(int $slowestMs, int $cutoffMs): void
    {
        $siteName = $this->settings->getString('site.name', 'phpChess');
        $link = $this->settings->getString('site.base_url') . '/admin/archive';
        foreach ($this->users->activeAdminIds() as $adminId) {
            $admin = $this->users->findById($adminId);
            if ($admin === null || $admin->email === '') {
                continue;
            }
            try {
                $this->mailer->send(new Email(
                    $admin->email,
                    $admin->username,
                    "{$siteName}: the database is getting slow",
                    "Hello {$admin->username},\n\nThe daily check found a typical read taking {$slowestMs} ms; the limit is {$cutoffMs} ms.\n"
                    . "Writing an archive of old games and messages to a file, and keeping it on your computer, will make the site fast again. Nothing is lost: the file can be read back in.\n\n"
                    . "Open the archive page: {$link}\n",
                ));
            } catch (MailFailed) {
                continue;
            }
        }
    }
}
