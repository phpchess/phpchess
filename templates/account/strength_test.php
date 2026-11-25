<?php
/**
 * The strength test page (B15, A60): 10 puzzles on a board.
 *
 * Without a test running it shows the explanation and the Start button,
 * or the last result and when a new test is possible.
 * strength-test.js runs the test: it asks the server for each puzzle,
 * plays the opponent's first move, lets the player answer by moving,
 * and sends "solved" or "missed".
 *
 * Variables: $band (string|null, the band of the last test),
 * $nextTestDate (string|null, "2026-12-25" when a new test is not possible yet),
 * $boardTheme (string), $basePath, $csrfToken, $t.
 */
?>
<h1><?= e($t->text('strength_test.title')) ?></h1>

<div class="strength-test" id="strength-test"
     data-base-path="<?= e($basePath) ?>"
     data-csrf-token="<?= e($csrfToken) ?>"
     data-text-your-move="<?= e($t->text('strength_test.your_move')) ?>"
     data-text-solved="<?= e($t->text('strength_test.solved')) ?>"
     data-text-missed="<?= e($t->text('strength_test.missed')) ?>"
     data-text-result="<?= e($t->text('strength_test.result')) ?>"
     data-text-number="<?= e($t->text('strength_test.number')) ?>"
     data-text-error="<?= e($t->text('strength_test.lost')) ?>">

    <?php // --- Before the test --- ?>
    <section id="strength-test-intro">
        <?php if ($band !== null) : ?>
            <p><?= e($t->text('strength_test.last_result', ['band' => $t->text('register.strength.' . $band)])) ?></p>
        <?php endif; ?>
        <?php if ($nextTestDate !== null) : ?>
            <p class="muted"><?= e($t->text('strength_test.next_date', ['date' => $nextTestDate])) ?></p>
        <?php else : ?>
            <p><?= e($t->text('strength_test.intro')) ?></p>
            <p class="button-row">
                <button type="button" class="button-large" id="strength-test-start"><?= e($t->text('strength_test.start')) ?></button>
            </p>
        <?php endif; ?>
    </section>

    <?php // --- During the test (shown by the script) --- ?>
    <section id="strength-test-running" hidden>
        <p id="strength-test-status" role="status" aria-live="polite"></p>
        <div class="board-frame board-theme-<?= e($boardTheme) ?>" id="strength-test-board"></div>
    </section>

    <?php // --- The result (shown by the script) --- ?>
    <section id="strength-test-done" hidden>
        <p id="strength-test-result" role="status"></p>
        <p class="button-row">
            <a class="button" href="<?= e($basePath) ?>/play"><?= e($t->text('play.button')) ?></a>
        </p>
    </section>
</div>

<link rel="stylesheet" href="<?= e($basePath) ?>/assets/vendor/cm-chessboard/assets/chessboard.css">
<link rel="stylesheet" href="<?= e($basePath) ?>/assets/vendor/cm-chessboard/assets/extensions/markers/markers.css">
<link rel="stylesheet" href="<?= e($basePath) ?>/assets/vendor/cm-chessboard/assets/extensions/promotion-dialog/promotion-dialog.css">
<script type="module" src="<?= e($basePath) ?>/assets/js/account/strength-test.js"></script>
