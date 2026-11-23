<?php
/**
 * The feature switches page (SCH-01, K8): every switch, with what it does.
 * A feature this server can't run (ServerSupport: no GD for uploads, no
 * push support) is greyed out with the reason, and stays off.
 *
 * Variables: $states (array: switch name => bool, on or off),
 * $unavailable (array: switch name => language key of the reason),
 * $warnings, $currentAdminPath, $basePath, $csrfToken, $t, $template.
 */
?>
<?= $template->render('admin/header', ['warnings' => $warnings, 'currentAdminPath' => $currentAdminPath, 'basePath' => $basePath, 't' => $t]) ?>

<h1><?= e($t->text('admin.switches.title')) ?></h1>
<p><?= e($t->text('admin.switches.explain')) ?></p>

<form method="post" action="<?= e($basePath) ?>/admin/switches" class="form">
    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
    <?php foreach ($states as $name => $isOn) : ?>
        <?php $reasonKey = $unavailable[$name] ?? ''; ?>
        <label class="switch-row<?= $reasonKey !== '' ? ' is-unavailable' : '' ?>">
            <input type="checkbox" name="switch_<?= e($name) ?>" value="on"<?= $isOn ? ' checked' : '' ?><?= $reasonKey !== '' ? ' disabled' : '' ?>>
            <span>
                <strong><?= e($t->text('switch.' . $name . '.label')) ?></strong>
                <span class="hint"><?= e($t->text('switch.' . $name . '.explain')) ?></span>
                <?php if ($reasonKey !== '') : ?>
                    <span class="hint notice-error"><?= e($t->text($reasonKey)) ?></span>
                <?php endif; ?>
            </span>
        </label>
    <?php endforeach; ?>
    <button type="submit"><?= e($t->text('admin.save')) ?></button>
</form>
