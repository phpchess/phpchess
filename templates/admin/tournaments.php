<?php
/**
 * The admin's tournament page (ADM-06, A98, A107, A108): open disputes of
 * every tournament first (with the game one tap away), then every
 * tournament with a link to its desk, then who may organise.
 *
 * Variables: $tournaments (list of Tournament), $tournamentsById (id => Tournament),
 * $disputes (list of open dispute arrays), $disputeBoards (dispute id => ?Pairing),
 * $names (id => name), $organisers (id => name),
 * $warnings, $currentAdminPath, $basePath, $csrfToken, $t, $template.
 */
?>
<?= $template->render('admin/header', ['warnings' => $warnings, 'currentAdminPath' => $currentAdminPath, 'basePath' => $basePath, 't' => $t, 'csrfToken' => $csrfToken]) ?>

<h1><?= e($t->text('admin.tournaments.title')) ?></h1>

<h2><?= e($t->text('admin.tournaments.disputes')) ?></h2>
<?php if ($disputes === []) : ?>
    <p class="muted"><?= e($t->text('tournament.no_disputes')) ?></p>
<?php endif; ?>
<ul class="card-list">
    <?php foreach ($disputes as $dispute) : ?>
        <?php $tournament = $tournamentsById[$dispute['tournamentId']] ?? null; ?>
        <?php $board = $disputeBoards[$dispute['id']] ?? null; ?>
        <li class="card card-mine">
            <p class="card-title"><?= e($tournament === null ? '#' . $dispute['tournamentId'] : $tournament->name) ?>
                <?php if ($board !== null) : ?>· <?= e($t->text('tournament.round_number', ['round' => $board->round])) ?>, #<?= e($board->board) ?><?php endif; ?></p>
            <p><?= e($t->text('tournament.dispute_by', ['name' => $names[$dispute['raisedBy']] ?? '?'])) ?></p>
            <p class="keeps-lines"><?= e($dispute['reason']) ?></p>
            <div class="card-actions">
                <?php if ($board !== null && $board->gameId !== null) : ?>
                    <a class="button button-secondary" href="<?= e($basePath) ?>/games/<?= e($board->gameId) ?>"><?= $template->render('partials/icon', ['icon' => 'tv']) ?> <?= e($t->text('tournament.open_game')) ?></a>
                <?php endif; ?>
                <a class="button" href="<?= e($basePath) ?>/tournaments/<?= e($dispute['tournamentId']) ?>/manage?view=disputes"><?= e($t->text('tournament.dispute_answer')) ?></a>
            </div>
        </li>
    <?php endforeach; ?>
</ul>

<h2><?= e($t->text('admin.tournaments.all')) ?></h2>
<div class="table-wrap">
    <table class="data-table">
        <thead>
            <tr>
                <th scope="col">#</th>
                <th scope="col"><?= e($t->text('tournament.field.name')) ?></th>
                <th scope="col"><?= e($t->text('tournament.field.format')) ?></th>
                <th scope="col"><?= e($t->text('tournament.status')) ?></th>
                <th scope="col"><span class="visually-hidden"><?= e($t->text('tournament.actions')) ?></span></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($tournaments as $tournament) : ?>
                <tr>
                    <td><?= e($tournament->id) ?></td>
                    <td><a href="<?= e($basePath) ?>/tournaments/<?= e($tournament->id) ?>"><?= e($tournament->name) ?></a></td>
                    <td><?= e($t->text('tournament.format.' . $tournament->format)) ?></td>
                    <td><?= e($t->text('tournament.status.' . $tournament->status)) ?><?= $tournament->isPaused ? ' ⏸' : '' ?></td>
                    <td><a class="button button-secondary" href="<?= e($basePath) ?>/tournaments/<?= e($tournament->id) ?>/manage"><?= e($t->text('tournament.manage')) ?></a></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<h2><?= e($t->text('admin.tournaments.organisers')) ?></h2>
<p class="hint"><?= e($t->text('admin.tournaments.organisers_hint')) ?></p>
<ul class="text-list">
    <?php foreach ($organisers as $userId => $username) : ?>
        <li>
            <?= e($username) ?>
            <form method="post" action="<?= e($basePath) ?>/admin/tournaments/organisers/<?= e($userId) ?>/remove" class="inline-form">
                <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                <button type="submit" class="button button-secondary icon-button" aria-label="<?= e($t->text('tournament.remove')) ?>" title="<?= e($t->text('tournament.remove')) ?>"><?= $template->render('partials/icon', ['icon' => 'remove']) ?></button>
            </form>
        </li>
    <?php endforeach; ?>
</ul>
<form method="post" action="<?= e($basePath) ?>/admin/tournaments/organisers" class="form form-row">
    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
    <label><?= e($t->text('tournament.username')) ?><input type="text" name="username" required autocomplete="off"></label>
    <button type="submit" class="button"><?= e($t->text('admin.tournaments.organiser_add')) ?></button>
</form>
