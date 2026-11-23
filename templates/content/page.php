<?php
/**
 * A static page by the admin (C-09), such as /pages/rules.
 *
 * Variables: $page (content item: title, body), $template.
 */
?>
<h1><?= e($page['title']) ?></h1>
<?= $template->render('content/text', ['text' => $page['body']]) ?>
