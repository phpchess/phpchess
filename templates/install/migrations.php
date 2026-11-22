<?php
/**
 * Installer step 5: creating the tables (INS-05).
 *
 * Variables: $pendingSteps (int), $stepsRun (list of string: the steps done by
 * the last click), $basePath, $csrfToken.
 */
?>
<?php if ($stepsRun !== []) : ?>
    <p>Done just now:</p>
    <ul class="checks">
        <?php foreach ($stepsRun as $step) : ?>
            <li class="check-passed"><?= e($step) ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
<p><?= e($pendingSteps) ?> database steps still to do. They run in small batches, so the server's time limit is never reached. If a batch stops, press the button again: it carries on where it stopped.</p>
<form method="post" action="<?= e($basePath) ?>/install/migrations">
    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
    <button type="submit"><?= $pendingSteps > 0 ? 'Set up the database' : 'Create the admin account' ?></button>
</form>
