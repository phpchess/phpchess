<?php
/**
 * Logging in with a login card (SCH-04). The card's link opens this page;
 * one tap on the button logs in. The button is needed because some apps
 * open links in the background to preview them, which would use up the
 * one-time card.
 *
 * Variables: $token (string, '' when the link is broken), $basePath, $csrfToken, $t.
 */
?>
<h1><?= e($t->text('school.card_login_title')) ?></h1>
<?php if ($token === '') : ?>
    <p><?= e($t->text('school.card_not_valid')) ?></p>
<?php else : ?>
    <form method="post" action="<?= e($basePath) ?>/c/<?= e($token) ?>" class="form">
        <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
        <button type="submit" class="button button-large"><?= e($t->text('school.card_login_button')) ?></button>
    </form>
<?php endif; ?>
