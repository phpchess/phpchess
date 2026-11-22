<?php
/**
 * Installer step 3: database details (INS-02, INS-03, A23).
 *
 * Only the three things every hosting panel shows are needed: the database
 * server, a user name and a password. phpChess finds or creates the database
 * itself (DatabaseFinder).
 *
 * Variables: $fields (array of the form's values, never the password),
 * $error (string), $checks (list of CheckResult, empty unless the database
 * checks failed), $basePath, $csrfToken, $template.
 */
?>
<p>Enter the database login from your hosting control panel (usually under "Databases" or "MySQL"). phpChess finds a database it may use, or creates one, by itself.</p>

<?php if ($error !== '') : ?>
    <p class="notice-error"><?= e($error) ?></p>
<?php endif; ?>
<?php if ($checks !== []) : ?>
    <?= $template->render('install/checks', ['checks' => $checks]) ?>
<?php endif; ?>

<form method="post" action="<?= e($basePath) ?>/install/database" class="form">
    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
    <label>Database server <input name="database_host" value="<?= e($fields['database_host'] ?? '') ?>" required></label>
    <label>User name <input name="database_user" value="<?= e($fields['database_user'] ?? '') ?>" autocomplete="off" required></label>
    <label>Password <input type="password" name="database_password" autocomplete="off"></label>

    <details>
        <summary>More options (usually not needed)</summary>
        <div class="form">
            <label>Database name (leave empty and phpChess picks or creates one) <input name="database_name" value="<?= e($fields['database_name'] ?? '') ?>"></label>
            <label>Port <input name="database_port" value="<?= e($fields['database_port'] ?? '') ?>" inputmode="numeric" required></label>
            <label>Table prefix (keeps phpChess's tables apart from others) <input name="table_prefix" value="<?= e($fields['table_prefix'] ?? '') ?>" pattern="[a-z][a-z0-9_]{0,19}" required></label>
        </div>
    </details>

    <button type="submit">Connect</button>
</form>
