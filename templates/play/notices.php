<?php
/**
 * In-app notices (N-01): the newest first, unread ones in bold.
 *
 * Variables: $notices (list of array{id, kind, game_id, challenge_id,
 * from_username, created_at, is_read}), $basePath, $csrfToken, $t.
 */
?>
<h1><?= e($t->text('notices.title')) ?></h1>

<?php if ($notices === []) : ?>
    <p class="muted"><?= e($t->text('notices.none')) ?></p>
<?php else : ?>
    <form method="post" action="<?= e($basePath) ?>/notices/read-all" class="inline-form">
        <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
        <button type="submit" class="button-secondary"><?= e($t->text('notices.mark_all_read')) ?></button>
    </form>
    <ul class="card-list">
        <?php foreach ($notices as $notice) : ?>
            <?php $link = $notice['game_id'] !== null ? '/games/' . $notice['game_id'] : '/play'; ?>
            <li class="card<?= $notice['is_read'] ? '' : ' notice-unread' ?>">
                <a href="<?= e($basePath . $link) ?>"><?= e($t->text('notice.' . $notice['kind'], ['name' => $notice['from_username']])) ?></a>
                <span class="muted"><?= e(substr($notice['created_at'], 0, 16)) ?> UTC</span>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
