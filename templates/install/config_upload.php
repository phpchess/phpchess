<?php
/**
 * Shown when phpChess cannot write config/config.php itself (INS-02).
 *
 * Variables: $fileContents (string, the whole file), $basePath.
 */
?>
<p>phpChess may not write into the folder <code>config</code> on this server. Please create the file yourself:</p>
<ol>
    <li>Copy all the text below into a new text file.</li>
    <li>Save it as <code>config.php</code>.</li>
    <li>Upload it with your FTP program into the folder <code>config</code> of phpChess.</li>
</ol>
<pre class="config-file"><?= e($fileContents) ?></pre>
<p><a class="button" href="<?= e($basePath) ?>/install">I have uploaded it - continue</a></p>
