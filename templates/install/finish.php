<?php
/**
 * Installer step 6: lock the installer (INS-06).
 *
 * Variables: $basePath, $csrfToken.
 */
?>
<p>Everything is ready. Finishing closes the installer for good. Later versions are updated from the admin pages with the button "Run updates now".</p>
<form method="post" action="<?= e($basePath) ?>/install/finish">
    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
    <button type="submit">Finish and lock</button>
</form>
