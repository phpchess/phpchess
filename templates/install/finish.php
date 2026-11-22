<?php
/**
 * Installer step 6: lock the installer (INS-06).
 *
 * Variables: $basePath, $csrfToken.
 */
?>
<p>Everything is ready. Finishing locks the installer: after that it opens only with a one-time setup token from the admin pages.</p>
<form method="post" action="<?= e($basePath) ?>/install/finish">
    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
    <button type="submit">Finish and lock</button>
</form>
