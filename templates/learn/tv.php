<?php
/**
 * Lichess TV on our own board (C5-1, L-01, A75): no iframe.
 *
 * tv.js asks our server for the game now on the chosen channel
 * (GET /learn/tv/now) every few seconds and shows its last position. Our
 * server asks lichess.org, at most once every 10 seconds for all viewers,
 * and the game's PGN passes the content scanner like every other game (A81).
 * Only shown while "external content" is on (K8).
 *
 * Variables: $channels (list of string), $boardTheme (string), $basePath, $t, $template.
 */
?>
<p><a class="icon-button" href="<?= e($basePath) ?>/learn" aria-label="<?= e($t->text('learn.title')) ?>" title="<?= e($t->text('learn.title')) ?>"><?= $template->render('partials/icon', ['icon' => 'back']) ?></a></p>
<h1><?= e($t->text('tv.title')) ?></h1>

<div class="lichess-tv" id="lichess-tv" data-base-path="<?= e($basePath) ?>" data-text-error="<?= e($t->text('lichess.unavailable')) ?>">
    <fieldset class="choice-group">
        <legend><?= e($t->text('tv.channel')) ?></legend>
        <?php foreach ($channels as $index => $channel) : ?>
            <label class="choice">
                <input type="radio" name="channel" value="<?= e($channel) ?>"<?= $index === 0 ? ' checked' : '' ?>>
                <span><?= e($channel) ?></span>
            </label>
        <?php endforeach; ?>
    </fieldset>

    <p id="lichess-tv-players" class="lichess-tv-players" aria-live="polite"></p>
    <div class="board-frame board-theme-<?= e($boardTheme) ?>" id="lichess-tv-board"></div>
    <p id="lichess-tv-status" class="muted" role="status"></p>
</div>

<p class="hint"><?= e($t->text('tv.credit')) ?></p>

<link rel="stylesheet" href="<?= e($basePath) ?>/assets/vendor/cm-chessboard/assets/chessboard.css">
<script type="module" src="<?= e($basePath) ?>/assets/js/learn/tv.js"></script>
