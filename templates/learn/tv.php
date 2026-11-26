<?php
/**
 * Lichess TV on our own board (C5-1, L-01, A75): no iframe.
 *
 * tv.js asks our server for the game now on the chosen channel
 * (GET /learn/tv/now) every few seconds and plays the new moves on the
 * shared replay (partials/replay.php, replay.js; B48), so it looks live. Our
 * server asks lichess.org, at most once every 10 seconds for all viewers,
 * and the game's PGN passes the content scanner like every other game (A81).
 * Only shown while "external content" is on (K8).
 *
 * When a game ends, tv.js shows the box "Game ended: White won. Next game?"
 * over the board (Yes: the next game; No: stay on it in replay mode).
 *
 * Variables: $channels (list of string: Lichess's channel keys), $chosenChannel (string), $boardTheme (string), $basePath, $t, $template.
 */
?>
<p><a class="icon-button" href="<?= e($basePath) ?>/learn" aria-label="<?= e($t->text('learn.title')) ?>" title="<?= e($t->text('learn.title')) ?>"><?= $template->render('partials/icon', ['icon' => 'back']) ?></a></p>
<h1><?= e($t->text('tv.title')) ?></h1>

<div class="lichess-tv" id="lichess-tv" data-base-path="<?= e($basePath) ?>" data-text-error="<?= e($t->text('lichess.unavailable')) ?>"
     data-text-white-won="<?= e($t->text('tv.ended_white')) ?>" data-text-black-won="<?= e($t->text('tv.ended_black')) ?>"
     data-text-draw="<?= e($t->text('tv.ended_draw')) ?>" data-text-ended="<?= e($t->text('tv.ended')) ?>">
    <fieldset class="choice-group">
        <legend><?= e($t->text('tv.channel')) ?></legend>
        <?php foreach ($channels as $index => $channel) : ?>
            <label class="choice">
                <input type="radio" name="channel" value="<?= e($channel) ?>"<?= $channel === $chosenChannel ? ' checked' : '' ?>>
                <span><?= e($t->text('tv.channel.' . $channel)) ?></span>
            </label>
        <?php endforeach; ?>
    </fieldset>

    <p id="lichess-tv-players" class="lichess-tv-players" aria-live="polite"></p>
    <p id="lichess-tv-status" class="muted" role="status"></p>
    <?= $template->render('partials/replay', ['replayId' => 'lichess-tv-replay', 'boardTheme' => $boardTheme, 'isLive' => true, 'showComments' => false, 'basePath' => $basePath, 't' => $t]) ?>

    <?php // After "No" in the box: the next game is one button away. ?>
    <p class="button-row centred"><button type="button" class="button" id="lichess-tv-next" hidden><?= e($t->text('tv.next_game')) ?></button></p>

    <?php // The box over the board when a game ends (tv.js fills in the result). ?>
    <div class="tv-ended" id="lichess-tv-ended" role="dialog" aria-modal="false" aria-labelledby="lichess-tv-ended-text" hidden>
        <p id="lichess-tv-ended-text"></p>
        <p><?= e($t->text('tv.next_question')) ?></p>
        <p class="button-row centred">
            <button type="button" class="button" data-answer="yes"><?= e($t->text('tv.yes')) ?></button>
            <button type="button" class="button button-secondary" data-answer="no"><?= e($t->text('tv.no')) ?></button>
        </p>
    </div>
</div>

<p class="hint"><?= e($t->text('tv.credit')) ?></p>

<link rel="stylesheet" href="<?= e($basePath) ?>/assets/vendor/cm-chessboard/assets/chessboard.css">
<link rel="stylesheet" href="<?= e($basePath) ?>/assets/vendor/cm-chessboard/assets/extensions/markers/markers.css">
<script type="module" src="<?= e($basePath) ?>/assets/js/learn/tv.js?v=6"></script><?php // ?v=6: a new address, so browsers drop the old copy they kept ?>
