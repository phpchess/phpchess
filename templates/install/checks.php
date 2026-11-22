<?php
/**
 * A list of check results, used by the pre-flight and database steps (INS-03).
 *
 * Variables: $checks (list of PhpChess\Install\CheckResult).
 */
?>
<ul class="checks">
    <?php foreach ($checks as $check) : ?>
        <li class="<?= $check->passed ? 'check-passed' : ($check->required ? 'check-failed' : 'check-warning') ?>">
            <strong><?= e($check->name) ?></strong>
            <span><?= e($check->detail) ?></span>
            <?php if (!$check->passed && $check->fix !== '') : ?>
                <span class="fix">What to do: <?= e($check->fix) ?></span>
            <?php endif; ?>
        </li>
    <?php endforeach; ?>
</ul>
