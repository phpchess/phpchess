<?php
/**
 * One study board (C6-1, A129-A136): the board, the move tree with side
 * lines, and, for its owner, the tools to annotate.
 *
 * study-board.js runs it. From top to bottom:
 * 1. back, title;
 * 2. the board, full width on a phone (A18);
 * 3. the move buttons: start, back, forward, end, flip;
 * 4. the moves: the main line, side lines in brackets; a tap jumps there;
 * 5. the comment of the move on the board;
 * 6. owner only: symbols, drawing colours, comment, promote, delete;
 * 7. the PGN download (works without JavaScript), and the owner's settings.
 *
 * Drawing on a phone (A134): tap a colour, then tap a square for a circle,
 * or two squares for an arrow; the same drawing again removes it.
 *
 * Variables: $board (array from StudyBoardRepository::find), $tree (MoveTree::toList()),
 * $mayEdit, $isOwner, $isAdmin (bool), $moveSymbols, $positionSymbols, $allSymbols (code => sign),
 * $colours (letter => meaning), $maxComment (int), $boardTheme (string), $basePath, $csrfToken, $t, $template.
 */
$boardPath = $basePath . '/learn/boards/' . $board['id'];
$colourClasses = ['G' => 'good', 'R' => 'threat', 'Y' => 'alternative', 'B' => 'note'];
?>
<p><a class="icon-button" href="<?= e($basePath) ?>/learn/boards" aria-label="<?= e($t->text('study_board.title')) ?>" title="<?= e($t->text('study_board.title')) ?>"><?= $template->render('partials/icon', ['icon' => 'back']) ?></a></p>

<h1><?= e($board['title']) ?> <span class="muted"><?= e($board['result']) ?></span></h1>
<?php if (!$isOwner) : ?>
    <p class="muted"><?= e($t->text('study_board.by', ['name' => $board['owner']])) ?></p>
<?php endif; ?>

<div class="study-board" id="study-board"
     data-tree="<?= e((string) json_encode($tree)) ?>"
     data-symbols="<?= e((string) json_encode($allSymbols)) ?>"
     data-editable="<?= $mayEdit ? '1' : '0' ?>"
     data-save-url="<?= e($boardPath . '/save') ?>"
     data-csrf="<?= e($csrfToken) ?>"
     data-base-path="<?= e($basePath) ?>"
     data-text-saving="<?= e($t->text('study_board.saving')) ?>"
     data-text-saved="<?= e($t->text('study_board.saved')) ?>"
     data-text-save-failed="<?= e($t->text('study_board.save_failed')) ?>"
     data-text-start="<?= e($t->text('study_board.start')) ?>">

    <?php // ===== 2. The board ===== ?>
    <div class="study-board-frame board-theme-<?= e($boardTheme) ?>" data-study-board></div>

    <?php // ===== 3. Move buttons ===== ?>
    <div class="icon-bar needs-script">
        <?php foreach (['first' => 'replay-first', 'back' => 'replay-back', 'forward' => 'replay-forward', 'last' => 'replay-last'] as $action => $icon) : ?>
            <button type="button" class="icon-button" data-study-action="<?= e($action) ?>" title="<?= e($t->text('study_board.' . $action)) ?>" aria-label="<?= e($t->text('study_board.' . $action)) ?>"><?= $template->render('partials/icon', ['icon' => $icon]) ?></button>
        <?php endforeach; ?>
        <button type="button" class="icon-button" data-study-action="flip" title="<?= e($t->text('game.flip')) ?>" aria-label="<?= e($t->text('game.flip')) ?>"><?= $template->render('partials/icon', ['icon' => 'random']) ?></button>
    </div>

    <?php // ===== 4. The moves (drawn by study-board.js, as text only) ===== ?>
    <div class="study-moves needs-script" data-study-moves aria-live="polite"></div>

    <?php // ===== 5. The comment on the board ===== ?>
    <p class="study-comment needs-script" data-study-comment></p>

    <?php // ===== 6. Annotation tools (owner) ===== ?>
    <?php if ($mayEdit) : ?>
        <div class="study-tools needs-script">
            <p class="muted"><?= e($t->text('study_board.how_to')) ?></p>

            <div class="button-row" role="group" aria-label="<?= e($t->text('study_board.move_symbols')) ?>">
                <?php foreach ($moveSymbols as $code => $sign) : ?>
                    <button type="button" class="button button-secondary study-symbol" data-study-symbol="<?= e($code) ?>" aria-pressed="false" title="<?= e($t->text('study_symbol.' . $code)) ?>"><?= e($sign) ?></button>
                <?php endforeach; ?>
            </div>
            <div class="button-row" role="group" aria-label="<?= e($t->text('study_board.position_symbols')) ?>">
                <?php foreach ($positionSymbols as $code => $sign) : ?>
                    <button type="button" class="button button-secondary study-symbol" data-study-symbol="<?= e($code) ?>" aria-pressed="false" title="<?= e($t->text('study_symbol.' . $code)) ?>"><?= e($sign) ?></button>
                <?php endforeach; ?>
            </div>

            <div class="button-row" role="group" aria-label="<?= e($t->text('study_board.draw')) ?>">
                <?php foreach ($colours as $letter => $meaning) : ?>
                    <button type="button" class="icon-button study-colour study-colour-<?= e($colourClasses[$letter]) ?>" data-study-colour="<?= e($letter) ?>" aria-pressed="false" title="<?= e($t->text('study_colour.' . $meaning)) ?>" aria-label="<?= e($t->text('study_colour.' . $meaning)) ?>"><?= $template->render('partials/icon', ['icon' => 'arrow']) ?></button>
                <?php endforeach; ?>
                <button type="button" class="icon-button" data-study-action="erase" title="<?= e($t->text('study_board.erase')) ?>" aria-label="<?= e($t->text('study_board.erase')) ?>"><?= $template->render('partials/icon', ['icon' => 'erase']) ?></button>
            </div>

            <label class="study-comment-edit"><?= e($t->text('study_board.comment')) ?>
                <textarea rows="3" maxlength="<?= e($maxComment) ?>" data-study-comment-input></textarea>
            </label>

            <div class="button-row">
                <button type="button" class="button button-secondary" data-study-action="save-comment"><?= $template->render('partials/icon', ['icon' => 'comment']) ?> <?= e($t->text('study_board.save_comment')) ?></button>
                <button type="button" class="icon-button" data-study-action="promote" title="<?= e($t->text('study_board.promote')) ?>" aria-label="<?= e($t->text('study_board.promote')) ?>"><?= $template->render('partials/icon', ['icon' => 'promote']) ?></button>
                <button type="button" class="icon-button" data-study-action="delete" title="<?= e($t->text('study_board.delete')) ?>" aria-label="<?= e($t->text('study_board.delete')) ?>" data-confirm="<?= e($t->text('study_board.delete_confirm')) ?>"><?= $template->render('partials/icon', ['icon' => 'remove']) ?></button>
            </div>
            <p class="muted" data-study-status role="status"></p>
        </div>
    <?php endif; ?>
