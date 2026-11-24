<?php
/**
 * Shows a new setup token link once (INS-06).
 *
 * Variables: $installerLink (string), $warnings, $currentAdminPath, $basePath, $t, $template.
 */
?>
<?= $template->render('admin/header', ['warnings' => $warnings, 'currentAdminPath' => $currentAdminPath, 'basePath' => $basePath, 't' => $t]) ?>

<h1><?= e($t->text('admin.setup_token.title')) ?></h1>
<p><?= e($t->text('admin.setup_token.shown_once')) ?></p>
<p><a class="button" href="<?= e($installerLink) ?>"><?= e($t->text('admin.setup_token.open')) ?></a></p>
<p class="hint"><?= e($t->text('admin.setup_token.or_copy')) ?></p>
<pre class="copyable"><?= e($installerLink) ?></pre>
