<?php
/**
 * A short message page inside the site layout, for example "This game does not exist" (404).
 *
 * Variables: $title (string), $message (string), $basePath, $t.
 */
?>
<h1><?= e($title) ?></h1>
<p><?= e($message) ?></p>
<p><a href="<?= e($basePath) ?>/play"><?= e($t->text('play.back_to_games')) ?></a></p>
