<?php
/**
 * "Forgot your password?": asks for the email address (ID-06).
 *
 * Variables: $error (string), $basePath, $csrfToken, $t.
 */
?>
<h1><?= e($t->text('forgot.title')) ?></h1>
<p><?= e($t->text('forgot.explain')) ?></p>

<?php if ($error !== '') : ?>
    <p class="notice-error" role="alert"><?= e($error) ?></p>
<?php endif; ?>

<form method="post" action="<?= e($basePath) ?>/forgot-password" class="form">
    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
    <label><?= e($t->text('account.email')) ?>
        <input type="email" name="email" autocomplete="email" required>
    </label>
    <button type="submit"><?= e($t->text('forgot.button')) ?></button>
</form>
