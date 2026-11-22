<?php
/**
 * Shown when the installer is opened after the install (INS-06).
 *
 * Variables: $basePath.
 */
?>
<p>phpChess is already installed, so the installer is closed. To open it again (for example to run the database updates of a new version), create a setup token on the admin pages and open the link it gives you.</p>
<p><a class="button" href="<?= e($basePath) ?>/">Go to the chess site</a></p>
