<?php
/**
 * One study game on a board, move by move (C5-1, L-03).
 *
 * The board and the four buttons (start, back, forward, end) are run by
 * study-game.js; the move list below is a row of buttons too, so a tap on
 * a move jumps there. Without JavaScript the PGN is still shown.
 * A game from Lichess gets a plain link to it on lichess.org (A75): a link,
 * not a request, so it works the same with the switch off.
 *
 * Variables: $game (array from StudyRepository::findGame), $theme (array{id, title}|null),
 * $positions (list of array{fen: string, san: string}), $lichessLink (string, '' for an upload),
 * $boardTheme (string), $basePath, $t, $template.
 */
$backPath = $theme === null ? '/learn' : '/learn/themes/' . $theme['id'];
?>
<p><a class="icon-button" href="<?= e($basePath . $backPath) ?>" aria-label="<?= e($theme['title'] ?? $t->text('learn.title')) ?>" title="<?= e($theme['title'] ?? $t->text('learn.title')) ?>"><?= $template->render('partials/icon', ['icon' => 'back']) ?></a></p>

<h1><?= e(($game['white'] !== '' ? $game['white'] : '?') . ' – ' . ($game['black'] !== '' ? $game['black'] : '?')) ?> <span class="muted"><?= e($game['result']) ?></span></h1>
<p class="muted"><?= e(implode(' · ', array_filter([$game['opening'], $game['event'], $game['played_on']], static fn (string $part): bool => $part !== '' && !str_contains($part, '?')))) ?></p>

<div class="study-game" id="study-game" data-base-path="<?= e($basePath) ?>" data-positions="<?= e((string) json_encode($positions)) ?>">
    <div class="board-frame board-theme-<?= e($boardTheme) ?>" id="study-game-board"></div>

    <p class="button-row study-game-buttons">
        <?php foreach (['first' => 'study.first', 'previous' => 'study.previous', 'next' => 'study.next', 'last' => 'study.last'] as $icon => $labelKey) : ?>
            <button type="button" class="icon-button" data-step="<?= e($icon) ?>" aria-label="<?= e($t->text($labelKey)) ?>" title="<?= e($t->text($labelKey)) ?>">
                <?= $template->render('partials/icon', ['icon' => $icon]) ?>
            </button>
        <?php endforeach; ?>
    </p>

    <ol class="study-game-moves" id="study-game-moves" aria-label="<?= e($t->text('study.moves')) ?>"></ol>
</div>

<details>
    <summary><?= e($t->text('study.pgn')) ?></summary>
    <pre class="study-game-pgn"><?= e($game['pgn']) ?></pre>
</details>

<?php if ($lichessLink !== '') : ?>
    <p><a href="<?= e($lichessLink) ?>" target="_blank" rel="noopener noreferrer"><?= $template->render('partials/icon', ['icon' => 'link', 'size' => 20]) ?> <?= e($t->text('study.on_lichess')) ?></a></p>
<?php endif; ?>

<link rel="stylesheet" href="<?= e($basePath) ?>/assets/vendor/cm-chessboard/assets/chessboard.css">
<script type="module" src="<?= e($basePath) ?>/assets/js/learn/study-game.js"></script>
