<?php
/**
 * The game replay's parts (B44, B45, B47, B48): the board, the comment
 * line, the seven controls and the move table. assets/js/learn/replay.js
 * runs them; the page's own script starts it (study-game.js, tv.js).
 *
 * The controls, from left to right:
 *   |<< start, << back one move, < play backwards, = stop,
 *   > play forward, >> forward one move, >>| end (on TV: the live game).
 *
 * Layout (play.css): the board always fits the screen with a margin, and
 * the move table sits beside it (below it on a phone held upright); only
 * the table scrolls, never the page.
 *
 * Variables: $replayId (string, the element's id), $boardTheme (string),
 * $isLive (bool: Lichess TV), $basePath, $t, $template.
 */
$controls = [
    'first' => 'replay.first',
    'back' => 'replay.back',
    'play-back' => 'replay.play_back',
    'stop' => 'replay.stop',
    'play' => 'replay.play',
    'forward' => 'replay.forward',
    'last' => $isLive ? 'replay.live' : 'replay.last',
];
?>
<div class="replay" id="<?= e($replayId) ?>" data-base-path="<?= e($basePath) ?>">
<div class="replay-layout">
    <div class="replay-board">
        <div class="board-frame board-theme-<?= e($boardTheme) ?>" data-replay-board></div>
        <p class="replay-comment" data-replay-comment aria-live="polite"></p>
        <p class="replay-controls" role="group" aria-label="<?= e($t->text('replay.controls')) ?>">
            <?php foreach ($controls as $control => $labelKey) : ?>
                <button type="button" class="icon-button" data-replay="<?= e($control) ?>" aria-label="<?= e($t->text($labelKey)) ?>" title="<?= e($t->text($labelKey)) ?>">
                    <?= $template->render('partials/icon', ['icon' => 'replay-' . $control]) ?>
                </button>
            <?php endforeach; ?>
        </p>
    </div>

    <?php // ===== The move table: number, White, Black (B44) ===== ?>
    <div class="replay-table-box">
        <table class="replay-table">
            <caption class="visually-hidden"><?= e($t->text('study.moves')) ?></caption>
            <thead>
                <tr><th scope="col">#</th><th scope="col"><?= e($t->text('replay.white')) ?></th><th scope="col"><?= e($t->text('replay.black')) ?></th></tr>
            </thead>
            <tbody data-replay-moves></tbody>
        </table>
    </div>
</div>
</div>
