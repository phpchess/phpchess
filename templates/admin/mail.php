<?php
/**
 * Mail settings (ADM-02): a log file (development, offline schools) or an SMTP server.
 *
 * Variables: $transport ("log" or "smtp"), $fromAddress, $smtpHost, $smtpUsername,
 * $smtpEncryption (string), $smtpPort (int), $hasSmtpPassword (bool),
 * $warnings, $currentAdminPath, $basePath, $csrfToken, $t, $template.
 */
?>
<?= $template->render('admin/header', ['warnings' => $warnings, 'currentAdminPath' => $currentAdminPath, 'basePath' => $basePath, 't' => $t, 'csrfToken' => $csrfToken]) ?>

<h1><?= e($t->text('admin.mail.title')) ?></h1>
<p><?= e($t->text('admin.mail.explain')) ?></p>

<form method="post" action="<?= e($basePath) ?>/admin/mail" class="form">
    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">

    <fieldset>
        <legend><?= e($t->text('admin.mail.transport')) ?></legend>
        <label class="checkbox"><input type="radio" name="transport" value="log"<?= $transport === 'log' ? ' checked' : '' ?>> <?= e($t->text('admin.mail.transport_log')) ?></label>
        <label class="checkbox"><input type="radio" name="transport" value="smtp"<?= $transport === 'smtp' ? ' checked' : '' ?>> <?= e($t->text('admin.mail.transport_smtp')) ?></label>
    </fieldset>

    <label><?= e($t->text('admin.mail.from_address')) ?>
        <input type="email" name="from_address" value="<?= e($fromAddress) ?>" required>
    </label>

    <fieldset>
        <legend><?= e($t->text('admin.mail.smtp')) ?></legend>
        <div class="form">
            <label><?= e($t->text('admin.mail.smtp_host')) ?> <input name="smtp_host" value="<?= e($smtpHost) ?>"></label>
            <label><?= e($t->text('admin.mail.smtp_port')) ?> <input name="smtp_port" value="<?= e($smtpPort) ?>" inputmode="numeric"></label>
            <label><?= e($t->text('admin.mail.smtp_encryption')) ?>
                <select name="smtp_encryption">
                    <?php foreach (['tls', 'ssl', 'none'] as $encryption) : ?>
                        <option value="<?= e($encryption) ?>"<?= $encryption === $smtpEncryption ? ' selected' : '' ?>><?= e($t->text('admin.mail.encryption.' . $encryption)) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label><?= e($t->text('admin.mail.smtp_username')) ?> <input name="smtp_username" value="<?= e($smtpUsername) ?>" autocomplete="off"></label>
            <label><?= e($t->text('admin.mail.smtp_password')) ?>
                <input type="password" name="smtp_password" autocomplete="new-password">
                <?php if ($hasSmtpPassword) : ?>
                    <span class="hint"><?= e($t->text('admin.mail.password_kept')) ?></span>
                <?php endif; ?>
            </label>
        </div>
    </fieldset>

    <button type="submit"><?= e($t->text('admin.save')) ?></button>
</form>

<form method="post" action="<?= e($basePath) ?>/admin/mail/test">
    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
    <button type="submit" class="button-secondary"><?= e($t->text('admin.mail.test_button')) ?></button>
</form>
