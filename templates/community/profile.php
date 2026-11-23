<?php
/**
 * A player's profile (C-01): avatar, the text about them, rating, last
 * seen, recent games, and buttons to message them or keep them as a buddy.
 *
 * Variables: $player (User), $profile (array{avatar, about}), $rating (array{rating, games_played, wins, draws, losses}|null),
 * $finishedGames (list of Game), $usernames (array of id => name), $isBuddy, $isSelf, $messagesAreOn (bool),
 * $lastSeenAt (int|null), $user (User|null), $basePath, $csrfToken, $t, $template.
 */
?>
<div class="profile-head">
    <?= $template->render('community/avatar', ['userId' => $player->id, 'username' => $player->username, 'avatar' => $profile['avatar'], 'size' => 128, 'basePath' => $basePath]) ?>
    <div>
        <h1><?= e($player->username) ?></h1>
        <?php if ($rating !== null) : ?>
            <p><?= e($t->text('profile.rating', ['rating' => $rating['rating'], 'games' => $rating['games_played']])) ?></p>
        <?php endif; ?>
        <?php if ($lastSeenAt !== null) : ?>
            <p class="muted"><?= e($t->text('profile.last_seen', ['date' => gmdate('Y-m-d', $lastSeenAt)])) ?></p>
        <?php endif; ?>
    </div>
</div>

<?php if ($profile['about'] !== '') : ?>
    <section class="profile-about"><?= $template->render('content/text', ['text' => $profile['about']]) ?></section>
<?php endif; ?>

<?php if ($user !== null && !$isSelf) : ?>
    <p class="button-row">
        <?php if ($messagesAreOn) : ?>
            <a class="button" href="<?= e($basePath) ?>/messages/<?= e(rawurlencode($player->username)) ?>"><?= e($t->text('profile.write_message')) ?></a>
        <?php endif; ?>
        <form method="post" action="<?= e($basePath) ?>/players/<?= e(rawurlencode($player->username)) ?>/<?= $isBuddy ? 'unbuddy' : 'buddy' ?>" class="inline-form">
            <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
            <button type="submit" class="button button-secondary"><?= e($t->text($isBuddy ? 'players.remove_buddy' : 'players.add_buddy')) ?></button>
        </form>
    </p>
<?php elseif ($isSelf) : ?>
    <p><a class="button button-secondary" href="<?= e($basePath) ?>/account/profile"><?= e($t->text('profile.edit_title')) ?></a></p>
<?php endif; ?>

<?php if ($finishedGames !== []) : ?>
    <h2><?= e($t->text('home.recent_games')) ?></h2>
    <ul class="text-list">
        <?php foreach ($finishedGames as $game) : ?>
            <li><a href="<?= e($basePath) ?>/games/<?= e($game->id) ?>"><?= e(($usernames[$game->whiteId] ?? '?') . ' – ' . ($usernames[$game->blackId] ?? '?')) ?></a>
                <span class="muted"><?= e($game->result === null ? '' : $t->text('result.' . $game->result)) ?></span></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
