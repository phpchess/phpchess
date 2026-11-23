<?php

declare(strict_types=1);

namespace PhpChess\Infra\Jobs;

use Closure;
use PhpChess\Domain\Notify\PushNotifications;
use PhpChess\Domain\System\FeatureSwitches;
use PhpChess\Infra\System\SettingsRepository;

/**
 * The push job (N-03): sends the push messages that are due, every minute.
 *
 * With the "push notifications" switch off it stops at once: the push
 * service is not even built, so the server makes no outgoing connection
 * (K9, C2-2 test). That is why it gets a function that builds
 * PushNotifications, rather than the object itself.
 */
final class PushJob implements Job
{
    /**
     * @param Closure(): PushNotifications $buildPushNotifications
     */
    public function __construct(
        private readonly Closure $buildPushNotifications,
        private readonly FeatureSwitches $switches,
        private readonly SettingsRepository $settings,
        private readonly ?int $fixedNow = null,
    ) {
    }

    public function name(): string
    {
        return 'push';
    }

    public function intervalSeconds(): int
    {
        return 60;
    }

    public function run(): string
    {
        if (!$this->switches->isOn(FeatureSwitches::PUSH_NOTIFICATIONS)) {
            return 'push is switched off';
        }
        $pushNotifications = ($this->buildPushNotifications)();
        $sentCount = $pushNotifications->sendDue($this->fixedNow ?? time(), $this->settings->getString('site.base_url'));

        return "{$sentCount} pushes sent";
    }
}
