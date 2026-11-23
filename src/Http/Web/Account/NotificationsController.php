<?php

declare(strict_types=1);

namespace PhpChess\Http\Web\Account;

use DateTimeZone;
use PhpChess\Domain\Notify\NotificationChoices;
use PhpChess\Domain\System\FeatureSwitches;
use PhpChess\Http\Request;
use PhpChess\Http\Response;
use PhpChess\Http\Web\MemberPages;
use PhpChess\Infra\Notify\WebPushSender;
use PhpChess\Kernel\Services;

/**
 * Notification settings (N-03, N-04): per kind push, email or none; quiet
 * hours; and the devices that receive push.
 *
 * Addresses (Routes.php), members only:
 *   GET  /account/notifications              the choices and my push devices
 *   POST /account/notifications              save the choices
 *   POST /account/push/subscribe             a browser that allowed push sends its subscription (switch "push notifications")
 *   POST /account/push/{id}/remove           stop push to one device
 * Push is only ever asked for after a tap on "Turn on push here"
 * (assets/js/app/push.js): browsers refuse to ask by themselves, and it is fair.
 */
final class NotificationsController
{
    public function __construct(
        private readonly Services $services,
        private readonly MemberPages $pages,
    ) {
    }

    public function show(Request $request): Response
    {
        $user = $this->pages->user();
        $pushIsOn = $this->services->switches()->isOn(FeatureSwitches::PUSH_NOTIFICATIONS);

        return $this->pages->render($request, 'account/notifications', 'notifications.title', [
            'choices' => $this->services->preferenceRepository()->notificationChoices($user->id),
            'channels' => NotificationChoices::CHANNELS,
            'groups' => NotificationChoices::GROUPS,
            'pushIsOn' => $pushIsOn,
            'devices' => $pushIsOn ? $this->services->pushSubscriptions()->listFor($user->id) : [],
            'vapidPublicKey' => $pushIsOn ? WebPushSender::publicKey($this->services->settings()) : '',
        ]);
    }

    public function save(Request $request): Response
    {
        $user = $this->pages->user();
        $choices = [];
        foreach (NotificationChoices::GROUPS as $group) {
            $channel = $request->formValue('notify_' . $group);
            $choices[$group] = in_array($channel, NotificationChoices::CHANNELS, true) ? $channel : NotificationChoices::EMAIL;
        }
        $timeZone = $request->formValue('time_zone', 'UTC');
        $choices['time_zone'] = in_array($timeZone, DateTimeZone::listIdentifiers(), true) ? $timeZone : 'UTC';
        $choices['quiet_from'] = self::hourOrNull($request->formValue('quiet_from'));
        $choices['quiet_until'] = self::hourOrNull($request->formValue('quiet_until'));
        $this->services->preferenceRepository()->saveNotificationChoices($user->id, $choices);

        return $this->pages->done($request, '/account/notifications', 'notifications.saved');
    }

    /** Saves the subscription a browser sends after the member allowed push (JSON answer). */
    public function subscribe(Request $request): Response
    {
        $endpoint = $request->formValue('endpoint');
        $publicKey = $request->formValue('p256dh');
        $authSecret = $request->formValue('auth');
        $isHttpsEndpoint = str_starts_with($endpoint, 'https://') && strlen($endpoint) <= 500;
        $keysLookRight = preg_match('/^[A-Za-z0-9_-]{20,200}={0,2}$/', $publicKey) === 1 && preg_match('/^[A-Za-z0-9_-]{8,100}={0,2}$/', $authSecret) === 1;
        if (!$isHttpsEndpoint || !$keysLookRight) {
            return Response::json(['ok' => false, 'error' => 'not_a_subscription'], 422);
        }
        $this->services->pushSubscriptions()->save($this->pages->user()->id, $endpoint, $publicKey, $authSecret);

        return Response::json(['ok' => true]);
    }

    /**
     * @param array<string, string> $parts
     */
    public function remove(Request $request, array $parts): Response
    {
        $this->services->pushSubscriptions()->remove(MemberPages::idFrom($parts), $this->pages->user()->id);

        return $this->pages->done($request, '/account/notifications', 'notifications.device_removed');
    }

    private static function hourOrNull(string $value): ?int
    {
        return preg_match('/^(1?[0-9]|2[0-3])$/', $value) === 1 ? (int) $value : null;
    }
}
