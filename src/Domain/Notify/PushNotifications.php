<?php

declare(strict_types=1);

namespace PhpChess\Domain\Notify;

use PhpChess\Domain\Language\Translator;
use PhpChess\Infra\Notify\NoticeRepository;
use PhpChess\Infra\Notify\PushSender;
use PhpChess\Infra\Notify\PushSubscriptionRepository;
use PhpChess\Infra\Play\GameRepository;

/**
 * Turns new notices into push messages (N-03, N-04).
 *
 * For each unread notice not yet pushed:
 *   - the member wants this kind by email or not at all: marked done here;
 *   - it is their quiet time: left waiting, sent when quiet time is over;
 *   - otherwise one message goes to each of their devices.
 * A device the push service says is gone is forgotten. The message holds
 * a title, a line of text, the address to open and the number of games
 * waiting for the member's move (shown as the app badge, UI-05).
 * Notices older than a day are not pushed any more: old news by push is
 * only noise; the email job still covers them.
 */
final class PushNotifications
{
    private const int MAX_AGE_SECONDS = 86400;

    public function __construct(
        private readonly NoticeRepository $notices,
        private readonly PushSubscriptionRepository $subscriptions,
        private readonly NotificationChoices $choices,
        private readonly GameRepository $games,
        private readonly PushSender $sender,
        private readonly Translator $translator,
        private readonly string $siteName,
    ) {
    }

    /** Sends the pushes that are due; returns how many messages were sent. */
    public function sendDue(int $now, string $siteUrl): int
    {
        $sentCount = 0;
        foreach ($this->notices->waitingForPush($now - self::MAX_AGE_SECONDS) as $notice) {
            $userId = $notice['user_id'];
            if ($this->choices->channelFor($userId, $notice['kind']) !== NotificationChoices::PUSH) {
                $this->notices->markPushed($notice['id'], $now);
                continue;
            }
            if ($this->choices->isQuietTime($userId, $now)) {
                continue;
            }

            $payload = $this->payload($notice, $siteUrl);
            foreach ($this->subscriptions->listFor($userId) as $subscription) {
                $result = $this->sender->send($subscription, $payload);
                if ($result === PushSender::SENT) {
                    $sentCount++;
                } elseif ($result === PushSender::GONE) {
                    $this->subscriptions->removeEndpoint($subscription['endpoint']);
                }
            }
            $this->notices->markPushed($notice['id'], $now);
        }

        return $sentCount;
    }

    /**
     * The message as JSON, read by the service worker (templates/pwa/service_worker.php).
     *
     * @param array{id: int, user_id: int, kind: string, game_id: int|null, from_username: string} $notice
     */
    private function payload(array $notice, string $siteUrl): string
    {
        return (string) json_encode([
            'title' => $this->siteName,
            'body' => $this->translator->text('notice.' . $notice['kind'], ['name' => $notice['from_username']]),
            'url' => MoveEmails::linkFor($notice['kind'], $notice['game_id'], $notice['from_username'], $siteUrl),
            'badge' => $this->games->countWaitingFor($notice['user_id']),
            'tag' => 'notice-' . $notice['kind'] . '-' . ($notice['game_id'] ?? 0),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
