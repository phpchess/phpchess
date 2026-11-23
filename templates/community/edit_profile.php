<?php
/**
 * Editing my profile (C-01, C-02): the text about me, a bundled avatar,
 * or my own picture (switch "file uploads").
 *
 * Variables: $profile (array{avatar, about}), $bundledAvatars (list of string), $uploadsAreOn (bool),
 * $maxMegabytes (int), $user (User), $basePath, $csrfToken, $t, $template.
 */
?>
<h1><?= e($t->text('profile.edit_title')) ?></h1>
<p><a href="<?= e($basePath) ?>/players/<?= e(rawurlencode($user->username)) ?>"><?= e($t->text('profile.see_mine')) ?></a></p>

<form method="post" action="<?= e($basePath) ?>/account/profile" class="form">
    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
    <label for="about"><?= e($t->text('profile.about')) ?></label>
    <textarea id="about" name="about" rows="5" maxlength="1000"><?= e($profile['about']) ?></textarea>
    <button type="submit" class="button"><?= e($t->text('profile.save')) ?></button>
</form>

<h2><?= e($t->text('profile.avatar')) ?></h2>
<form method="post" action="<?= e($basePath) ?>/account/avatar" class="form">
    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
    <fieldset class="avatar-choice">
        <legend><?= e($t->text('profile.choose_avatar')) ?></legend>
        <label><input type="radio" name="avatar" value=""<?= $profile['avatar'] === '' ? ' checked' : '' ?>>
            <?= $template->render('community/avatar', ['userId' => $user->id, 'username' => $user->username, 'avatar' => '', 'size' => 64, 'basePath' => $basePath]) ?></label>
        <?php foreach ($bundledAvatars as $name) : ?>
            <label><input type="radio" name="avatar" value="<?= e($name) ?>"<?= $profile['avatar'] === $name ? ' checked' : '' ?>>
                <?= $template->render('community/avatar', ['userId' => $user->id, 'username' => $user->username, 'avatar' => $name, 'size' => 64, 'basePath' => $basePath]) ?>
                <span class="visually-hidden"><?= e($name) ?></span></label>
        <?php endforeach; ?>
    </fieldset>
    <button type="submit" class="button"><?= e($t->text('profile.use_avatar')) ?></button>
</form>

<?php if ($uploadsAreOn) : ?>
    <form method="post" action="<?= e($basePath) ?>/account/avatar/upload" enctype="multipart/form-data" class="form">
        <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
        <?php if ($profile['avatar'] === 'upload') : ?>
            <p><?= $template->render('community/avatar', ['userId' => $user->id, 'username' => $user->username, 'avatar' => 'upload', 'size' => 128, 'basePath' => $basePath]) ?></p>
        <?php endif; ?>
        <label for="picture"><?= e($t->text('profile.upload', ['max' => $maxMegabytes])) ?></label>
        <input type="file" id="picture" name="picture" accept="image/jpeg,image/png,image/gif,image/webp" required>
        <button type="submit" class="button"><?= e($t->text('profile.upload_button')) ?></button>
    </form>
<?php endif; ?>
