<?php
/**
 * The League editor (T-05, A15, A103; the F2 look: full width, buttons
 * first, icons drawn at runtime).
 *
 * One column per division, division 1 at the left, and a column of players
 * not placed yet. Players are cards: drag them between columns and up or
 * down (mouse or finger), or use the arrow buttons on each card, which is
 * easier on a small phone. Every change is saved at once and checked on
 * the server (LeagueDivisions::save); "Undo" goes back one change until
 * the season starts. The top seed of each division is at the top.
 *
 * Variables: $tournament (Tournament), $placement (array{divisions: array<int, list<TournamentPlayer>>,
 * unplaced: list<TournamentPlayer>}), $isEditable (bool), $basePath, $csrfToken, $t, $template.
 */

$tournamentPath = $basePath . '/tournaments/' . $tournament->id;
$columns = $placement['divisions'];
?>
<div class="league-editor-page">
    <h1><?= e($t->text('league.editor_title')) ?>: <?= e($tournament->name) ?></h1>
    <p class="muted">
        <?= e($t->text('league.season_number', ['season' => $tournament->season])) ?>
        · <?= e($t->text('league.promotion_rule', ['up' => $tournament->promoteCount, 'down' => $tournament->relegateCount])) ?>
        · <a href="<?= e($tournamentPath) ?>/manage"><?= e($t->text('tournament.back_to_desk')) ?></a>
    </p>

    <?php if (!$isEditable) : ?>
        <p class="notice-warning"><?= e($t->text('league.locked')) ?></p>
    <?php endif; ?>

    <?php // ===== The buttons ===== ?>
    <div class="button-row league-toolbar">
        <?php if ($isEditable) : ?>
            <button type="button" class="button button-secondary" data-league-undo disabled>
                <?= $template->render('partials/icon', ['icon' => 'undo']) ?> <?= e($t->text('league.undo')) ?></button>
            <form method="post" action="<?= e($tournamentPath) ?>/league/divisions/add" class="inline-form">
                <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                <button type="submit" class="button button-secondary"><?= $template->render('partials/icon', ['icon' => 'add']) ?> <?= e($t->text('league.division_add')) ?></button>
            </form>
            <?php if ($tournament->divisions > 1) : ?>
                <form method="post" action="<?= e($tournamentPath) ?>/league/divisions/remove" class="inline-form">
                    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                    <button type="submit" class="button button-secondary"><?= $template->render('partials/icon', ['icon' => 'remove']) ?> <?= e($t->text('league.division_remove')) ?></button>
                </form>
            <?php endif; ?>
            <form method="post" action="<?= e($tournamentPath) ?>/start" class="inline-form">
                <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                <button type="submit" class="button"><?= $template->render('partials/icon', ['icon' => 'play']) ?> <?= e($t->text('tournament.start')) ?></button>
            </form>
        <?php endif; ?>
        <span class="league-status muted" role="status" aria-live="polite" data-league-status></span>
    </div>

    <?php // ===== The divisions; the script reads the order back from the columns ===== ?>
    <div class="league-board"
         data-league-editor
         data-save-url="<?= e($tournamentPath) ?>/league"
         data-csrf-token="<?= e($csrfToken) ?>"
         data-editable="<?= $isEditable ? 'yes' : 'no' ?>"
         data-text-saved="<?= e($t->text('league.saved')) ?>"
         data-text-saving="<?= e($t->text('league.saving')) ?>"
         data-text-failed="<?= e($t->text('league.save_failed')) ?>">
        <?php foreach ($columns as $division => $players) : ?>
            <section class="league-column" aria-labelledby="division-<?= e($division) ?>">
                <h2 id="division-<?= e($division) ?>"><?= e($t->text('league.division_number', ['division' => $division])) ?>
                    <span class="muted" data-league-count></span></h2>
                <ol class="league-list" data-league-list data-division="<?= e($division) ?>">
                    <?php foreach ($players as $player) : ?>
                        <?= $template->render('tournaments/league_card', ['player' => $player, 'isEditable' => $isEditable, 't' => $t]) ?>
                    <?php endforeach; ?>
                </ol>
            </section>
        <?php endforeach; ?>
        <section class="league-column league-column-unplaced" aria-labelledby="division-unplaced">
            <h2 id="division-unplaced"><?= e($t->text('league.unplaced')) ?> <span class="muted" data-league-count></span></h2>
            <ol class="league-list" data-league-list data-division="0">
                <?php foreach ($placement['unplaced'] as $player) : ?>
                    <?= $template->render('tournaments/league_card', ['player' => $player, 'isEditable' => $isEditable, 't' => $t]) ?>
                <?php endforeach; ?>
            </ol>
        </section>
    </div>
    <p class="hint"><?= e($t->text('league.hint')) ?></p>
</div>

<?php if ($isEditable) : ?>
    <script src="<?= e($basePath) ?>/assets/vendor/sortablejs/Sortable.min.js"></script>
    <script type="module" src="<?= e($basePath) ?>/assets/js/tournament/league-editor.js"></script>
<?php endif; ?>
