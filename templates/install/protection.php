<?php
/**
 * Installer step 2: protection self-test (INS-04).
 *
 * Variables: $testResult (null before the test, else an array with "result",
 * "exposedUrls" and "testedUrls"), $nginxSnippet (string), $basePath, $csrfToken.
 */
?>
<p>Only the folder <code>public</code> may be visible on the web. The folders <code>config</code> (with the database password) and <code>storage</code> must not be. phpChess checks this by asking your own web server for a test file in each.</p>

<?php if ($testResult !== null && $testResult['result'] === 'exposed') : ?>
    <div class="notice-error">
        <p><strong>Stop: private folders can be opened from the web.</strong> These addresses showed the test file:</p>
        <ul>
            <?php foreach ($testResult['exposedUrls'] as $url) : ?>
                <li><code><?= e($url) ?></code></li>
            <?php endforeach; ?>
        </ul>
        <p><strong>How to fix it</strong>, choose one:</p>
        <ol>
            <li>Point your domain (or subdomain) at the folder <code>public</code> in your hosting control panel. That is the safest set-up.</li>
            <li>Make sure the <code>.htaccess</code> files were uploaded (their names start with a dot, and some FTP programs hide them), and ask your host to allow <code>.htaccess</code> files ("AllowOverride All").</li>
            <li>On a server with nginx, use this configuration:</li>
        </ol>
        <pre><?= e($nginxSnippet) ?></pre>
        <p>Then run the test again.</p>
    </div>
<?php endif; ?>

<?php if ($testResult !== null && $testResult['result'] === 'unknown') : ?>
    <div class="notice-warning">
        <p>The server could not reach itself to run the test. Please check by hand: open each address below in a new tab. Each must show an error page ("Forbidden" or "Not Found"), never a file or a folder listing.</p>
        <ul>
            <?php foreach ($testResult['testedUrls'] as $url) : ?>
                <li><code><?= e($url) ?></code></li>
            <?php endforeach; ?>
        </ul>
        <form method="post" action="<?= e($basePath) ?>/install/protection">
            <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
            <label><input type="checkbox" name="manually_checked" value="yes" required> I opened them and saw error pages</label>
            <button type="submit">Continue</button>
        </form>
    </div>
<?php endif; ?>

<form method="post" action="<?= e($basePath) ?>/install/protection">
    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
    <button type="submit"><?= $testResult === null ? 'Run the test' : 'Run the test again' ?></button>
</form>
