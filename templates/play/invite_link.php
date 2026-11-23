<?php
/**
 * The new invite link and its QR code (G-21), shown to the person who made it.
 * The share button uses the phone's share sheet (Web Share) where there is one.
 *
 * Variables: $link (string), $qrSvgHtml (string, from QrCode::svg),
 * $expiresAt (int, Unix seconds), $basePath, $t.
 */
?>
<h1><?= e($t->text('invite.title')) ?></h1>
<p><?= e($t->text('invite.explain', ['time' => gmdate('Y-m-d H:i', $expiresAt) . ' UTC'])) ?></p>

<div class="qr-frame"><?= $qrSvgHtml ?></div>

<label class="form"><?= e($t->text('invite.link')) ?>
    <input type="text" class="copyable" readonly value="<?= e($link) ?>" id="invite-link">
</label>
<p class="button-row">
    <button type="button" id="share-invite" data-share-url="<?= e($link) ?>" data-share-title="<?= e($t->text('invite.share_title')) ?>" hidden><?= e($t->text('invite.share')) ?></button>
    <a class="button button-secondary" href="<?= e($basePath) ?>/play"><?= e($t->text('play.back_to_games')) ?></a>
</p>
<script src="<?= e($basePath) ?>/assets/js/play/share-link.js" defer></script>
