<?php
/**
 * My data (ID-11): download all of it, or delete my account.
 *
 * Variables: $basePath, $csrfToken, $t.
 */
?>
<h1><?= e($t->text('own_data.title')) ?></h1>

<section aria-labelledby="export-title">
    <h2 id="export-title"><?= e($t->text('own_data.export_title')) ?></h2>
    <p><?= e($t->text('own_data.export_explain')) ?></p>
    <p><a class="button" href="<?= e($basePath) ?>/account/data/export" download><?= e($t->text('own_data.export_button')) ?></a></p>
</section>

<section aria-labelledby="delete-title" class="danger-zone">
    <h2 id="delete-title"><?= e($t->text('own_data.delete_title')) ?></h2>
    <p><?= e($t->text('own_data.delete_explain')) ?></p>
    <form method="post" action="<?= e($basePath) ?>/account/delete" class="form">
        <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
        <label for="password"><?= e($t->text('own_data.password')) ?></label>
        <input type="password" id="password" name="password" required autocomplete="current-password">
        <label class="checkbox"><input type="checkbox" name="confirm" value="yes" required> <?= e($t->text('own_data.confirm')) ?></label>
        <button type="submit" class="button button-danger"><?= e($t->text('own_data.delete_button')) ?></button>
    </form>
</section>
