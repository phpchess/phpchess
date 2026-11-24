<?php
/**
 * The audit log (ADM-03): the newest admin actions.
 *
 * Variables: $entries (list of array{created_at, username, action, details, ip_address}),
 * $warnings, $currentAdminPath, $basePath, $t, $template.
 */
?>
<?= $template->render('admin/header', ['warnings' => $warnings, 'currentAdminPath' => $currentAdminPath, 'basePath' => $basePath, 't' => $t, 'csrfToken' => $csrfToken]) ?>

<h1><?= e($t->text('admin.audit.title')) ?></h1>

<?php if ($entries === []) : ?>
    <p><?= e($t->text('admin.audit.none')) ?></p>
<?php endif; ?>

<ul class="card-list">
    <?php foreach ($entries as $entry) : ?>
        <li class="card">
            <p class="card-title"><strong><?= e($entry['action']) ?></strong> <span class="muted"><?= e($entry['created_at']) ?> UTC</span></p>
            <p><?= e($entry['details']) ?></p>
            <p class="muted"><?= e($entry['username'] === '' ? '—' : $entry['username']) ?> · <?= e($entry['ip_address']) ?></p>
        </li>
    <?php endforeach; ?>
</ul>
