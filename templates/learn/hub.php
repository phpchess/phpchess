<?php
/**
 * The Learn page (C5-1, L-01, L-03): the way in to puzzles, study games
 * and Lichess TV.
 *
 * From top to bottom:
 * 1. big tiles: Puzzles, and Lichess TV while "external content" is on;
 * 2. the study themes the admins made (games to replay move by move);
 * 3. my own games: import one by its Lichess link (only while "external
 *    content" is on), or paste one as PGN (when the admin allows it, B37);
 *    only I see them (A88).
 *
 * Variables: $themes (list of array{id: int, title: string, section: string, topic: string, games: int}),
 * $sectionOrder (StudyTopics::SECTIONS, for the order of the headings),
 * $ownGames (list of array{id, white, black, event, played_on, result, opening}),
 * $lichessIsOn (bool), $importsAreOn (bool: Lichess imports on, A96), $pasteIsOpen (bool), $maxOwnGames (int), $maxBytes (int), $ownGameDays (int), $basePath, $csrfToken, $t, $template.
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
        <?php // Everything from lichess.org on one page: live games, broadcasts, the daily puzzle (A96). ?>
        <a class="play-tile play-tile-secondary" href="<?= e($basePath) ?>/learn/lichess"
           aria-label="<?= e($t->text('lichess.page_title')) ?>" title="<?= e($t->text('lichess.page_title')) ?>">
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
        <?php
        // Grouped by Lichess's sections, in Lichess's order (A95); older themes last.
        $bySection = [];
        foreach ($themes as $theme) {
            $bySection[$theme['section']][] = $theme;
        }
        ?>
        <?php foreach (array_merge(array_keys($sectionOrder), ['']) as $section) : ?>
            <?php if (isset($bySection[$section])) : ?>
                <h3><?= e($t->text($section === '' ? 'study_section.other' : 'study_section.' . $section)) ?></h3>
                <ul class="text-list">
                    <?php foreach ($bySection[$section] as $theme) : ?>
                        <li>
                            <a href="<?= e($basePath) ?>/learn/themes/<?= e($theme['id']) ?>"><?= e($theme['title']) ?></a>
                            <span class="muted">(<?= e($t->text('study.game_count', ['count' => $theme['games']])) ?>)</span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>
</section>

<?php // ===== 3. My own Lichess games ===== ?>
<section aria-labelledby="own-title">
    <h2 id="own-title"><?= e($t->text('study.own_games')) ?></h2>
    <?php if ($importsAreOn) : ?>
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
    <?php if ($pasteIsOpen) : ?>
        <details>
            <summary><?= e($t->text('study.paste_title')) ?></summary>
            <form method="post" action="<?= e($basePath) ?>/learn/paste" class="form">
                <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                <label>
                    <?= e($t->text('study.paste_label')) ?>
                    <textarea name="pgn" rows="8" required maxlength="<?= e($maxBytes) ?>" spellcheck="false" autocapitalize="off"></textarea>
                    <span class="hint"><?= e($t->text('study.paste_hint', ['max' => $maxOwnGames])) ?></span>
                </label>
                <p class="button-row"><button type="submit" class="button"><?= e($t->text('study.paste')) ?></button></p>
            </form>
        </details>
    <?php endif; ?>
    <?php if ($ownGames === []) : ?>
        <p class="muted"><?= e($t->text('study.no_own_games')) ?></p>
    <?php else : ?>
        <?= $template->render('learn/game_list', ['games' => $ownGames, 'ownGameDays' => $ownGameDays, 'basePath' => $basePath, 't' => $t]) ?>
    <?php endif; ?>
</section>

<p class="hint"><?= e($t->text('learn.credit')) ?></p>
