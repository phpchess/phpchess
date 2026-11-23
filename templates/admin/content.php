<?php
/**
 * Content (ADM-05): news, server messages, tips of the day, FAQ entries
 * and static pages. Each kind has its list (each item opens to edit) and
 * a form for a new one. The text is plain text; blank lines make paragraphs.
 *
 * Variables: $itemsByKind (array of kind => list of content items), $kinds (list of string),
 * $warnings, $currentAdminPath, $basePath, $csrfToken, $t, $template.
 */
?>
<?= $template->render('admin/header', ['warnings' => $warnings, 'currentAdminPath' => $currentAdminPath, 'basePath' => $basePath, 't' => $t]) ?>

<h1><?= e($t->text('admin.content.title')) ?></h1>
<p><?= e($t->text('admin.content.explain')) ?></p>

<?php foreach ($kinds as $kind) : ?>
    <section aria-labelledby="kind-<?= e($kind) ?>">
        <h2 id="kind-<?= e($kind) ?>"><?= e($t->text('admin.content.kind.' . $kind)) ?></h2>
        <?php foreach ($itemsByKind[$kind] as $item) : ?>
            <details class="card">
                <summary>
                    <?= e($item['title']) ?>
                    <?php if (!$item['is_published']) : ?><span class="muted">(<?= e($t->text('admin.content.hidden')) ?>)</span><?php endif; ?>
                    <?php if ($kind === 'page') : ?><span class="muted">/pages/<?= e($item['slug']) ?></span><?php endif; ?>
                </summary>
                <?= $template->render('admin/content_form', ['item' => $item, 'kind' => $kind, 'action' => $basePath . '/admin/content/' . $item['id'], 'basePath' => $basePath, 'csrfToken' => $csrfToken, 't' => $t]) ?>
                <form method="post" action="<?= e($basePath) ?>/admin/content/<?= e($item['id']) ?>/delete" class="inline-form">
                    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                    <button type="submit" class="button button-secondary button-small"><?= e($t->text('admin.content.delete')) ?></button>
                </form>
            </details>
        <?php endforeach; ?>
        <details>
            <summary><?= e($t->text('admin.content.add')) ?></summary>
            <?= $template->render('admin/content_form', ['item' => null, 'kind' => $kind, 'action' => $basePath . '/admin/content', 'basePath' => $basePath, 'csrfToken' => $csrfToken, 't' => $t]) ?>
        </details>
    </section>
<?php endforeach; ?>
