<?php

declare(strict_types=1);

namespace PhpChess\Domain\Notify;

use DateTimeImmutable;
use DateTimeZone;
use Exception;
use PhpChess\Infra\Identity\PreferenceRepository;
use PhpChess\Infra\Notify\PushSubscriptionRepository;

/**
 * How each member wants to be told (N-04): per kind push, email or none,
 * and quiet hours for push.
 *
 * Notices fall into three groups, each with its own choice:
 *   games       your move, game ended, draw, rematch and abort offers;
 *   challenges  a challenge for you, your challenge accepted;
 *   messages    new direct messages.
 * The push job and the email job both ask channelFor(). Quiet hours are
 * whole hours in the member's own time zone; "22 to 7" wraps past
 * midnight. During quiet hours push waits; nothing is lost.
 */
final class NotificationChoices
{
    public const string PUSH = 'push';
    public const string EMAIL = 'email';
    public const string NONE = 'none';
    public const array CHANNELS = [self::PUSH, self::EMAIL, self::NONE];

    public const array GROUPS = ['games', 'challenges', 'messages'];

    public function __construct(
        private readonly PreferenceRepository $preferences,
        private readonly PushSubscriptionRepository $subscriptions,
        private readonly bool $pushIsOn,
    ) {
    }

    /**
     * True when this notice should go by email: the member chose email, or
     * chose push but push can't reach them (switch off, or no device
     * subscribed), so they are not left without news.
     */
    public function wantsEmail(int $userId, string $noticeKind): bool
    {
        $channel = $this->channelFor($userId, $noticeKind);
        if ($channel === self::EMAIL) {
            return true;
        }

        return $channel === self::PUSH && !$this->canPush($userId);
    }

    /** True when push is on for the site and the member has at least one device subscribed. */
    public function canPush(int $userId): bool
    {
        return $this->pushIsOn && $this->subscriptions->listFor($userId) !== [];
    }

    /** The group a notice kind belongs to. */
    public static function groupOf(string $noticeKind): string
    {
        return match ($noticeKind) {
            Notices::CHALLENGE_RECEIVED, Notices::CHALLENGE_ACCEPTED => 'challenges',
            Notices::MESSAGE_RECEIVED => 'messages',
            default => 'games',
        };
    }

    /** push, email or none: how this member wants to hear about this kind of notice. */
    public function channelFor(int $userId, string $noticeKind): string
    {
        $choices = $this->preferences->notificationChoices($userId);
        $channel = $choices[self::groupOf($noticeKind)];

        return in_array($channel, self::CHANNELS, true) ? $channel : self::EMAIL;
    }

    /** True when it is quiet time for this member at $now. */
    public function isQuietTime(int $userId, int $now): bool
    {
        $choices = $this->preferences->notificationChoices($userId);

        return self::isQuietHour($choices['quiet_from'], $choices['quiet_until'], $choices['time_zone'], $now);
    }

    /** The rule itself, with plain values, so it is easy to test. */
    public static function isQuietHour(?int $from, ?int $until, string $timeZone, int $now): bool
    {
        if ($from === null || $until === null || $from === $until) {
            return false;
        }
        try {
            $zone = new DateTimeZone($timeZone);
        } catch (Exception) {
            $zone = new DateTimeZone('UTC');
        }
        $hour = (int) (new DateTimeImmutable('@' . $now))->setTimezone($zone)->format('G');

        // "8 to 12" is 8, 9, 10, 11; "22 to 7" wraps past midnight.
        return $from < $until ? $hour >= $from && $hour < $until : $hour >= $from || $hour < $until;
    }
}
