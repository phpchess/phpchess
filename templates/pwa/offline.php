<?php
/**
 * The offline page (UI-08): shown by the service worker when there is no
 * connection and the page asked for was never opened on this device.
 *
 * Variables: $basePath, $t.
 */
?>
<h1><?= e($t->text('offline.title')) ?></h1>
<p><?= e($t->text('offline.explain')) ?></p>
<p class="button-row">
    <a class="button" href="<?= e($basePath) ?>/"><?= e($t->text('offline.start_page')) ?></a>
    <a class="button button-secondary" href="<?= e($basePath) ?>/games"><?= e($t->text('games.title')) ?></a>
</p>
