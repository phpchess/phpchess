<?php
/**
 * The newsletter (ADM-07): write one, and see how far earlier ones got.
 * Sending is done by the newsletter job, a few emails per run (A41).
 *
 * Variables: $newsletters (list of array{subject, created_at, sent, waiting}),
 * $warnings, $currentAdminPath, $basePath, $csrfToken, $t, $template.
 */
?>
<?= $template->render('admin/header', ['warnings' => $warnings, 'currentAdminPath' => $currentAdminPath, 'basePath' => $basePath, 't' => $t, 'csrfToken' => $csrfToken]) ?>

<h1><?= e($t->text('admin.newsletter.title')) ?></h1>
<p><?= e($t->text('admin.newsletter.explain')) ?></p>

<form method="post" action="<?= e($basePath) ?>/admin/newsletter" class="form">
    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
    <label for="subject"><?= e($t->text('admin.newsletter.subject')) ?></label>
    <input type="text" id="subject" name="subject" required maxlength="200">
    <label for="body"><?= e($t->text('admin.newsletter.body')) ?></label>
    <textarea id="body" name="body" rows="10" required></textarea>
    <button type="submit" class="button"><?= e($t->text('admin.newsletter.send')) ?></button>
</form>

<?php if ($newsletters !== []) : ?>
    <h2><?= e($t->text('admin.newsletter.earlier')) ?></h2>
    <ul class="card-list">
        <?php foreach ($newsletters as $letter) : ?>
            <li class="card">
                <p class="card-title"><strong><?= e($letter['subject']) ?></strong> <span class="muted"><?= e(substr($letter['created_at'], 0, 16)) ?></span></p>
                <p class="muted"><?= e($t->text('admin.newsletter.progress', ['sent' => $letter['sent'], 'waiting' => $letter['waiting']])) ?></p>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
