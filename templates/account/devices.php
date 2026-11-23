<?php
/**
 * API access tokens per device (ID-10). A new token is shown once, at the top.
 *
 * Variables: $tokens (list of array{id, device_name, created_at, last_used_at}), $newToken (string, '' when none),
 * $basePath, $csrfToken, $t.
 */
?>
<h1><?= e($t->text('devices.title')) ?></h1>
<p><?= e($t->text('devices.explain')) ?></p>

<?php if ($newToken !== '') : ?>
    <div class="notice-warning" role="status">
        <p><?= e($t->text('devices.shown_once')) ?></p>
        <p><code class="token"><?= e($newToken) ?></code></p>
    </div>
<?php endif; ?>

<?php if ($tokens === []) : ?>
    <p class="muted"><?= e($t->text('devices.none')) ?></p>
<?php else : ?>
    <ul class="card-list">
        <?php foreach ($tokens as $token) : ?>
            <li class="card">
                <p class="card-title"><strong><?= e($token['device_name']) ?></strong></p>
                <p class="muted"><?= e($t->text('devices.made', ['date' => substr($token['created_at'], 0, 16)])) ?>
                    · <?= e($token['last_used_at'] === '' ? $t->text('devices.never_used') : $t->text('devices.last_used', ['date' => substr($token['last_used_at'], 0, 16)])) ?></p>
                <form method="post" action="<?= e($basePath) ?>/account/devices/<?= e($token['id']) ?>/revoke" class="inline-form">
                    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                    <button type="submit" class="button button-secondary button-small"><?= e($t->text('devices.revoke')) ?></button>
                </form>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<h2><?= e($t->text('devices.new')) ?></h2>
<form method="post" action="<?= e($basePath) ?>/account/devices" class="form form-inline">
    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
    <label for="device_name"><?= e($t->text('devices.name')) ?></label>
    <input type="text" id="device_name" name="device_name" required maxlength="80">
    <button type="submit" class="button"><?= e($t->text('devices.create')) ?></button>
</form>
