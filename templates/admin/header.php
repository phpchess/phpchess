<?php
/**
 * The top of every admin page: the warning banner (INS-07) and the admin
 * menu, in groups (F2, A71; site.css puts it beside the page on wide screens).
 *
 * Variables: $warnings (list of string, from UnsafeStateCheck),
 * $currentAdminPath (string), $basePath, $csrfToken, $t.
 * The "updates waiting" warning gets the "Run updates now" button (A54).
 */

use PhpChess\Domain\System\UnsafeStateCheck;

// The admin menu in groups (F2, A71): a side menu on a laptop or tablet,
// rows of buttons above the page on a phone.
$adminMenu = [
    'admin.menu.people' => [
        '/admin' => 'admin.dashboard.title',
        '/admin/users' => 'admin.users.title',
        '/admin/community' => 'admin.community.title',
        '/admin/newsletter' => 'admin.newsletter.title',
    ],
    'admin.menu.play' => [
        '/admin/games' => 'admin.games.title',
        '/admin/play' => 'admin.play.title',
        '/admin/engine' => 'admin.engine.title',
    ],
    'admin.menu.site' => [
        '/admin/settings' => 'admin.settings.title',
        '/admin/mail' => 'admin.mail.title',
        '/admin/switches' => 'admin.switches.title',
        '/admin/content' => 'admin.content.title',
        '/admin/notices' => 'admin.notices.title',
    ],
    'admin.menu.records' => [
        '/admin/archive' => 'admin.archive.title',
        '/admin/audit' => 'admin.audit.title',
    ],
];
?>
<?php if ($warnings !== []) : ?>
    <section class="notice-warning admin-banner" aria-labelledby="admin-banner-title">
        <h2 id="admin-banner-title"><?= e($t->text('admin.banner.title')) ?></h2>
        <ul>
            <?php foreach ($warnings as $warning) : ?>
                <li>
                    <?= e($warning) ?>
                    <?php if ($warning === UnsafeStateCheck::UPDATES_WAITING) : ?>
                        <form method="post" action="<?= e($basePath) ?>/admin/updates" class="inline-form">
                            <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                            <button type="submit" class="button"><?= e($t->text('admin.updates.run')) ?></button>
                        </form>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endif; ?>

<nav class="admin-menu" aria-label="<?= e($t->text('layout.admin')) ?>">
    <?php foreach ($adminMenu as $groupKey => $items) : ?>
        <div class="admin-menu-group">
            <h2><?= e($t->text($groupKey)) ?></h2>
            <ul>
                <?php foreach ($items as $path => $labelKey) : ?>
                    <li><a href="<?= e($basePath . $path) ?>"<?= $currentAdminPath === $path ? ' aria-current="page"' : '' ?>><?= e($t->text($labelKey)) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endforeach; ?>
</nav>
