<?php
/**
 * The user administration (ADM-01): search, filter, and per account the
 * actions approve, state, role and rename.
 *
 * Variables: $users (list of User), $search (string), $stateFilter (string),
 * $states (list of string), $roles (list of string),
 * $warnings, $currentAdminPath, $basePath, $csrfToken, $t, $template.
 */
?>
<?= $template->render('admin/header', ['warnings' => $warnings, 'currentAdminPath' => $currentAdminPath, 'basePath' => $basePath, 't' => $t, 'csrfToken' => $csrfToken]) ?>

<h1><?= e($t->text('admin.users.title')) ?></h1>

<form method="get" action="<?= e($basePath) ?>/admin/users" class="form form-row">
    <label><?= e($t->text('admin.users.search')) ?>
        <input type="search" name="search" value="<?= e($search) ?>">
    </label>
    <label><?= e($t->text('admin.users.state')) ?>
        <select name="state">
            <option value=""><?= e($t->text('admin.users.all_states')) ?></option>
            <?php foreach ($states as $state) : ?>
                <option value="<?= e($state) ?>"<?= $state === $stateFilter ? ' selected' : '' ?>><?= e($t->text('state.' . $state)) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <button type="submit"><?= e($t->text('admin.users.filter')) ?></button>
</form>

<?php if ($users === []) : ?>
    <p><?= e($t->text('admin.users.none')) ?></p>
<?php endif; ?>

<ul class="card-list">
    <?php foreach ($users as $listedUser) : ?>
        <li class="card">
            <h2 class="card-title"><?= e($listedUser->username) ?> <span class="muted">#<?= e($listedUser->id) ?></span></h2>
            <p class="muted"><?= e($listedUser->email) ?> · <?= e($t->text('state.' . $listedUser->state)) ?> · <?= e($t->text('role.' . $listedUser->role)) ?></p>

            <div class="card-actions">
                <?php if ($listedUser->state === 'pending' && !$listedUser->isApproved) : ?>
                    <form method="post" action="<?= e($basePath) ?>/admin/users/<?= e($listedUser->id) ?>/approve" class="inline-form">
                        <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                        <button type="submit"><?= e($t->text('admin.users.approve')) ?></button>
                    </form>
                <?php endif; ?>

                <form method="post" action="<?= e($basePath) ?>/admin/users/<?= e($listedUser->id) ?>/state" class="inline-form">
                    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                    <label class="visually-hidden" for="state-<?= e($listedUser->id) ?>"><?= e($t->text('admin.users.state')) ?></label>
                    <select id="state-<?= e($listedUser->id) ?>" name="state">
                        <?php foreach (['active', 'disabled', 'archived'] as $state) : ?>
                            <option value="<?= e($state) ?>"<?= $state === $listedUser->state ? ' selected' : '' ?>><?= e($t->text('state.' . $state)) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="button-secondary"><?= e($t->text('admin.users.set_state')) ?></button>
                </form>

                <form method="post" action="<?= e($basePath) ?>/admin/users/<?= e($listedUser->id) ?>/role" class="inline-form">
                    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                    <label class="visually-hidden" for="role-<?= e($listedUser->id) ?>"><?= e($t->text('admin.users.role')) ?></label>
                    <select id="role-<?= e($listedUser->id) ?>" name="role">
                        <?php foreach ($roles as $role) : ?>
                            <option value="<?= e($role) ?>"<?= $role === $listedUser->role ? ' selected' : '' ?>><?= e($t->text('role.' . $role)) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="button-secondary"><?= e($t->text('admin.users.set_role')) ?></button>
                </form>

                <form method="post" action="<?= e($basePath) ?>/admin/users/<?= e($listedUser->id) ?>/rename" class="inline-form">
                    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                    <label class="visually-hidden" for="rename-<?= e($listedUser->id) ?>"><?= e($t->text('admin.users.new_name')) ?></label>
                    <input id="rename-<?= e($listedUser->id) ?>" name="username" value="<?= e($listedUser->username) ?>" maxlength="40">
                    <button type="submit" class="button-secondary"><?= e($t->text('admin.users.rename')) ?></button>
                </form>
            </div>
        </li>
    <?php endforeach; ?>
</ul>
