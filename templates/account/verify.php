<?php
/**
 * Email verification (ID-02): the link from the email opens this page; the
 * button confirms. (Opening a link never changes anything, K7.)
 *
 * Variables: $token (string, from the link), $basePath, $csrfToken, $t.
 */
?>
<h1><?= e($t->text('verify.title')) ?></h1>
<p><?= e($t->text('verify.explain')) ?></p>
<form method="post" action="<?= e($basePath) ?>/verify-email">
    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
    <input type="hidden" name="token" value="<?= e($token) ?>">
    <button type="submit"><?= e($t->text('verify.button')) ?></button>
</form>
