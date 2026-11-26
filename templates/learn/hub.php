<?php
/**
 * The Learn page (C5-1, L-01, L-03): the way in to puzzles, study games
 * and Lichess TV.
 *
 * From top to bottom:
 * 1. big tiles: Puzzles, and Lichess TV while "external content" is on;
 * 2. the study themes the admins made (games to replay move by move);
 * 3. my own Lichess games: import one by its link (only while "external
 *    content" is on); only I see them (A88).
 *
 * Variables: $themes (list of array{id: int, title: string, games: int}),
 * $ownGames (list of array{id, white, black, event, played_on, result, opening}),
 * $lichessIsOn (bool), $maxOwnGames (int), $basePath, $csrfToken, $t, $template.
 */
?>
<h1><?= e($t->text('learn.title')) ?></h1>

<?php // ===== 1. The tiles (A84: icons over text; the name is read out and shown on pointing) ===== ?>
<div class="play-tiles">
    <a class="play-tile" href="<?= e($basePath) ?>/learn/puzzles"
       aria-label="<?= e($t->text('puzzles.title')) ?>" title="<?= e($t->text('puzzles.title')) ?>">
        <?= $template->render('partials/icon', ['icon' => 'puzzle', 'size' => 64]) ?>
    </a>
    <?php if ($lichessIsOn) : ?>
        <a class="play-tile play-tile-secondary" href="<?= e($basePath) ?>/learn/tv"
           aria-label="<?= e($t->text('tv.title')) ?>" title="<?= e($t->text('tv.title')) ?>">
            <?= $template->render('partials/icon', ['icon' => 'tv', 'size' => 64]) ?>
        </a>
    <?php endif; ?>
</div>

<?php // ===== 2. Study themes ===== ?>
<section aria-labelledby="themes-title">
    <h2 id="themes-title"><?= e($t->text('study.themes')) ?></h2>
    <?php if ($themes === []) : ?>
        <p class="muted"><?= e($t->text('study.no_themes')) ?></p>
    <?php else : ?>
        <ul class="text-list">
            <?php foreach ($themes as $theme) : ?>
                <li>
                    <a href="<?= e($basePath) ?>/learn/themes/<?= e($theme['id']) ?>"><?= e($theme['title']) ?></a>
                    <span class="muted">(<?= e($t->text('study.game_count', ['count' => $theme['games']])) ?>)</span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

<?php // ===== 3. My own Lichess games ===== ?>
<section aria-labelledby="own-title">
    <h2 id="own-title"><?= e($t->text('study.own_games')) ?></h2>
    <?php if ($lichessIsOn) : ?>
        <form method="post" action="<?= e($basePath) ?>/learn/import" class="form">
            <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
            <label>
                <?= e($t->text('study.import_link')) ?>
                <input type="url" name="link" required maxlength="80" inputmode="url" placeholder="https://lichess.org/abcdEFGH">
                <span class="hint"><?= e($t->text('study.import_hint', ['max' => $maxOwnGames])) ?></span>
            </label>
            <p class="button-row">
                <button type="submit" class="button"><?= $template->render('partials/icon', ['icon' => 'upload', 'size' => 20]) ?> <?= e($t->text('study.import')) ?></button>
            </p>
        </form>
    <?php endif; ?>
    <?php if ($ownGames === []) : ?>
        <p class="muted"><?= e($t->text('study.no_own_games')) ?></p>
    <?php else : ?>
        <?= $template->render('learn/game_list', ['games' => $ownGames, 'basePath' => $basePath, 't' => $t]) ?>
    <?php endif; ?>
</section>

<p class="hint"><?= e($t->text('learn.credit')) ?></p>
