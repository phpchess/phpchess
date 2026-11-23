<?php
/**
 * The admin confirms their password before admin work (ID-08).
 *
 * Variables: $error (string), $next (string, the admin page to go to), $basePath, $csrfToken, $t.
 */
?>
<h1><?= e($t->text('admin.confirm.title')) ?></h1>
<p><?= e($t->text('admin.confirm.explain')) ?></p>

<?php if ($error !== '') : ?>
    <p class="notice-error" role="alert"><?= e($error) ?></p>
<?php endif; ?>

<form method="post" action="<?= e($basePath) ?>/admin/confirm" class="form">
    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
    <input type="hidden" name="next" value="<?= e($next) ?>">
    <label><?= e($t->text('account.password')) ?>
        <input type="password" name="password" autocomplete="current-password" required>
    </label>
    <button type="submit"><?= e($t->text('admin.confirm.button')) ?></button>
</form>
