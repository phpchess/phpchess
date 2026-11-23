<?php
/**
 * All news, newest first (C-06).
 *
 * Variables: $news (list of content items: title, body, created_at), $t, $template.
 */
?>
<h1><?= e($t->text('content.news')) ?></h1>
<?php if ($news === []) : ?>
    <p class="muted"><?= e($t->text('content.news_empty')) ?></p>
<?php endif; ?>
<?php foreach ($news as $item) : ?>
    <article class="news-item">
        <h2><?= e($item['title']) ?> <span class="muted"><?= e(substr($item['created_at'], 0, 10)) ?></span></h2>
        <?= $template->render('content/text', ['text' => $item['body']]) ?>
    </article>
<?php endforeach; ?>
