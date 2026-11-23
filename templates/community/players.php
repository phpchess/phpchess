<?php
/**
 * Players (C-03, C-10): my buddies, and who is online now.
 *
 * Variables: $buddies and $online (lists of array{id, username, last_seen_at}),
 * $showOnline (bool: switch "player list visibility"), $onlineSince (int),
 * $basePath, $csrfToken, $t.
 */
?>
<h1><?= e($t->text('players.title')) ?></h1>
<p><a href="<?= e($basePath) ?>/account/profile"><?= e($t->text('profile.edit_title')) ?></a></p>

<section aria-labelledby="buddies-title">
    <h2 id="buddies-title"><?= e($t->text('players.buddies')) ?></h2>
    <?php if ($buddies === []) : ?>
        <p class="muted"><?= e($t->text('players.no_buddies')) ?></p>
    <?php else : ?>
        <ul class="text-list">
            <?php foreach ($buddies as $buddy) : ?>
                <li>
                    <a href="<?= e($basePath) ?>/players/<?= e(rawurlencode($buddy['username'])) ?>"><?= e($buddy['username']) ?></a>
                    <?php if ($buddy['last_seen_at'] !== null && $buddy['last_seen_at'] >= $onlineSince) : ?>
                        <span class="online-dot" aria-label="<?= e($t->text('players.online_now')) ?>"></span>
                    <?php endif; ?>
                    <form method="post" action="<?= e($basePath) ?>/players/<?= e(rawurlencode($buddy['username'])) ?>/unbuddy" class="inline-form">
                        <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                        <button type="submit" class="link-button"><?= e($t->text('players.remove_buddy')) ?></button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

<?php if ($showOnline) : ?>
    <section aria-labelledby="online-title">
        <h2 id="online-title"><?= e($t->text('players.online')) ?></h2>
        <?php if ($online === []) : ?>
            <p class="muted"><?= e($t->text('players.nobody_online')) ?></p>
        <?php else : ?>
            <ul class="text-list">
                <?php foreach ($online as $player) : ?>
                    <li><a href="<?= e($basePath) ?>/players/<?= e(rawurlencode($player['username'])) ?>"><?= e($player['username']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
<?php endif; ?>
