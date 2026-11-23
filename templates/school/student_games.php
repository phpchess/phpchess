<?php
/**
 * A student's games, for their teacher (SCH-06).
 *
 * Variables: $student (User), $running and $finished (lists of Game), $usernames (array of id => name),
 * $basePath, $t, $template.
 */
?>
<h1><?= e($t->text('school.games_of', ['name' => $student->username])) ?></h1>

<h2><?= e($t->text('games.running')) ?></h2>
<?php if ($running === []) : ?>
    <p class="muted"><?= e($t->text('school.no_games')) ?></p>
<?php else : ?>
    <ul class="card-list game-card-grid">
        <?php foreach ($running as $game) : ?>
            <?= $template->render('play/game_card', ['game' => $game, 'usernames' => $usernames, 'viewerId' => $student->id, 'basePath' => $basePath, 't' => $t]) ?>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<h2><?= e($t->text('games.archive')) ?></h2>
<?php if ($finished === []) : ?>
    <p class="muted"><?= e($t->text('school.no_games')) ?></p>
<?php else : ?>
    <ul class="text-list">
        <?php foreach ($finished as $game) : ?>
            <li><a href="<?= e($basePath) ?>/games/<?= e($game->id) ?>"><?= e(($usernames[$game->whiteId] ?? '?') . ' – ' . ($usernames[$game->blackId] ?? '?')) ?></a>
                <span class="muted"><?= e($game->result === null ? '' : $t->text('result.' . $game->result)) ?></span></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
