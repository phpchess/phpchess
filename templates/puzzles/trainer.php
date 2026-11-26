<?php
/**
 * The puzzle trainer (C5-1, L-01, A76): the bundled Lichess puzzles.
 *
 * At the top, two rows of choice buttons: the motif (fork, pin, mate in 2 ...)
 * and the level. puzzles.js asks the server for a puzzle that fits, plays
 * the opponent's first move, and lets the player find the answer. After
 * each puzzle: "Next", "Show me" (the solution), and a plain link to the
 * same puzzle on lichess.org (A75).
 *
 * Variables: $motifs (list of string), $levels (list of string),
 * $boardTheme (string), $puzzleCount (int), $lichessTrainingLink (string, the start of a puzzle's address on lichess.org), $basePath, $csrfToken, $t, $template.
 */
?>
<p><a class="icon-button" href="<?= e($basePath) ?>/learn" aria-label="<?= e($t->text('learn.title')) ?>" title="<?= e($t->text('learn.title')) ?>"><?= $template->render('partials/icon', ['icon' => 'back']) ?></a></p>
<h1><?= e($t->text('puzzles.title')) ?></h1>

<div class="puzzle-trainer" id="puzzle-trainer"
     data-base-path="<?= e($basePath) ?>"
     data-csrf-token="<?= e($csrfToken) ?>"
     data-lichess-training="<?= e($lichessTrainingLink) ?>"
     data-text-your-move="<?= e($t->text('puzzles.your_move')) ?>"
     data-text-solved="<?= e($t->text('puzzles.solved')) ?>"
     data-text-wrong="<?= e($t->text('puzzles.wrong')) ?>"
     data-text-rating="<?= e($t->text('puzzles.rating')) ?>"
     data-text-error="<?= e($t->text('puzzles.none')) ?>">

    <?php // --- The choices: one row of buttons each (no drop-down menus, F2) --- ?>
    <fieldset class="choice-group">
        <legend><?= e($t->text('puzzles.motif')) ?></legend>
        <?php foreach ($motifs as $index => $motif) : ?>
            <label class="choice">
                <input type="radio" name="motif" value="<?= e($motif) ?>"<?= $index === 0 ? ' checked' : '' ?>>
                <span><?= e($t->text('puzzles.motif.' . $motif)) ?></span>
            </label>
        <?php endforeach; ?>
    </fieldset>
    <fieldset class="choice-group">
        <legend><?= e($t->text('puzzles.level')) ?></legend>
        <?php foreach ($levels as $level) : ?>
            <label class="choice">
                <input type="radio" name="level" value="<?= e($level) ?>"<?= $level === 'improving' ? ' checked' : '' ?>>
                <span><?= e($t->text('puzzles.level.' . $level)) ?></span>
            </label>
        <?php endforeach; ?>
    </fieldset>

    <p class="button-row">
        <button type="button" class="button-large" id="puzzle-start"><?= $template->render('partials/icon', ['icon' => 'play', 'size' => 20]) ?> <?= e($t->text('puzzles.start')) ?></button>
    </p>

    <?php // --- The puzzle (shown by the script) --- ?>
    <section id="puzzle-running" hidden>
        <p id="puzzle-status" role="status" aria-live="polite"></p>
        <div class="board-frame board-theme-<?= e($boardTheme) ?>" id="puzzle-board"></div>
        <p class="button-row">
            <button type="button" class="icon-button" id="puzzle-hint" aria-label="<?= e($t->text('puzzles.show_me')) ?>" title="<?= e($t->text('puzzles.show_me')) ?>">
                <?= $template->render('partials/icon', ['icon' => 'hint']) ?>
            </button>
            <button type="button" class="button" id="puzzle-next"><?= $template->render('partials/icon', ['icon' => 'next', 'size' => 20]) ?> <?= e($t->text('puzzles.next')) ?></button>
            <a class="button button-secondary" id="puzzle-on-lichess" href="#" target="_blank" rel="noopener noreferrer" hidden><?= $template->render('partials/icon', ['icon' => 'link', 'size' => 20]) ?> lichess.org</a>
        </p>
    </section>
</div>

<p class="hint"><?= e($t->text('puzzles.credit', ['count' => $puzzleCount])) ?></p>

<link rel="stylesheet" href="<?= e($basePath) ?>/assets/vendor/cm-chessboard/assets/chessboard.css">
<link rel="stylesheet" href="<?= e($basePath) ?>/assets/vendor/cm-chessboard/assets/extensions/markers/markers.css">
<link rel="stylesheet" href="<?= e($basePath) ?>/assets/vendor/cm-chessboard/assets/extensions/promotion-dialog/promotion-dialog.css">
<script type="module" src="<?= e($basePath) ?>/assets/js/learn/puzzles.js"></script>
