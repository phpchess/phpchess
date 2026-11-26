<?php
/**
 * Create a tournament (T-01, A107): a name and three choices (format, pace,
 * who may join) make a working tournament; the rest has defaults behind
 * "More settings". Afterwards the organiser lands on the manage page.
 *
 * Variables: $timeControls (list of string), $maximumDays (int),
 * $basePath, $csrfToken, $t, $template.
 */
?>
<h1><?= e($t->text('tournament.create_title')) ?></h1>

<form method="post" action="<?= e($basePath) ?>/tournaments/new" class="form">
    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
    <?= $template->render('tournaments/settings_fields', [
        'values' => [],
        'timeControls' => $timeControls,
        'maximumDays' => $maximumDays,
        'lockFormat' => false,
        't' => $t,
        'template' => $template,
    ]) ?>
    <button type="submit" class="button button-large"><?= $template->render('partials/icon', ['icon' => 'check']) ?> <?= e($t->text('tournament.create_button')) ?></button>
</form>
