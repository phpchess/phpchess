<?php
/**
 * Setting a new password from the reset link (ID-06).
 *
 * Variables: $token (string), $error (string), $basePath, $csrfToken, $t.
 */
?>
<h1><?= e($t->text('reset.title')) ?></h1>

<?php if ($error !== '') : ?>
    <p class="notice-error" role="alert"><?= e($error) ?></p>
<?php endif; ?>

<form method="post" action="<?= e($basePath) ?>/reset-password" class="form">
    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
    <input type="hidden" name="token" value="<?= e($token) ?>">
    <label><?= e($t->text('password.new')) ?>
        <input type="password" name="password" autocomplete="new-password" required minlength="10">
    </label>
    <label><?= e($t->text('account.password_again')) ?>
        <input type="password" name="password_again" autocomplete="new-password" required minlength="10">
    </label>
    <button type="submit"><?= e($t->text('reset.button')) ?></button>
</form>
