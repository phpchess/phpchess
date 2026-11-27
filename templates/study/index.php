<?php
/**
 * The study board list (C6-1, A133, A136).
 *
 * From top to bottom:
 * 1. a new empty board, and a PGN import (paste or file) when the member may make boards;
 * 2. my own boards;
 * 3. public boards (approved by an admin);
 * 4. for admins: public boards waiting for approval (A133).
 *
 * Variables: $ownBoards, $publicBoards, $waitingBoards (lists of boards from
 * StudyBoardRepository), $mayCreate (bool), $maxBytes (int), $basePath, $csrfToken, $t, $template.
 */

?>
<p><a class="icon-button" href="<?= e($basePath) ?>/learn" aria-label="<?= e($t->text('learn.title')) ?>" title="<?= e($t->text('learn.title')) ?>"><?= $template->render('partials/icon', ['icon' => 'back']) ?></a></p>

<h1><?= e($t->text('study_board.title')) ?></h1>
<p class="muted"><?= e($t->text('study_board.intro')) ?></p>

<?php // ===== 1. New board, import ===== ?>
<?php if ($mayCreate) : ?>
    <div class="button-row">
        <form method="post" action="<?= e($basePath) ?>/learn/boards/new" class="inline-form">
            <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
            <button type="submit" class="button"><?= $template->render('partials/icon', ['icon' => 'add']) ?> <?= e($t->text('study_board.new')) ?></button>
        </form>
    </div>
    <details>
        <summary><?= e($t->text('study_board.import')) ?></summary>
        <form method="post" action="<?= e($basePath) ?>/learn/boards/import" enctype="multipart/form-data" class="stack-form">
            <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
            <label><?= e($t->text('study_board.paste_pgn')) ?>
                <textarea name="pgn" rows="6" maxlength="<?= e($maxBytes) ?>"></textarea>
            </label>
            <label><?= e($t->text('study_board.pgn_file')) ?> <input type="file" name="pgn_file" accept=".pgn,text/plain"></label>
            <p class="muted"><?= e($t->text('study_board.import_note')) ?></p>
            <p class="button-row"><button type="submit" class="button"><?= $template->render('partials/icon', ['icon' => 'upload']) ?> <?= e($t->text('study_board.import_button')) ?></button></p>
        </form>
    </details>
<?php else : ?>
    <p class="muted"><?= e($t->text('study_board.view_only')) ?></p>
<?php endif; ?>

<?php // ===== 2. My boards ===== ?>
<section aria-labelledby="own-boards-title">
    <h2 id="own-boards-title"><?= e($t->text('study_board.own')) ?></h2>
    <?php if ($ownBoards === []) : ?>
        <p class="muted"><?= e($t->text('study_board.none')) ?></p>
    <?php else : ?>
        <ul class="text-list">
            <?php foreach ($ownBoards as $board) : ?>
                <li>
                    <a href="<?= e($basePath) ?>/learn/boards/<?= e($board['id']) ?>"><?= e($board['title']) ?></a>
                    <?php if ($board['visibility'] === 'public') : ?>
                        <span class="muted">(<?= e($t->text($board['is_approved'] ? 'study_board.public' : 'study_board.waiting')) ?>)</span>
                    <?php endif; ?>
                    <span class="muted"><?= e(gmdate('Y-m-d', $board['updated_at'])) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

<?php // ===== 3. Public boards ===== ?>
<section aria-labelledby="public-boards-title">
    <h2 id="public-boards-title"><?= e($t->text('study_board.public_boards')) ?></h2>
    <?php if ($publicBoards === []) : ?>
        <p class="muted"><?= e($t->text('study_board.none')) ?></p>
    <?php else : ?>
        <ul class="text-list">
            <?php foreach ($publicBoards as $board) : ?>
                <li>
                    <a href="<?= e($basePath) ?>/learn/boards/<?= e($board['id']) ?>"><?= e($board['title']) ?></a>
                    <span class="muted"><?= e($board['owner']) ?></span>
                    <?php if ($board['visibility'] === 'public') : ?>
                        <span class="muted">(<?= e($t->text($board['is_approved'] ? 'study_board.public' : 'study_board.waiting')) ?>)</span>
                    <?php endif; ?>
                    <span class="muted"><?= e(gmdate('Y-m-d', $board['updated_at'])) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

<?php // ===== 4. Waiting for an admin ===== ?>
<?php if ($waitingBoards !== []) : ?>
    <section aria-labelledby="waiting-boards-title">
        <h2 id="waiting-boards-title"><?= e($t->text('study_board.waiting_boards')) ?></h2>
        <ul class="text-list">
            <?php foreach ($waitingBoards as $board) : ?>
                <li>
                    <a href="<?= e($basePath) ?>/learn/boards/<?= e($board['id']) ?>"><?= e($board['title']) ?></a>
                    <span class="muted"><?= e($board['owner']) ?></span>
                    <form method="post" action="<?= e($basePath) ?>/learn/boards/<?= e($board['id']) ?>/approve" class="inline-form">
                        <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                        <button type="submit" class="icon-button" aria-label="<?= e($t->text('study_board.approve')) ?>" title="<?= e($t->text('study_board.approve')) ?>"><?= $template->render('partials/icon', ['icon' => 'check']) ?></button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endif; ?>
