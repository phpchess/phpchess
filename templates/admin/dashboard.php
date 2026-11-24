<?php
/**
 * The admin start page (INS-07): warnings and waiting accounts.
 *
 * Variables: $pendingCount (int), $warnings, $currentAdminPath, $basePath, $csrfToken, $t, $template.
 */
?>
<?= $template->render('admin/header', ['warnings' => $warnings, 'currentAdminPath' => $currentAdminPath, 'basePath' => $basePath, 't' => $t, 'csrfToken' => $csrfToken]) ?>

<h1><?= e($t->text('admin.dashboard.title')) ?></h1>

<?php if ($pendingCount > 0) : ?>
    <p><a href="<?= e($basePath) ?>/admin/users?state=pending"><?= e($t->text('admin.dashboard.pending', ['count' => $pendingCount])) ?></a></p>
<?php else : ?>
    <p><?= e($t->text('admin.dashboard.nothing_waiting')) ?></p>
<?php endif; ?>

