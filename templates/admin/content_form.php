<?php
/**
 * The form for one content item (ADM-05), new ($item null) or existing.
 *
 * Variables: $item (content item|null), $kind (string), $action (string, full address), $csrfToken, $t.
 */
$prefix = 'content-' . ($item === null ? 'new-' . $kind : $item['id']);
?>
<form method="post" action="<?= e($action) ?>" class="form">
    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
    <input type="hidden" name="kind" value="<?= e($kind) ?>">
    <label for="<?= e($prefix) ?>-title"><?= e($t->text('admin.content.item_title')) ?></label>
    <input type="text" id="<?= e($prefix) ?>-title" name="title" required maxlength="200" value="<?= e($item['title'] ?? '') ?>">
    <?php if ($kind === 'page') : ?>
        <label for="<?= e($prefix) ?>-slug"><?= e($t->text('admin.content.slug')) ?></label>
        <input type="text" id="<?= e($prefix) ?>-slug" name="slug" maxlength="60" value="<?= e($item['slug'] ?? '') ?>">
    <?php endif; ?>
    <label for="<?= e($prefix) ?>-body"><?= e($t->text('admin.content.body')) ?></label>
    <textarea id="<?= e($prefix) ?>-body" name="body" rows="6"><?= e($item['body'] ?? '') ?></textarea>
    <?php if ($kind === 'tip' || $kind === 'faq' || $kind === 'page') : ?>
        <label for="<?= e($prefix) ?>-order"><?= e($t->text('admin.content.order')) ?></label>
        <input type="number" id="<?= e($prefix) ?>-order" name="sort_order" value="<?= e($item['sort_order'] ?? 0) ?>">
    <?php endif; ?>
    <label class="checkbox"><input type="checkbox" name="published" value="yes"<?= ($item['is_published'] ?? true) ? ' checked' : '' ?>> <?= e($t->text('admin.content.published')) ?></label>
    <button type="submit" class="button"><?= e($t->text('admin.content.save')) ?></button>
</form>
