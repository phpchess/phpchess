<?php
/**
 * The top of every admin page: the warning banner (INS-07) and the admin menu.
 *
 * Variables: $warnings (list of string, from UnsafeStateCheck),
 * $currentAdminPath (string), $basePath, $t.
 */

$adminMenu = [
    '/admin' => 'admin.dashboard.title',
    '/admin/users' => 'admin.users.title',
    '/admin/games' => 'admin.games.title',
    '/admin/play' => 'admin.play.title',
    '/admin/settings' => 'admin.settings.title',
    '/admin/mail' => 'admin.mail.title',
    '/admin/switches' => 'admin.switches.title',
    '/admin/audit' => 'admin.audit.title',
];
?>
<?php if ($warnings !== []) : ?>
    <section class="notice-warning admin-banner" aria-labelledby="admin-banner-title">
        <h2 id="admin-banner-title"><?= e($t->text('admin.banner.title')) ?></h2>
        <ul>
            <?php foreach ($warnings as $warning) : ?>
                <li><?= e($warning) ?></li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endif; ?>

<nav class="admin-menu" aria-label="<?= e($t->text('layout.admin')) ?>">
    <ul>
        <?php foreach ($adminMenu as $path => $labelKey) : ?>
            <li><a href="<?= e($basePath . $path) ?>"<?= $currentAdminPath === $path ? ' aria-current="page"' : '' ?>><?= e($t->text($labelKey)) ?></a></li>
        <?php endforeach; ?>
    </ul>
</nav>
