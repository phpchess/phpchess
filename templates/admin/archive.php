<?php
/**
 * The archive (A42, A43): keep the database fast without losing history.
 *
 * Steps on the page, in order:
 *   1. the last speed check (and a button to check now);
 *   2. pick a date: how many old rows an archive would hold;
 *   3. write the archive file, then download it and keep it;
 *   4. remove the archived rows (only after step 3, with a tick);
 *   5. read a kept file back in.
 *
 * Variables: $before (string, YYYY-MM-DD), $preview (array of table => rows), $fileExists (bool),
 * $isSuggested (bool), $lastCheckAt (int, 0 = never), $lastSlowestMs and $cutoffMs (int),
 * $warnings, $currentAdminPath, $basePath, $csrfToken, $t, $template.
 */
?>
<?= $template->render('admin/header', ['warnings' => $warnings, 'currentAdminPath' => $currentAdminPath, 'basePath' => $basePath, 't' => $t, 'csrfToken' => $csrfToken]) ?>

<h1><?= e($t->text('admin.archive.title')) ?></h1>
<p><?= e($t->text('admin.archive.explain')) ?></p>

<?php if ($isSuggested) : ?>
    <p class="notice-warning" role="status"><?= e($t->text('admin.archive.suggested', ['ms' => $lastSlowestMs, 'cutoff' => $cutoffMs])) ?></p>
<?php endif; ?>

<section aria-labelledby="check-title">
    <h2 id="check-title">1. <?= e($t->text('admin.archive.check_title')) ?></h2>
    <p><?= e($lastCheckAt === 0 ? $t->text('admin.archive.never_checked') : $t->text('admin.archive.last_check', ['date' => gmdate('Y-m-d H:i', $lastCheckAt), 'ms' => $lastSlowestMs, 'cutoff' => $cutoffMs])) ?></p>
    <form method="post" action="<?= e($basePath) ?>/admin/archive/check" class="inline-form">
        <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
        <button type="submit" class="button button-secondary"><?= e($t->text('admin.archive.check_now')) ?></button>
    </form>
</section>

<section aria-labelledby="date-title">
    <h2 id="date-title">2. <?= e($t->text('admin.archive.date_title')) ?></h2>
    <form method="get" action="<?= e($basePath) ?>/admin/archive" class="form form-inline">
        <label for="before"><?= e($t->text('admin.archive.before')) ?></label>
        <input type="date" id="before" name="before" value="<?= e($before) ?>" required>
        <button type="submit" class="button button-secondary"><?= e($t->text('admin.archive.count')) ?></button>
    </form>
    <?php if ($preview !== []) : ?>
        <ul class="text-list">
            <?php foreach ($preview as $table => $count) : ?>
                <li><?= e($t->text('admin.archive.part.' . $table)) ?>: <?= e($count) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

<section aria-labelledby="write-title">
    <h2 id="write-title">3. <?= e($t->text('admin.archive.write_title')) ?></h2>
    <form method="post" action="<?= e($basePath) ?>/admin/archive/write" class="inline-form">
        <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
        <input type="hidden" name="before" value="<?= e($before) ?>">
        <button type="submit" class="button"><?= e($t->text('admin.archive.write', ['date' => $before])) ?></button>
    </form>
    <?php if ($fileExists) : ?>
        <p><a class="button button-secondary" href="<?= e($basePath) ?>/admin/archive/download?before=<?= e(rawurlencode($before)) ?>"><?= e($t->text('admin.archive.download')) ?></a></p>
    <?php endif; ?>
</section>

<?php if ($fileExists) : ?>
    <section aria-labelledby="remove-title" class="danger-zone">
        <h2 id="remove-title">4. <?= e($t->text('admin.archive.remove_title')) ?></h2>
        <form method="post" action="<?= e($basePath) ?>/admin/archive/remove" class="form">
            <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
            <input type="hidden" name="before" value="<?= e($before) ?>">
            <label class="checkbox"><input type="checkbox" name="kept_file" value="yes" required> <?= e($t->text('admin.archive.kept_file')) ?></label>
            <button type="submit" class="button button-danger"><?= e($t->text('admin.archive.remove', ['date' => $before])) ?></button>
        </form>
    </section>
<?php endif; ?>

<section aria-labelledby="restore-title">
    <h2 id="restore-title">5. <?= e($t->text('admin.archive.restore_title')) ?></h2>
    <form method="post" action="<?= e($basePath) ?>/admin/archive/restore" enctype="multipart/form-data" class="form">
        <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
        <label for="archive"><?= e($t->text('admin.archive.file')) ?></label>
        <input type="file" id="archive" name="archive" accept=".gz,application/gzip" required>
        <button type="submit" class="button button-secondary"><?= e($t->text('admin.archive.restore')) ?></button>
    </form>
</section>
