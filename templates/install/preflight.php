<?php
/**
 * Installer step 1: pre-flight checks (INS-03).
 *
 * Variables: $checks (list of CheckResult), $canContinue (bool),
 * $basePath, $csrfToken (string), $template (PhpChess\Http\Template).
 */
?>
<p>phpChess checks that this server has what it needs. Red lines must be fixed; yellow lines are advice.</p>
<?= $template->render('install/checks', ['checks' => $checks]) ?>
<?php if ($canContinue) : ?>
    <form method="post" action="<?= e($basePath) ?>/install/preflight">
        <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
        <button type="submit">Continue</button>
    </form>
<?php else : ?>
    <p class="notice-error">Please fix the red lines, then reload this page.</p>
<?php endif; ?>
