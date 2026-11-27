<?php
/**
 * Create a tournament (T-01, A107): a name and three choices (format, pace,
 * who may join) make a working tournament; the rest has defaults behind
 * "More settings". Afterwards the organiser lands on the manage page.
 *
 * "Propose from dates" (A114) sends the form back here with round days and a
 * break filled in, the calendar they give and a short message; nothing is saved.
 *
 * Variables: $timeControls (list of string), $maximumDays (int), $values (the form's
 * values, [] for a new form), $calendarRounds (the proposed calendar, or []),
 * $proposalMessage (string), $basePath, $csrfToken, $t, $template.
 */
?>
<h1><?= e($t->text('tournament.create_title')) ?></h1>
<?php if ($proposalMessage !== '') : ?>
    <p class="notice-success" role="status"><?= e($proposalMessage) ?></p>
<?php endif; ?>

<form method="post" action="<?= e($basePath) ?>/tournaments/new" class="form">
    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
    <?= $template->render('tournaments/settings_fields', [
        'values' => $values,
        'calendarRounds' => $calendarRounds,
        'timeControls' => $timeControls,
        'maximumDays' => $maximumDays,
        'lockFormat' => false,
        't' => $t,
        'template' => $template,
    ]) ?>
    <button type="submit" class="button button-large"><?= $template->render('partials/icon', ['icon' => 'check']) ?> <?= e($t->text('tournament.create_button')) ?></button>
</form>
