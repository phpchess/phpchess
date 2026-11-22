<?php
/**
 * Installer step 4: the first admin account (INS-02).
 *
 * Variables: $error (string), $username (string), $email (string), $basePath, $csrfToken.
 */
?>
<p>This account runs the server. You can add more admins later.</p>
<?php if ($error !== '') : ?>
    <p class="notice-error"><?= e($error) ?></p>
<?php endif; ?>
<form method="post" action="<?= e($basePath) ?>/install/admin" class="form">
    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
    <label>User name <input name="username" value="<?= e($username) ?>" autocomplete="username" required></label>
    <label>Email <input type="email" name="email" value="<?= e($email) ?>" autocomplete="email" required></label>
    <label>Password (at least 10 characters) <input type="password" name="password" autocomplete="new-password" minlength="10" required></label>
    <label>Password again <input type="password" name="password_again" autocomplete="new-password" minlength="10" required></label>
    <button type="submit">Continue</button>
</form>
