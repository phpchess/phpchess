<?php
/**
 * Notification settings (N-03, N-04): per kind push, email or none; quiet
 * hours; this device's push; the devices that receive push.
 *
 * "Turn on push here" is a button: the browser asks the member only after
 * that tap (push.js). On an iPhone, push works only in the installed app
 * (Share, then "Add to Home Screen"), from iOS 16.4; the page says so.
 *
 * Variables: $choices (array{games, challenges, messages, quiet_from, quiet_until, time_zone}),
 * $channels and $groups (lists of string), $pushIsOn (bool), $devices (list of array{id, endpoint, created_at}),
 * $vapidPublicKey (string), $basePath, $csrfToken, $t.
 */
?>
<h1><?= e($t->text('notifications.title')) ?></h1>

<form method="post" action="<?= e($basePath) ?>/account/notifications" class="form">
    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
    <?php foreach ($groups as $group) : ?>
        <fieldset>
            <legend><?= e($t->text('notifications.group.' . $group)) ?></legend>
            <?php foreach ($channels as $channel) : ?>
                <?php if ($channel === 'push' && !$pushIsOn) {
                    continue;
                } ?>
                <label class="checkbox"><input type="radio" name="notify_<?= e($group) ?>" value="<?= e($channel) ?>"<?= $choices[$group] === $channel ? ' checked' : '' ?>> <?= e($t->text('notifications.channel.' . $channel)) ?></label>
            <?php endforeach; ?>
        </fieldset>
    <?php endforeach; ?>

    <?php if ($pushIsOn) : ?>
        <fieldset>
            <legend><?= e($t->text('notifications.quiet_hours')) ?></legend>
            <p class="muted"><?= e($t->text('notifications.quiet_explain')) ?></p>
            <label for="quiet_from"><?= e($t->text('notifications.quiet_from')) ?></label>
            <select id="quiet_from" name="quiet_from">
                <option value=""><?= e($t->text('notifications.no_quiet_hours')) ?></option>
                <?php for ($hour = 0; $hour < 24; $hour++) : ?>
                    <option value="<?= e($hour) ?>"<?= $choices['quiet_from'] === $hour ? ' selected' : '' ?>><?= e(sprintf('%02d:00', $hour)) ?></option>
                <?php endfor; ?>
            </select>
            <label for="quiet_until"><?= e($t->text('notifications.quiet_until')) ?></label>
            <select id="quiet_until" name="quiet_until">
                <option value=""><?= e($t->text('notifications.no_quiet_hours')) ?></option>
                <?php for ($hour = 0; $hour < 24; $hour++) : ?>
                    <option value="<?= e($hour) ?>"<?= $choices['quiet_until'] === $hour ? ' selected' : '' ?>><?= e(sprintf('%02d:00', $hour)) ?></option>
                <?php endfor; ?>
            </select>
            <label for="time_zone"><?= e($t->text('notifications.time_zone')) ?></label>
            <input type="text" id="time_zone" name="time_zone" value="<?= e($choices['time_zone']) ?>" maxlength="60" data-fill-time-zone>
        </fieldset>
    <?php endif; ?>
    <button type="submit" class="button"><?= e($t->text('notifications.save')) ?></button>
</form>

<?php if ($pushIsOn) : ?>
    <section aria-labelledby="push-title" id="push-section"
             data-vapid-public-key="<?= e($vapidPublicKey) ?>"
             data-subscribe-url="<?= e($basePath) ?>/account/push/subscribe"
             data-csrf-token="<?= e($csrfToken) ?>"
             data-text-done="<?= e($t->text('notifications.push_on')) ?>"
             data-text-denied="<?= e($t->text('notifications.push_denied')) ?>"
             data-text-unsupported="<?= e($t->text('notifications.push_unsupported')) ?>"
             data-text-failed="<?= e($t->text('notifications.push_failed')) ?>">
        <h2 id="push-title"><?= e($t->text('notifications.push_title')) ?></h2>
        <p class="muted"><?= e($t->text('notifications.iphone_hint')) ?></p>
        <p><button type="button" class="button" id="push-button"><?= e($t->text('notifications.turn_on_push')) ?></button></p>
        <p id="push-status" role="status"></p>

        <?php if ($devices !== []) : ?>
            <h3><?= e($t->text('notifications.devices')) ?></h3>
            <ul class="text-list">
                <?php foreach ($devices as $device) : ?>
                    <li>
                        <?= e((string) parse_url($device['endpoint'], PHP_URL_HOST)) ?>
                        <span class="muted"><?= e(substr($device['created_at'], 0, 10)) ?></span>
                        <form method="post" action="<?= e($basePath) ?>/account/push/<?= e($device['id']) ?>/remove" class="inline-form">
                            <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                            <button type="submit" class="link-button"><?= e($t->text('notifications.remove_device')) ?></button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
    <script type="module" src="<?= e($basePath) ?>/assets/js/app/push.js"></script>
<?php endif; ?>
