<?php
/**
 * Plain text from the admin (news, tips, FAQ, pages; C-06 to C-09) as
 * paragraphs: a blank line starts a new paragraph, a single line break
 * stays a line break. Everything is escaped, so content can never carry
 * HTML or a script (K6).
 *
 * Variables: $text (string).
 */
$paragraphs = preg_split('/\R\s*\R/', trim($text)) ?: [];
?>
<?php foreach ($paragraphs as $paragraph) : ?>
    <?php $lines = preg_split('/\R/', trim($paragraph)) ?: []; ?>
    <p><?php foreach ($lines as $index => $line) : ?><?php if ($index > 0) : ?><br><?php endif; ?><?= e($line) ?><?php endforeach; ?></p>
<?php endforeach; ?>
