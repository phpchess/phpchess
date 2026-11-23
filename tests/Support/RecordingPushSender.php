<?php

declare(strict_types=1);

namespace PhpChess\Tests\Support;

use PhpChess\Infra\Notify\PushSender;

/**
 * A push sender for tests: keeps every push instead of sending it, so no
 * test ever connects to a push service (K9). "answer" is what each send
 * reports back (SENT, or GONE to test forgotten devices).
 */
final class RecordingPushSender implements PushSender
{
    /** @var list<array{endpoint: string, payload: array<mixed>}> */
    public array $sent = [];

    public string $answer = self::SENT;

    public function send(array $subscription, string $payloadJson): string
    {
        $payload = json_decode($payloadJson, true);
        $this->sent[] = ['endpoint' => $subscription['endpoint'], 'payload' => is_array($payload) ? $payload : []];

        return $this->answer;
    }
}
