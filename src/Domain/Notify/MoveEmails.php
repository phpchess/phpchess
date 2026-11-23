<?php

declare(strict_types=1);

namespace PhpChess\Domain\Notify;

use PhpChess\Domain\Language\Translator;
use PhpChess\Infra\Identity\PreferenceRepository;
use PhpChess\Infra\Identity\UserRepository;
use PhpChess\Infra\Mail\Email;
use PhpChess\Infra\Mail\MailFailed;
use PhpChess\Infra\Mail\Mailer;
use PhpChess\Infra\Notify\NoticeRepository;
use PhpChess\Infra\System\SettingsRepository;

/**
 * Move and challenge emails for players who are away (N-02, G-10, A34).
 *
 * Run by the MoveEmailJob. Waiting notices (unread, not emailed yet) are
 * emailed as each player chose, among the choices the admin allows:
 * - each_move: as soon as the player is offline;
 * - delay:     when a notice has waited the admin's delay and the player is still offline;
 * - digest:    at most one email a day, with everything waiting;
 * - off:       never; the notices stay in the app only.
 * A notice is marked emailed when it is claimed, so it is never emailed twice,
 * and a notice the player has read in the app is never emailed at all.
 * Each email has one-click links to the games.
 *
 * Admin settings: mail.move_email_allowed (comma list), mail.move_email_default,
 * mail.move_email_delay_minutes.
 */
final class MoveEmails
{
    public const string EACH_MOVE = 'each_move';
    public const string DELAY = 'delay';
    public const string DIGEST = 'digest';
    public const string OFF = 'off';
    public const array MODES = [self::EACH_MOVE, self::DELAY, self::DIGEST, self::OFF];

    /** A player counts as offline when not seen on the site for this long (seconds, ID-12). */
    public const int OFFLINE_AFTER_SECONDS = 300;

    private const int USERS_PER_RUN = 50;

    public function __construct(
        private readonly NoticeRepository $notices,
        private readonly UserRepository $users,
        private readonly PreferenceRepository $preferences,
        private readonly SettingsRepository $settings,
        private readonly Mailer $mailer,
        private readonly Translator $translator,
        private readonly ?NotificationChoices $choices = null,
    ) {
    }

    // --- The admin's choices ---

    /**
     * The modes players may choose from (A34).
     *
     * @return list<string>
     */
    public function allowedModes(): array
    {
        $allowed = array_values(array_intersect(self::MODES, explode(',', $this->settings->getString('mail.move_email_allowed', implode(',', self::MODES)))));

        return $allowed === [] ? [self::OFF] : $allowed;
    }

    public function defaultMode(): string
    {
        $default = $this->settings->getString('mail.move_email_default', self::DELAY);

        return in_array($default, $this->allowedModes(), true) ? $default : $this->allowedModes()[0];
    }

    /** The mode that applies to a player: their own choice if allowed, else the default. */
    public function modeFor(int $userId): string
    {
        $chosen = $this->preferences->moveEmailFor($userId);

        return in_array($chosen, $this->allowedModes(), true) ? $chosen : $this->defaultMode();
    }

    // --- Sending ---

    /** Sends the emails that are due. Returns how many were sent. */
    public function sendDue(int $now, string $siteUrl): int
    {
        $sentCount = 0;
        foreach ($this->notices->usersWithUnemailedNotices($now, self::USERS_PER_RUN) as $userId) {
            $mode = $this->modeFor($userId);
            if ($mode === self::OFF) {
                $this->notices->skipEmail($userId, $now);
                continue;
            }
            if (!$this->isDue($userId, $mode, $now)) {
                continue;
            }
            if ($this->sendTo($userId, $now, $siteUrl)) {
                $sentCount++;
            }
        }

        return $sentCount;
    }

    private function isDue(int $userId, string $mode, int $now): bool
    {
        $lastSeenAt = $this->users->lastSeenAt($userId);
        $isOffline = $lastSeenAt === null || $now - $lastSeenAt >= self::OFFLINE_AFTER_SECONDS;

        if ($mode === self::DIGEST) {
            $lastEmailedAt = $this->notices->lastEmailedAt($userId);

            return $lastEmailedAt === null || $now - $lastEmailedAt >= 86400;
        }
        if (!$isOffline) {
            return false;
        }
        if ($mode === self::DELAY) {
            $oldest = $this->notices->oldestWaitingAt($userId);
            $delaySeconds = max(1, $this->settings->getInt('mail.move_email_delay_minutes', 60)) * 60;

            return $oldest !== null && $now - $oldest >= $delaySeconds;
        }

        return true;
    }

    private function sendTo(int $userId, int $now, string $siteUrl): bool
    {
        $user = $this->users->findById($userId);
        if ($user === null || !$user->isActive()) {
            $this->notices->skipEmail($userId, $now);

            return false;
        }
        $claimed = $this->notices->claimForEmail($userId, $now);
        // Only the kinds this member wants by email (N-04); the rest go by push or not at all.
        if ($this->choices !== null) {
            $claimed = array_values(array_filter(
                $claimed,
                fn (array $notice): bool => $this->choices->wantsEmail($userId, $notice['kind']),
            ));
        }
        if ($claimed === [] || $user->email === '') {
            return false;
        }

        $lines = [];
        foreach ($claimed as $notice) {
            $link = self::linkFor($notice['kind'], $notice['game_id'], $notice['from_username'], $siteUrl);
            $lines[] = '- ' . $this->translator->text('notice.' . $notice['kind'], ['name' => $notice['from_username']]) . ': ' . $link;
        }
        $siteName = $this->settings->getString('site.name', 'phpChess');

        try {
            $this->mailer->send(new Email(
                $user->email,
                $user->username,
                $this->translator->text('email.notices.subject', ['site' => $siteName, 'count' => count($claimed)]),
                $this->translator->text('email.notices.text', [
                    'name' => $user->username,
                    'site' => $siteName,
                    'list' => implode("\n", $lines),
                    'settings' => $siteUrl . '/account/preferences',
                ]),
            ));
        } catch (MailFailed) {
            // The notices stay in the app; mail problems are in the mail log.
            return false;
        }

        return true;
    }

    /** Where a notice leads: its game, the conversation, or the start page (challenges are listed there, A44). */
    public static function linkFor(string $kind, ?int $gameId, string $fromUsername, string $siteUrl): string
    {
        if ($gameId !== null) {
            return $siteUrl . '/games/' . $gameId;
        }
        if ($kind === Notices::MESSAGE_RECEIVED && $fromUsername !== '') {
            return $siteUrl . '/messages/' . rawurlencode($fromUsername);
        }

        return $siteUrl . '/';
    }
}