</div>

<?php // ===== 7. PGN, download, settings ===== ?>
<p class="button-row">
    <a class="icon-button" href="<?= e($boardPath) ?>/pgn" download title="<?= e($t->text('game.download_pgn')) ?>" aria-label="<?= e($t->text('game.download_pgn')) ?>"><?= $template->render('partials/icon', ['icon' => 'download']) ?></a>
</p>

<?php if ($isOwner && $mayEdit) : ?>
    <details>
        <summary><?= e($t->text('study_board.settings')) ?></summary>
        <form method="post" action="<?= e($boardPath) ?>/rename" class="stack-form">
            <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
            <label><?= e($t->text('study_board.name')) ?> <input type="text" name="title" maxlength="100" value="<?= e($board['title']) ?>" required></label>
            <p class="button-row"><button type="submit" class="button button-secondary"><?= e($t->text('study_board.rename')) ?></button></p>
        </form>
        <form method="post" action="<?= e($boardPath) ?>/visibility" class="stack-form">
            <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
            <fieldset>
                <legend><?= e($t->text('study_board.who_sees')) ?></legend>
                <label class="checkbox"><input type="radio" name="visibility" value="private"<?= $board['visibility'] !== 'public' ? ' checked' : '' ?>> <?= e($t->text('study_board.private')) ?></label>
                <label class="checkbox"><input type="radio" name="visibility" value="public"<?= $board['visibility'] === 'public' ? ' checked' : '' ?>> <?= e($t->text('study_board.make_public')) ?></label>
            </fieldset>
            <?php if ($board['visibility'] === 'public' && !$board['is_approved']) : ?>
                <p class="muted"><?= e($t->text('study_board.waits_for_admin')) ?></p>
            <?php endif; ?>
            <p class="button-row"><button type="submit" class="button button-secondary"><?= e($t->text('study_board.save_settings')) ?></button></p>
        </form>
    </details>
<?php endif; ?>

<?php if ($isOwner || $isAdmin) : ?>
    <form method="post" action="<?= e($boardPath) ?>/remove" class="inline-form" data-confirm="<?= e($t->text('study_board.remove_confirm')) ?>">
        <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
        <button type="submit" class="button button-secondary"><?= $template->render('partials/icon', ['icon' => 'remove']) ?> <?= e($t->text('study_board.remove')) ?></button>
    </form>
<?php endif; ?>

<link rel="stylesheet" href="<?= e($basePath) ?>/assets/vendor/cm-chessboard/assets/chessboard.css">
<link rel="stylesheet" href="<?= e($basePath) ?>/assets/vendor/cm-chessboard/assets/extensions/markers/markers.css">
<link rel="stylesheet" href="<?= e($basePath) ?>/assets/vendor/cm-chessboard/assets/extensions/arrows/arrows.css">
<link rel="stylesheet" href="<?= e($basePath) ?>/assets/vendor/cm-chessboard/assets/extensions/promotion-dialog/promotion-dialog.css">
<script type="module" src="<?= e($basePath) ?>/assets/js/study/study-board.js?v=1"></script>
