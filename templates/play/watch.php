<?php
/**
 * Running games of everyone, to watch (G-13).
 *
 * Variables: $games (list of Game), $usernames (array of id => name), $basePath, $t, $template.
 */
?>
<h1><?= e($t->text('watch.title')) ?></h1>
<?php if ($games === []) : ?>
    <p class="muted"><?= e($t->text('watch.none')) ?></p>
<?php else : ?>
    <ul class="card-list">
        <?php foreach ($games as $game) : ?>
            <?= $template->render('play/game_card', ['game' => $game, 'usernames' => $usernames, 'viewerId' => null, 'basePath' => $basePath, 't' => $t]) ?>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
