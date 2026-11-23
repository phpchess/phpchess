<?php
/**
 * Login cards, ready to print (SCH-04): one card per student with the
 * name, a QR code and the link. A card logs in once; then the device is
 * remembered. These cards exist only on this page.
 *
 * Variables: $class (array{id, name}), $cards (list of array{username, link, qrSvgHtml}), $basePath, $t.
 */
?>
<div class="no-print">
    <h1><?= e($t->text('school.cards_title', ['class' => $class['name']])) ?></h1>
    <p><?= e($t->text('school.cards_explain')) ?></p>
    <p class="button-row">
        <a class="button button-secondary" href="<?= e($basePath) ?>/school/classes/<?= e($class['id']) ?>">‹ <?= e($class['name']) ?></a>
    </p>
</div>

<?php if ($cards === []) : ?>
    <p class="muted"><?= e($t->text('school.no_students_for_cards')) ?></p>
<?php endif; ?>

<ul class="login-cards">
    <?php foreach ($cards as $card) : ?>
        <li class="login-card">
            <strong><?= e($card['username']) ?></strong>
            <?php $qrSvgHtml = $card['qrSvgHtml']; ?>
            <?php if ($qrSvgHtml !== '') : ?>
                <?= $qrSvgHtml ?>
            <?php endif; ?>
            <span class="login-card-link"><?= e($card['link']) ?></span>
        </li>
    <?php endforeach; ?>
</ul>
