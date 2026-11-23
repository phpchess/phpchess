<?php
/**
 * The FAQ (C-09): each question opens to show its answer, so a long FAQ
 * stays short on a phone.
 *
 * Variables: $entries (list of content items: title, body), $t, $template.
 */
?>
<h1><?= e($t->text('content.faq')) ?></h1>
<?php if ($entries === []) : ?>
    <p class="muted"><?= e($t->text('content.faq_empty')) ?></p>
<?php endif; ?>
<?php foreach ($entries as $entry) : ?>
    <details class="faq-entry">
        <summary><?= e($entry['title']) ?></summary>
        <?= $template->render('content/text', ['text' => $entry['body']]) ?>
    </details>
<?php endforeach; ?>
