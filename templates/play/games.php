<?php
/**
 * My games (G-11): the running ones, then the archive of finished games.
 *
 * Variables: $runningGames and $finishedGames (list of Game),
 * $usernames (array of id => name), $viewerId (int), $basePath, $t, $template.
 */
?>
<h1><?= e($t->text('games.title')) ?></h1>

<h2><?= e($t->text('games.running')) ?></h2>
<?php if ($runningGames === []) : ?>
    <p class="muted"><?= e($t->text('play.no_games')) ?> <a href="<?= e($basePath) ?>/play"><?= e($t->text('play.title')) ?></a></p>
<?php else : ?>
    <ul class="card-list">
        <?php foreach ($runningGames as $game) : ?>
            <?= $template->render('play/game_card', ['game' => $game, 'usernames' => $usernames, 'viewerId' => $viewerId, 'basePath' => $basePath, 't' => $t]) ?>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<h2><?= e($t->text('games.archive')) ?></h2>
<?php if ($finishedGames === []) : ?>
    <p class="muted"><?= e($t->text('games.archive_empty')) ?></p>
<?php else : ?>
    <ul class="card-list">
        <?php foreach ($finishedGames as $game) : ?>
            <?= $template->render('play/game_card', ['game' => $game, 'usernames' => $usernames, 'viewerId' => $viewerId, 'basePath' => $basePath, 't' => $t]) ?>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
