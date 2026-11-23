<?php
/**
 * One conversation (C-04): the messages, oldest at the top, and the form
 * to write. While the page is open, messages.js asks every few seconds for
 * new ones and adds them (A41: live while on the site).
 *
 * Variables: $other (User), $messages (list of array{id, from_user_id, body, created_at}),
 * $mayWrite (bool), $lastId (int), $user (User), $basePath, $csrfToken, $t.
 */
?>
<h1><?= e($t->text('messages.with', ['name' => $other->username])) ?></h1>
<p><a href="<?= e($basePath) ?>/messages">‹ <?= e($t->text('messages.title')) ?></a></p>

<ol class="message-list" id="message-list"
    data-poll-url="<?= e($basePath) ?>/messages/<?= e(rawurlencode($other->username)) ?>/new"
    data-last-id="<?= e($lastId) ?>">
    <?php foreach ($messages as $message) : ?>
        <li class="message<?= $message['from_user_id'] === $user->id ? ' is-mine' : '' ?>">
            <p><?= e($message['body']) ?></p>
            <span class="muted"><?= e(substr($message['created_at'], 0, 16)) ?></span>
        </li>
    <?php endforeach; ?>
</ol>
<span id="newest"></span>

<?php if ($mayWrite) : ?>
    <form method="post" action="<?= e($basePath) ?>/messages/<?= e(rawurlencode($other->username)) ?>" class="form message-form">
        <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
        <label for="body" class="visually-hidden"><?= e($t->text('messages.write')) ?></label>
        <textarea id="body" name="body" rows="3" maxlength="2000" required placeholder="<?= e($t->text('messages.write')) ?>"></textarea>
        <button type="submit" class="button"><?= e($t->text('messages.send')) ?></button>
    </form>
<?php else : ?>
    <p class="muted"><?= e($t->text('messages.cannot_write')) ?></p>
<?php endif; ?>
<script type="module" src="<?= e($basePath) ?>/assets/js/app/messages.js"></script>
