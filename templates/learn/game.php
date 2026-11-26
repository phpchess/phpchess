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
 * $positions (list of array{fen: string, san: string, comment: string}),
 * $isComplete (bool: false when the board couldn't follow every move, B39),
 * $isOwn (bool: the viewer's own game, with Keep and Remove), $ownGameDays (int, 0 = never removed),
 * $lichessLink (string, '' for an upload),
 * $boardTheme (string), $backPath (string, optional), $basePath, $csrfToken, $t, $template.
 */
// Back to the theme, to Learn, or where the page was opened from ($backPath, broadcasts).
$backPath ??= $theme === null ? '/learn' : '/learn/themes/' . $theme['id'];
?>
<p><a class="icon-button" href="<?= e($basePath . $backPath) ?>" aria-label="<?= e($theme['title'] ?? $t->text('learn.title')) ?>" title="<?= e($theme['title'] ?? $t->text('learn.title')) ?>"><?= $template->render('partials/icon', ['icon' => 'back']) ?></a></p>

<h1><?= e(($game['white'] !== '' ? $game['white'] : '?') . ' – ' . ($game['black'] !== '' ? $game['black'] : '?')) ?> <span class="muted"><?= e($game['result']) ?></span></h1>
<p class="muted"><?= e(implode(' · ', array_filter([$game['opening'], $game['event'], $game['played_on']], static fn (string $part): bool => $part !== '' && !str_contains($part, '?')))) ?></p>

<div class="study-game" id="study-game" data-base-path="<?= e($basePath) ?>" data-positions="<?= e((string) json_encode($positions)) ?>">
    <div class="board-frame board-theme-<?= e($boardTheme) ?>" id="study-game-board"></div>

    <p class="study-game-comment" id="study-game-comment" aria-live="polite"></p>

    <p class="button-row study-game-buttons">
        <?php foreach (['first' => 'study.first', 'previous' => 'study.previous', 'next' => 'study.next', 'last' => 'study.last'] as $icon => $labelKey) : ?>
            <button type="button" class="icon-button" data-step="<?= e($icon) ?>" aria-label="<?= e($t->text($labelKey)) ?>" title="<?= e($t->text($labelKey)) ?>">
                <?= $template->render('partials/icon', ['icon' => $icon]) ?>
            </button>
        <?php endforeach; ?>
    </p>

    <ol class="study-game-moves" id="study-game-moves" aria-label="<?= e($t->text('study.moves')) ?>"></ol>
</div>

<?php if ($isOwn) : ?>
    <?php // A player's own game: when it goes, and the Keep and Remove buttons (B37, A92). ?>
    <p class="muted">
        <?php if ($game['is_kept'] || $ownGameDays === 0) : ?>
            <?= e($t->text('study.stays')) ?>
        <?php else : ?>
            <?= e($t->text('study.removed_on', ['date' => gmdate('Y-m-d', $game['created_at'] + $ownGameDays * 86400)])) ?>
        <?php endif; ?>
    </p>
    <div class="button-row">
        <form method="post" action="<?= e($basePath) ?>/learn/games/<?= e($game['id']) ?>/keep" class="inline-form">
            <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
            <button type="submit" class="button button-secondary"><?= e($t->text($game['is_kept'] ? 'study.stop_keeping' : 'study.keep')) ?></button>
        </form>
        <form method="post" action="<?= e($basePath) ?>/learn/games/<?= e($game['id']) ?>/remove" class="inline-form">
            <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
            <button type="submit" class="icon-button" aria-label="<?= e($t->text('study.remove')) ?>" title="<?= e($t->text('study.remove')) ?>"><?= $template->render('partials/icon', ['icon' => 'remove']) ?></button>
        </form>
    </div>
<?php endif; ?>

<?php if (!$isComplete) : ?>
    <p class="notice notice-error"><?= e($t->text('study.incomplete')) ?></p>
<?php endif; ?>

<details>
    <summary><?= e($t->text('study.pgn')) ?></summary>
    <pre class="study-game-pgn" id="study-game-pgn"><?= e($game['pgn']) ?></pre>
    <p class="button-row">
        <button type="button" class="button button-secondary" id="study-game-copy" data-copied="<?= e($t->text('study.copied')) ?>"><?= e($t->text('study.copy_pgn')) ?></button>
    </p>
</details>

<?php if ($lichessLink !== '') : ?>
    <p><a href="<?= e($lichessLink) ?>" target="_blank" rel="noopener noreferrer"><?= $template->render('partials/icon', ['icon' => 'link', 'size' => 20]) ?> <?= e($t->text('study.on_lichess')) ?></a></p>
<?php endif; ?>

<link rel="stylesheet" href="<?= e($basePath) ?>/assets/vendor/cm-chessboard/assets/chessboard.css">
<script type="module" src="<?= e($basePath) ?>/assets/js/learn/study-game.js"></script>
