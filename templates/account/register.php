<?php
/**
 * Registration form (ID-01).
 *
 * Variables: $username, $email (string, what was typed), $error (string),
 * $basePath, $csrfToken, $t.
 */
?>
<h1><?= e($t->text('register.title')) ?></h1>

<?php if ($error !== '') : ?>
    <p class="notice-error" role="alert"><?= e($error) ?></p>
<?php endif; ?>

<form method="post" action="<?= e($basePath) ?>/register" class="form">
    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
    <label><?= e($t->text('account.username')) ?>
        <input name="username" value="<?= e($username) ?>" autocomplete="username" required minlength="3" maxlength="40">
        <span class="hint"><?= e($t->text('account.username_rules')) ?></span>
    </label>
    <label><?= e($t->text('account.email')) ?>
        <input type="email" name="email" value="<?= e($email) ?>" autocomplete="email" required>
    </label>
    <label><?= e($t->text('account.password')) ?>
        <input type="password" name="password" autocomplete="new-password" required minlength="10">
        <span class="hint"><?= e($t->text('account.password_too_short', ['length' => 10])) ?></span>
    </label>
    <label><?= e($t->text('account.password_again')) ?>
        <input type="password" name="password_again" autocomplete="new-password" required minlength="10">
    </label>
    <button type="submit"><?= e($t->text('register.button')) ?></button>
</form>

<p><a href="<?= e($basePath) ?>/login"><?= e($t->text('register.have_account')) ?></a></p>
