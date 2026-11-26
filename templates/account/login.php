<?php
/**
 * Login form, with "remember me" and the "send the link again" button (ID-02, ID-04, ID-05).
 *
 * Variables: $nameOrEmail (string), $error (string), $showResend (bool: the
 * account waits for email verification), $next (string, where to go after
 * login), $registrationIsOpen (bool), $basePath, $csrfToken, $t.
 * F2: actions are buttons (A66); an empty slot waits for other ways to log in (A73).
 */
?>
<h1><?= e($t->text('login.title')) ?></h1>

<?php if ($error !== '') : ?>
    <p class="notice-error" role="alert"><?= e($error) ?></p>
<?php endif; ?>

<?php if ($showResend) : ?>
    <form method="post" action="<?= e($basePath) ?>/verify-email/send" class="inline-form">
        <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
        <input type="hidden" name="name_or_email" value="<?= e($nameOrEmail) ?>">
        <button type="submit" class="button-secondary"><?= e($t->text('verify.send_again')) ?></button>
    </form>
<?php endif; ?>

<form method="post" action="<?= e($basePath) ?>/login" class="form">
    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
    <input type="hidden" name="next" value="<?= e($next) ?>">
    <label><?= e($t->text('login.name_or_email')) ?>
        <input name="name_or_email" value="<?= e($nameOrEmail) ?>" autocomplete="username" required>
    </label>
    <label><?= e($t->text('account.password')) ?>
        <input type="password" name="password" autocomplete="current-password" required>
    </label>
    <label class="checkbox"><input type="checkbox" name="remember_me" value="yes"> <?= e($t->text('login.remember_me')) ?></label>
    <button type="submit"><?= e($t->text('login.button')) ?></button>
</form>

<?php
/*
 * ===== Slot: other ways to log in (A73) =====
 * Empty on purpose. Chunk C5-1 puts an optional "Log in with Lichess"
 * button here, as its own template (account/login_other_ways), when its
 * feature switch is on. Lichess is never needed to play on this site.
 */
?>
<div class="login-other-ways" id="login-other-ways"></div>

<div class="button-row">
    <a class="button button-secondary" href="<?= e($basePath) ?>/forgot-password"><?= e($t->text('login.forgot_password')) ?></a>
    <?php if ($registrationIsOpen) : ?>
        <a class="button button-secondary" href="<?= e($basePath) ?>/register"><?= e($t->text('login.no_account')) ?></a>
    <?php endif; ?>
</div>
