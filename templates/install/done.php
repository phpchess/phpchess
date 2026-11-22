<?php
/**
 * Shown once the install is finished (INS-06, A23).
 *
 * Variables: $installerRemoved (bool: the installer deleted its own files), $basePath.
 */
?>
<p class="notice-success">phpChess is installed.</p>
<?php if ($installerRemoved) : ?>
    <p>The installer has removed itself from the server, so nobody can run it again.</p>
<?php else : ?>
    <p class="notice-warning">The installer is locked, but this server did not let it delete its own files. Please delete the folders <code>src/Install</code> and <code>templates/install</code> with your FTP program.</p>
<?php endif; ?>
<p>Recommended next steps:</p>
<ul>
    <li>Make <code>config/config.php</code> read-only (permission 444) with your FTP program.</li>
    <li>If your host offers cron jobs, run <code>php public/cron.php</code> every minute. Without one, phpChess runs its background jobs during page visits.</li>
</ul>
<p><a class="button" href="<?= e($basePath) ?>/">Go to your chess site</a></p>
