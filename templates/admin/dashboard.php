<?php
/**
 * The admin start page (INS-07): warnings, waiting accounts, the setup token.
 *
 * Variables: $pendingCount (int), $warnings, $currentAdminPath, $basePath, $csrfToken, $t, $template.
 */
?>
<?= $template->render('admin/header', ['warnings' => $warnings, 'currentAdminPath' => $currentAdminPath, 'basePath' => $basePath, 't' => $t]) ?>

<h1><?= e($t->text('admin.dashboard.title')) ?></h1>

<?php if ($pendingCount > 0) : ?>
    <p><a href="<?= e($basePath) ?>/admin/users?state=pending"><?= e($t->text('admin.dashboard.pending', ['count' => $pendingCount])) ?></a></p>
<?php else : ?>
    <p><?= e($t->text('admin.dashboard.nothing_waiting')) ?></p>
<?php endif; ?>

<h2><?= e($t->text('admin.setup_token.title')) ?></h2>
<p><?= e($t->text('admin.setup_token.explain')) ?></p>
<form method="post" action="<?= e($basePath) ?>/admin/setup-token">
    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
    <button type="submit" class="button-secondary"><?= e($t->text('admin.setup_token.button')) ?></button>
</form>
