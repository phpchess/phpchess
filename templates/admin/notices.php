<?php
/**
 * Stored notices (A44): not shown to players any more, kept for admin
 * searches ("did Anna get told about her move?").
 *
 * Variables: $notices (list of array{username, kind, game_id, from_username, created_at, read_at, emailed_at, pushed_at}),
 * $username (string, the search), $warnings, $currentAdminPath, $basePath, $t, $template.
 */
?>
<?= $template->render('admin/header', ['warnings' => $warnings, 'currentAdminPath' => $currentAdminPath, 'basePath' => $basePath, 't' => $t, 'csrfToken' => $csrfToken]) ?>

<h1><?= e($t->text('admin.notices.title')) ?></h1>

<form method="get" action="<?= e($basePath) ?>/admin/notices" class="form form-inline">
    <label for="user"><?= e($t->text('admin.notices.user')) ?></label>
    <input type="text" id="user" name="user" value="<?= e($username) ?>" maxlength="40">
    <button type="submit" class="button button-secondary"><?= e($t->text('admin.notices.search')) ?></button>
</form>

<?php if ($notices === []) : ?>
    <p class="muted"><?= e($t->text('admin.notices.none')) ?></p>
<?php else : ?>
    <div class="table-scroll">
        <table class="data-table">
            <thead>
                <tr>
                    <th scope="col"><?= e($t->text('admin.notices.when')) ?></th>
                    <th scope="col"><?= e($t->text('admin.notices.user')) ?></th>
                    <th scope="col"><?= e($t->text('admin.notices.what')) ?></th>
                    <th scope="col"><?= e($t->text('admin.notices.delivery')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($notices as $notice) : ?>
                    <tr>
                        <td><?= e(substr($notice['created_at'], 0, 16)) ?></td>
                        <td><?= e($notice['username']) ?></td>
                        <td>
                            <?= e($t->text('notice.' . $notice['kind'], ['name' => $notice['from_username']])) ?>
                            <?php if ($notice['game_id'] !== null) : ?>
                                · <a href="<?= e($basePath) ?>/games/<?= e($notice['game_id']) ?>">#<?= e($notice['game_id']) ?></a>
                            <?php endif; ?>
                        </td>
                        <td class="muted">
                            <?= e($notice['read_at'] === '' ? $t->text('admin.notices.unread') : $t->text('admin.notices.read')) ?>
                            <?= e($notice['emailed_at'] === '' ? '' : '· ' . $t->text('admin.notices.emailed')) ?>
                            <?= e($notice['pushed_at'] === '' ? '' : '· ' . $t->text('admin.notices.pushed')) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
