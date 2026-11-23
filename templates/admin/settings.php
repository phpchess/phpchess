<?php
/**
 * Site settings (ADM-02, ID-03): name, language, skin, approval of new accounts.
 *
 * Variables: $siteName (string), $siteLanguage (string), $languages (list of string),
 * $skinName (string), $skins (array: folder => name), $approvalRequired (bool),
 * $warnings, $currentAdminPath, $basePath, $csrfToken, $t, $template.
 */
?>
<?= $template->render('admin/header', ['warnings' => $warnings, 'currentAdminPath' => $currentAdminPath, 'basePath' => $basePath, 't' => $t]) ?>

<h1><?= e($t->text('admin.settings.title')) ?></h1>

<form method="post" action="<?= e($basePath) ?>/admin/settings" class="form">
    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
    <label><?= e($t->text('admin.settings.site_name')) ?>
        <input name="site_name" value="<?= e($siteName) ?>" maxlength="60" required>
    </label>
    <label><?= e($t->text('admin.settings.language')) ?>
        <select name="site_language">
            <?php foreach ($languages as $language) : ?>
                <option value="<?= e($language) ?>"<?= $language === $siteLanguage ? ' selected' : '' ?>><?= e($t->text('language.' . $language)) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label><?= e($t->text('admin.settings.skin')) ?>
        <select name="skin">
            <?php foreach ($skins as $folder => $name) : ?>
                <option value="<?= e($folder) ?>"<?= $folder === $skinName ? ' selected' : '' ?>><?= e($name) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label class="checkbox"><input type="checkbox" name="approval_required" value="yes"<?= $approvalRequired ? ' checked' : '' ?>> <?= e($t->text('admin.settings.approval_required')) ?></label>
    <button type="submit"><?= e($t->text('admin.save')) ?></button>
</form>
