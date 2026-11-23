<?php
/**
 * The start page: a welcome for visitors; for members the Play button and a link to their games (G-20).
 *
 * For members also the games waiting for their move, 8 to a page, each
 * with a small picture of the position.
 *
 * Variables: $version (string), $registrationIsOpen (bool), $user (User|null),
 * $waitingGames (list of Game), $usernames (array of id => name),
 * $page and $pageCount (int), $basePath, $t (Translator), $template.
 */
?>
<h1><?= e($t->text('home.heading')) ?></h1>

<?php if ($user === null) : ?>
    <p><?= e($t->text('home.welcome_visitor')) ?></p>
    <p class="button-row">
        <a class="button" href="<?= e($basePath) ?>/login"><?= e($t->text('layout.login')) ?></a>
        <?php if ($registrationIsOpen) : ?>
            <a class="button button-secondary" href="<?= e($basePath) ?>/register"><?= e($t->text('register.title')) ?></a>
        <?php endif; ?>
    </p>
<?php else : ?>
    <p><?= e($t->text('home.welcome_member', ['name' => $user->username])) ?></p>
    <p class="button-row">
        <a class="button button-large" href="<?= e($basePath) ?>/play"><?= e($t->text('play.button')) ?></a>
        <a class="button button-secondary" href="<?= e($basePath) ?>/games"><?= e($t->text('games.title')) ?></a>
    </p>

    <h2><?= e($t->text('home.your_turn')) ?></h2>
    <?php if ($waitingGames === []) : ?>
        <p class="muted"><?= e($t->text('home.no_games_waiting')) ?></p>
    <?php else : ?>
        <ul class="card-list game-card-grid">
            <?php foreach ($waitingGames as $game) : ?>
                <?= $template->render('play/game_card', ['game' => $game, 'usernames' => $usernames, 'viewerId' => $user->id, 'basePath' => $basePath, 't' => $t]) ?>
            <?php endforeach; ?>
        </ul>
        <?php if ($pageCount > 1) : ?>
            <nav class="pager" aria-label="<?= e($t->text('home.pages')) ?>">
                <?php if ($page > 1) : ?>
                    <a class="button button-secondary" href="<?= e($basePath) ?>/?page=<?= e($page - 1) ?>" aria-label="<?= e($t->text('home.previous_page')) ?>">‹</a>
                <?php endif; ?>
                <span><?= e($t->text('home.page_of', ['page' => $page, 'count' => $pageCount])) ?></span>
                <?php if ($page < $pageCount) : ?>
                    <a class="button button-secondary" href="<?= e($basePath) ?>/?page=<?= e($page + 1) ?>" aria-label="<?= e($t->text('home.next_page')) ?>">›</a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
<?php endif; ?>

<p class="muted"><?= e($t->text('home.version', ['version' => $version])) ?></p>
