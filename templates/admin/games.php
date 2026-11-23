<?php
/**
 * The admin's games page (ADM-04, A37): running games with adjudication,
 * recently finished games, and setting up a game from a FEN.
 *
 * Variables: $runningGames and $finishedGames (list of Game),
 * $usernames (array of id => name), $reasons (list of string),
 * $players (array of id => name), $maximumDays (int),
 * $warnings, $currentAdminPath, $basePath, $csrfToken, $t, $template.
 */

use PhpChess\Domain\Play\Game;

?>
<?= $template->render('admin/header', ['warnings' => $warnings, 'currentAdminPath' => $currentAdminPath, 'basePath' => $basePath, 't' => $t]) ?>

<h1><?= e($t->text('admin.games.title')) ?></h1>

<h2><?= e($t->text('games.running')) ?></h2>
<?php if ($runningGames === []) : ?>
    <p class="muted"><?= e($t->text('watch.none')) ?></p>
<?php endif; ?>
<ul class="card-list">
    <?php foreach ($runningGames as $game) : ?>
        <li class="card">
            <a class="card-title" href="<?= e($basePath) ?>/games/<?= e($game->id) ?>">#<?= e($game->id) ?> <?= e($usernames[$game->whiteId] ?? '?') ?> – <?= e($usernames[$game->blackId] ?? '?') ?></a>
            <p class="muted"><?= e($t->text('game.move_count', ['count' => intdiv($game->ply + 1, 2)])) ?></p>
            <form method="post" action="<?= e($basePath) ?>/admin/games/<?= e($game->id) ?>/adjudicate" class="form form-row">
                <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                <label><?= e($t->text('admin.games.result')) ?>
                    <select name="result">
                        <?php foreach ([Game::RESULT_WHITE_WON, Game::RESULT_BLACK_WON, Game::RESULT_DRAW, Game::RESULT_ABORTED] as $result) : ?>
                            <option value="<?= e($result) ?>"><?= e($t->text('result.' . $result)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label><?= e($t->text('admin.games.reason')) ?>
                    <select name="reason">
                        <?php foreach ($reasons as $reason) : ?>
                            <option value="<?= e($reason) ?>"><?= e(str_replace('_', ' ', $reason)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <button type="submit"><?= e($t->text('admin.games.adjudicate')) ?></button>
            </form>
        </li>
    <?php endforeach; ?>
</ul>

<h2><?= e($t->text('admin.games.new')) ?></h2>
<form method="post" action="<?= e($basePath) ?>/admin/games/new" class="form">
    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
    <?php foreach (['white_id' => 'admin.games.white', 'black_id' => 'admin.games.black'] as $field => $labelKey) : ?>
        <label><?= e($t->text($labelKey)) ?>
            <select name="<?= e($field) ?>">
                <?php foreach ($players as $playerId => $playerName) : ?>
                    <option value="<?= e($playerId) ?>"><?= e($playerName) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
    <?php endforeach; ?>
    <label><?= e($t->text('play.days_per_move')) ?>
        <input type="number" name="days_per_move" min="1" max="<?= e($maximumDays) ?>" value="3">
    </label>
    <label class="checkbox"><input type="checkbox" name="rated" value="yes"> <?= e($t->text('play.rated')) ?></label>
    <label><?= e($t->text('challenge.start_fen')) ?>
        <input type="text" name="start_fen" autocomplete="off" spellcheck="false">
    </label>
    <p class="hint"><?= e($t->text('challenge.start_fen_hint')) ?></p>
    <button type="submit"><?= e($t->text('admin.games.create')) ?></button>
</form>

<h2><?= e($t->text('games.archive')) ?></h2>
<ul class="card-list">
    <?php foreach ($finishedGames as $game) : ?>
        <?= $template->render('play/game_card', ['game' => $game, 'usernames' => $usernames, 'viewerId' => null, 'basePath' => $basePath, 't' => $t]) ?>
    <?php endforeach; ?>
</ul>
