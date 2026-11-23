<?php
/**
 * My conversations (C-04), newest first, unread ones marked.
 *
 * Variables: $conversations (list of array{other_username, last_body, last_at, unread}),
 * $basePath, $t.
 */
?>
<h1><?= e($t->text('messages.title')) ?></h1>

<form method="get" action="<?= e($basePath) ?>/messages" class="form form-inline">
    <label for="to"><?= e($t->text('messages.write_to')) ?></label>
    <input type="text" id="to" name="to" required maxlength="40" autocomplete="off">
    <button type="submit" class="button"><?= e($t->text('messages.open')) ?></button>
</form>

<?php if ($conversations === []) : ?>
    <p class="muted"><?= e($t->text('messages.none')) ?></p>
<?php else : ?>
    <ul class="card-list">
        <?php foreach ($conversations as $conversation) : ?>
            <li class="card<?= $conversation['unread'] > 0 ? ' is-unread' : '' ?>">
                <a class="card-link" href="<?= e($basePath) ?>/messages/<?= e(rawurlencode($conversation['other_username'])) ?>">
                    <strong><?= e($conversation['other_username']) ?></strong>
                    <?php if ($conversation['unread'] > 0) : ?>
                        <span class="badge"><?= e($conversation['unread']) ?></span>
                    <?php endif; ?>
                    <span class="muted"><?= e(substr($conversation['last_at'], 0, 16)) ?></span>
                    <span class="message-preview"><?= e(mb_strimwidth($conversation['last_body'], 0, 80, '…')) ?></span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
