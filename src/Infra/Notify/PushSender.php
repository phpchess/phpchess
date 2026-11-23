<?php

declare(strict_types=1);

namespace PhpChess\Infra\Notify;

/**
 * Sends one push message to one subscription (N-03).
 *
 * WebPushSender is the real one, through the web-push library; tests use
 * a sender that only records. The push job never builds a sender while
 * the "push notifications" switch is off, so then no connection is made (K9).
 */
interface PushSender
{
    public const string SENT = 'sent';
    public const string GONE = 'gone';
    public const string FAILED = 'failed';

    /**
     * @param array{endpoint: string, public_key: string, auth_secret: string} $subscription
     * @return string SENT, GONE (the subscription no longer exists) or FAILED (try later)
     */
    public function send(array $subscription, string $payloadJson): string;
}
