<?php
/**
 * Changing the password; the old one is asked for (ID-13).
 *
 * Variables: $error (string), $basePath, $csrfToken, $t.
 */
?>
<h1><?= e($t->text('password.title')) ?></h1>

<?php if ($error !== '') : ?>
    <p class="notice-error" role="alert"><?= e($error) ?></p>
<?php endif; ?>

<form method="post" action="<?= e($basePath) ?>/account/password" class="form">
    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
    <label><?= e($t->text('password.old')) ?>
        <input type="password" name="old_password" autocomplete="current-password" required>
    </label>
    <label><?= e($t->text('password.new')) ?>
        <input type="password" name="password" autocomplete="new-password" required minlength="10">
    </label>
    <label><?= e($t->text('account.password_again')) ?>
        <input type="password" name="password_again" autocomplete="new-password" required minlength="10">
    </label>
    <button type="submit"><?= e($t->text('password.button')) ?></button>
</form>
