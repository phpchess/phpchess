<?php

declare(strict_types=1);

namespace PhpChess\Domain\Notify;

/**
 * The browser makers' push services (N-03, K9).
 *
 * A browser that allows push gives us an address at its maker's push
 * service. phpChess accepts and sends to these addresses only, so nobody
 * can make the server send requests to other machines, for example ones
 * inside a school's network (security review C2-2). Checked when a
 * subscription is saved (NotificationsController) and again before each
 * send (WebPushSender).
 */
final class PushServices
{
    /** Allowed hosts; their sub-domains count too (web.push.apple.com). */
    public const array HOSTS = [
        'fcm.googleapis.com',                // Chrome, Edge on Android, Samsung Internet
        'updates.push.services.mozilla.com', // Firefox
        'push.apple.com',                    // Safari, and iPhone apps added to the home screen
        'notify.windows.com',                // Edge on Windows
    ];

    /** True for an https address at one of the push services. */
    public static function isAllowed(string $endpoint): bool
    {
        if (!str_starts_with($endpoint, 'https://') || strlen($endpoint) > 500) {
            return false;
        }
        $host = strtolower((string) parse_url($endpoint, PHP_URL_HOST));
        foreach (self::HOSTS as $allowed) {
            if ($host === $allowed || str_ends_with($host, '.' . $allowed)) {
                return true;
            }
        }

        return false;
    }
}
