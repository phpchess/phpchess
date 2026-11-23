<?php
/**
 * Vacation (G-08, A29): whole days off that pause the clocks of all my
 * correspondence games.
 *
 * Variables: $upcomingDays (list of "YYYY-MM-DD"), $daysLeft (int),
 * $daysPerYear (int), $today (string), $basePath, $csrfToken, $t.
 */
?>
<h1><?= e($t->text('vacation.title')) ?></h1>
<p><?= e($t->text('vacation.explain', ['days' => $daysPerYear])) ?></p>
<p><strong><?= e($t->text('vacation.days_left', ['days' => $daysLeft])) ?></strong></p>

<?php if ($upcomingDays !== []) : ?>
    <h2><?= e($t->text('vacation.planned')) ?></h2>
    <p><?= e(implode(', ', $upcomingDays)) ?></p>
    <form method="post" action="<?= e($basePath) ?>/account/vacation/cancel" class="inline-form">
        <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
        <button type="submit" class="button-secondary"><?= e($t->text('vacation.cancel')) ?></button>
    </form>
<?php endif; ?>

<h2><?= e($t->text('vacation.take')) ?></h2>
<form method="post" action="<?= e($basePath) ?>/account/vacation" class="form">
    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
    <label><?= e($t->text('vacation.first_day')) ?>
        <input type="date" name="first_day" min="<?= e($today) ?>" value="<?= e($today) ?>" required>
    </label>
    <label><?= e($t->text('vacation.number_of_days')) ?>
        <input type="number" name="days" min="1" max="<?= e(max(1, $daysLeft)) ?>" value="1" required>
    </label>
    <button type="submit"><?= e($t->text('vacation.button')) ?></button>
</form>
