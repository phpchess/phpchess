<?php
/**
 * Community settings: the word filter (C-05), the kind of game chat
 * (G-12), the newsletter speed (ADM-07) and the archive cut-off (A43).
 *
 * Variables: $wordFilter (string), $chatMode (string), $chatModes (list of string),
 * $newsletterPerRun (int), $archiveCutoffMs (int),
 * $warnings, $currentAdminPath, $basePath, $csrfToken, $t, $template.
 */
?>
<?= $template->render('admin/header', ['warnings' => $warnings, 'currentAdminPath' => $currentAdminPath, 'basePath' => $basePath, 't' => $t]) ?>

<h1><?= e($t->text('admin.community.title')) ?></h1>

<form method="post" action="<?= e($basePath) ?>/admin/community" class="form">
    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">

    <label for="word_filter"><?= e($t->text('admin.community.word_filter')) ?></label>
    <textarea id="word_filter" name="word_filter" rows="6"><?= e($wordFilter) ?></textarea>
    <p class="muted"><?= e($t->text('admin.community.word_filter_explain')) ?></p>

    <fieldset>
        <legend><?= e($t->text('admin.community.chat_mode')) ?></legend>
        <?php foreach ($chatModes as $mode) : ?>
            <label class="checkbox"><input type="radio" name="chat_mode" value="<?= e($mode) ?>"<?= $mode === $chatMode ? ' checked' : '' ?>> <?= e($t->text('admin.community.chat_mode.' . $mode)) ?></label>
        <?php endforeach; ?>
        <p class="muted"><?= e($t->text('admin.community.chat_switch_hint')) ?></p>
    </fieldset>

    <label for="newsletter_per_run"><?= e($t->text('admin.community.newsletter_per_run')) ?></label>
    <input type="number" id="newsletter_per_run" name="newsletter_per_run" min="1" max="1000" value="<?= e($newsletterPerRun) ?>">

    <label for="archive_cutoff_ms"><?= e($t->text('admin.community.archive_cutoff')) ?></label>
    <input type="number" id="archive_cutoff_ms" name="archive_cutoff_ms" min="50" max="60000" value="<?= e($archiveCutoffMs) ?>">

    <button type="submit" class="button"><?= e($t->text('admin.community.save')) ?></button>
</form>
