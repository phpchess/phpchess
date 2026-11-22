<?php
/**
 * Installer step 3, when the account can use several databases (A23).
 *
 * The login details are kept in the session meanwhile, so only the choice is sent.
 *
 * Variables: $choices (list of string, database names), $message (string),
 * $basePath, $csrfToken.
 */
?>
<p><?= e($message) ?> phpChess only adds its own tables (starting with its prefix), so a database that other programs use is fine too.</p>
<form method="post" action="<?= e($basePath) ?>/install/database" class="form">
    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
    <input type="hidden" name="picked_from_list" value="yes">
    <?php foreach ($choices as $index => $name) : ?>
        <label class="choice"><input type="radio" name="database_name" value="<?= e($name) ?>" <?= $index === 0 ? 'checked' : '' ?>> <?= e($name) ?></label>
    <?php endforeach; ?>
    <button type="submit">Use this database</button>
</form>
