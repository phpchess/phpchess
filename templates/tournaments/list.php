<?php
/**
 * The tournament list (C4-1, T-01, A107): my tournaments first, then those
 * open for joining, running and finished. Closed tournaments of others are
 * not listed (A106). A big "New tournament" tile for those who may organise.
 *
 * Variables: $mine, $open, $running, $finished (list of Tournament),
 * $mayCreate (bool), $basePath, $t, $template.
 */

use PhpChess\Domain\Tournament\Tournament;

$formatIcon = [
    Tournament::FORMAT_ROUND_ROBIN => 'circle',
    Tournament::FORMAT_SWISS => 'swiss',
    Tournament::FORMAT_KNOCKOUT => 'bracket',
    Tournament::FORMAT_LEAGUE => 'league',
];
?>
<h1><?= e($t->text('tournament.list_title')) ?></h1>

<?php if ($mayCreate) : ?>
    <div class="play-tiles">
        <a class="play-tile" href="<?= e($basePath) ?>/tournaments/new"
           aria-label="<?= e($t->text('tournament.create_title')) ?>" title="<?= e($t->text('tournament.create_title')) ?>">
            <?= $template->render('partials/icon', ['icon' => 'add', 'size' => 64]) ?>
        </a>
    </div>
<?php endif; ?>

<?php foreach (['mine' => $mine, 'open' => $open, 'running' => $running, 'finished' => $finished] as $group => $tournaments) : ?>
    <?php if ($tournaments === [] && $group !== 'open') {
        continue;
    } ?>
    <section aria-labelledby="group-<?= e($group) ?>">
        <h2 id="group-<?= e($group) ?>"><?= e($t->text('tournament.group.' . $group)) ?></h2>
        <?php if ($tournaments === []) : ?>
            <p class="muted"><?= e($t->text('tournament.none_open')) ?></p>
        <?php endif; ?>
        <ul class="card-list">
            <?php foreach ($tournaments as $tournament) : ?>
                <li class="card">
                    <a class="card-title" href="<?= e($basePath) ?>/tournaments/<?= e($tournament->id) ?>">
                        <?= $template->render('partials/icon', ['icon' => $formatIcon[$tournament->format] ?? 'trophy']) ?>
                        <?= e($tournament->name) ?>
                    </a>
                    <p class="muted">
                        <?= e($t->text('tournament.format.' . $tournament->format)) ?>
                        · <?= e($tournament->isTimed() ? $tournament->timeControl : $t->text('new_game.days', ['days' => $tournament->daysPerMove])) ?>
                        · <?= e($t->text('tournament.status.' . $tournament->status)) ?>
                        <?php if ($tournament->isRunning() && $tournament->currentRound > 0) : ?>
                            · <?= e($t->text('tournament.round_number', ['round' => $tournament->currentRound])) ?>
                        <?php endif; ?>
                        <?php if ($tournament->format === Tournament::FORMAT_LEAGUE) : ?>
                            · <?= e($t->text('league.season_number', ['season' => $tournament->season])) ?>
                        <?php endif; ?>
                    </p>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endforeach; ?>
