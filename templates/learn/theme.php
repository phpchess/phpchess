<?php
/**
 * One study theme (C5-1, L-03): its title and its games.
 *
 * Variables: $theme (array{id: int, title: string}), $games (list, see
 * learn/game_list), $basePath, $t, $template.
 */
?>
<p><a class="icon-button" href="<?= e($basePath) ?>/learn" aria-label="<?= e($t->text('learn.title')) ?>" title="<?= e($t->text('learn.title')) ?>"><?= $template->render('partials/icon', ['icon' => 'back']) ?></a></p>
<h1><?= e($theme['title']) ?></h1>

<?php if ($games === []) : ?>
    <p class="muted"><?= e($t->text('study.empty_theme')) ?></p>
<?php else : ?>
    <?= $template->render('learn/game_list', ['games' => $games, 'basePath' => $basePath, 't' => $t]) ?>
<?php endif; ?>
