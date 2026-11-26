<?php
/**
 * A challenge with more choices (G-05, G-06, G-21, A37): to one player, as
 * an invite link (or QR code), or from an own start position. The quick
 * ways to start a game are on the Play page (the new game pop-up, A70);
 * this page is for the rarer cases that need a name or a position.
 *
 * Variables: $opponents (array of id => name), $playDefaults (array{days_per_move: int, rated: bool}),
 * $maximumDays (int), $isFenAllowed (bool), $timeControls (list of "5+3" texts),
 * $basePath, $csrfToken, $t, $template.
 */

use PhpChess\Domain\Play\Challenge;

$defaultDays = min($playDefaults['days_per_move'], $maximumDays);
?>
<div class="button-row">
    <a class="button button-secondary" href="<?= e($basePath) ?>/play"><?= $template->render('partials/icon', ['icon' => 'back']) ?><?= e($t->text('play.title')) ?></a>
</div>
<h1><?= e($t->text('challenge.new')) ?></h1>

<form method="post" action="<?= e($basePath) ?>/challenges" class="form">
    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">

    <!-- ===== Who may accept ===== -->
    <fieldset class="choice-group">
        <legend><?= e($t->text('challenge.kind')) ?></legend>
        <?php foreach (['open', 'direct', 'invite'] as $index => $kind) : ?>
            <label class="choice"><input type="radio" name="kind" value="<?= e($kind) ?>"<?= $index === 0 ? ' checked' : '' ?>> <?= e($t->text('challenge.kind.' . $kind)) ?></label>
        <?php endforeach; ?>
    </fieldset>

    <label><?= e($t->text('challenge.opponent')) ?>
        <input type="text" name="opponent" list="opponent-names" autocomplete="off">
    </label>
    <datalist id="opponent-names">
        <?php foreach ($opponents as $name) : ?>
            <option value="<?= e($name) ?>"></option>
        <?php endforeach; ?>
    </datalist>
    <p class="hint"><?= e($t->text('challenge.opponent_hint')) ?></p>

    <!-- ===== Colour and pace ===== -->
    <fieldset class="choice-group">
        <legend><?= e($t->text('challenge.colour')) ?></legend>
        <?php foreach (Challenge::COLOURS as $colour) : ?>
            <label class="choice"><input type="radio" name="colour" value="<?= e($colour) ?>"<?= $colour === Challenge::COLOUR_RANDOM ? ' checked' : '' ?>> <?= e($t->text('challenge.colour.' . $colour)) ?></label>
        <?php endforeach; ?>
    </fieldset>
    <label><?= e($t->text('play.pace')) ?>
        <select name="time_control">
            <option value=""><?= e($t->text('play.pace_correspondence')) ?></option>
            <?php foreach ($timeControls as $control) : ?>
                <option value="<?= e($control) ?>"><?= e($t->text('play.pace_timed', ['control' => $control])) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label><?= e($t->text('play.days_per_move')) ?>: <output id="days-output"><?= e($defaultDays) ?></output>
        <input type="range" name="days_per_move" min="1" max="<?= e($maximumDays) ?>" value="<?= e($defaultDays) ?>" data-shows-in="days-output">
    </label>
    <label class="checkbox"><input type="checkbox" name="rated" value="yes" checked> <?= e($t->text('play.rated')) ?></label>

    <!-- ===== Own start position ===== -->
    <?php if ($isFenAllowed) : ?>
        <label><?= e($t->text('challenge.start_fen')) ?>
            <input type="text" name="start_fen" autocomplete="off" spellcheck="false">
        </label>
        <p class="hint"><?= e($t->text('challenge.start_fen_hint')) ?></p>
    <?php endif; ?>
    <button type="submit"><?= e($t->text('challenge.create')) ?></button>
</form>
<script src="<?= e($basePath) ?>/assets/js/play/range-output.js" defer></script>
