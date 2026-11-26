<?php
/**
 * The start page (G-20, A44).
 *
 * Visitors: a welcome, log in and register, the tip of the day and the news.
 * Members: the dashboard in two columns (one on a phone, in this order):
 *   left   "My move" tiles with mini boards (8 to a page), "My games" where
 *          the opponent is to move;
 *   right  challenges for me, open challenges with one-tap Accept, live
 *          games to watch, my recent finished games: text lists;
 *   below  the tip of the day and the news.
 *
 * Variables: $version (string), $registrationIsOpen (bool), $user (User|null),
 * $news (list of content items), $tip (content item|null);
 * for members also $waitingGames, $theirMoveGames, $finishedGames, $liveGames
 * (lists of Game), $challengesForMe, $openChallenges (lists of Challenge),
 * $usernames (array of id => name), $page and $pageCount (int);
 * $basePath, $csrfToken, $t, $template.
 */

$resultText = function ($game) use ($t): string {
    return $game->result === null ? '' : $t->text('result.' . $game->result);
};
?>
<?php if ($user === null) : ?>
    <h1><?= e($t->text('home.heading')) ?></h1>
    <p><?= e($t->text('home.welcome_visitor')) ?></p>
    <p class="button-row">
        <a class="button" href="<?= e($basePath) ?>/login"><?= e($t->text('layout.login')) ?></a>
        <?php if ($registrationIsOpen) : ?>
            <a class="button button-secondary" href="<?= e($basePath) ?>/register"><?= e($t->text('register.title')) ?></a>
        <?php endif; ?>
    </p>
<?php else : ?>
    <div class="dashboard-top">
        <h1 class="visually-hidden"><?= e($t->text('home.heading')) ?></h1>
        <p class="button-row">
            <a class="button button-large" href="<?= e($basePath) ?>/play"><?= e($t->text('play.button')) ?></a>
            <a class="button button-secondary" href="<?= e($basePath) ?>/games"><?= e($t->text('games.title')) ?></a>
        </p>
    </div>

    <div class="dashboard">
        <div class="dashboard-main">
            <section aria-labelledby="my-move-title">
                <h2 id="my-move-title"><?= e($t->text('home.your_turn')) ?></h2>
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
            </section>

            <section aria-labelledby="their-move-title">
                <h2 id="their-move-title"><?= e($t->text('home.their_move')) ?></h2>
                <?php if ($theirMoveGames === []) : ?>
                    <p class="muted"><?= e($t->text('home.no_games_their_move')) ?></p>
                <?php else : ?>
                    <ul class="card-list game-card-grid">
                        <?php foreach ($theirMoveGames as $game) : ?>
                            <?= $template->render('play/game_card', ['game' => $game, 'usernames' => $usernames, 'viewerId' => $user->id, 'basePath' => $basePath, 't' => $t]) ?>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>
        </div>

        <div class="dashboard-side">
            <?php if ($challengesForMe !== []) : ?>
                <section aria-labelledby="for-me-title">
                    <h2 id="for-me-title"><?= e($t->text('home.challenges_for_me')) ?></h2>
                    <ul class="text-list">
                        <?php foreach ($challengesForMe as $challenge) : ?>
                            <li>
                                <span><?= e($t->text('challenge.from', ['name' => $usernames[$challenge->challengerId] ?? '?'])) ?>
                                    · <?= e($t->text('challenge.summary', ['days' => $challenge->daysPerMove])) ?></span>
                                <form method="post" action="<?= e($basePath) ?>/challenges/<?= e($challenge->id) ?>/accept" class="inline-form">
                                    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                                    <button type="submit" class="button button-small"><?= e($t->text('challenge.accept')) ?></button>
                                </form>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endif; ?>

            <section aria-labelledby="open-title">
                <h2 id="open-title"><?= e($t->text('home.open_challenges')) ?></h2>
                <?php if ($openChallenges === []) : ?>
                    <p class="muted"><?= e($t->text('home.no_open_challenges')) ?></p>
                <?php else : ?>
                    <ul class="text-list">
                        <?php foreach ($openChallenges as $challenge) : ?>
                            <li>
                                <span>
                                    <?= e($usernames[$challenge->challengerId] ?? '?') ?>
                                    · <?= e($t->text('challenge.summary', ['days' => $challenge->daysPerMove])) ?>
                                    · <?= e($t->text($challenge->isRated ? 'play.rated' : 'game.unrated')) ?>
                                </span>
                                <?php if ($challenge->challengerId === $user->id) : ?>
                                    <span class="muted"><?= e($t->text('challenge.yours.open')) ?></span>
                                <?php else : ?>
                                    <form method="post" action="<?= e($basePath) ?>/challenges/<?= e($challenge->id) ?>/accept" class="inline-form">
                                        <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                                        <button type="submit" class="button button-small"><?= e($t->text('challenge.accept')) ?></button>
                                    </form>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>

            <section aria-labelledby="live-title">
                <h2 id="live-title"><?= e($t->text('home.live_games')) ?></h2>
                <?php if ($liveGames === []) : ?>
                    <p class="muted"><?= e($t->text('home.no_live_games')) ?></p>
                <?php else : ?>
                    <ul class="text-list">
                        <?php foreach ($liveGames as $game) : ?>
                            <li><a href="<?= e($basePath) ?>/games/<?= e($game->id) ?>"><?= e(($usernames[$game->whiteId] ?? '?') . ' – ' . ($usernames[$game->blackId] ?? '?')) ?></a>
                                <span class="muted"><?= e($t->text('home.move_number', ['number' => intdiv($game->ply, 2) + 1])) ?></span></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>

            <section aria-labelledby="finished-title">
                <h2 id="finished-title"><?= e($t->text('home.recent_games')) ?></h2>
                <?php if ($finishedGames === []) : ?>
                    <p class="muted"><?= e($t->text('home.no_recent_games')) ?></p>
                <?php else : ?>
                    <ul class="text-list">
                        <?php foreach ($finishedGames as $game) : ?>
                            <li><a href="<?= e($basePath) ?>/games/<?= e($game->id) ?>"><?= e(($usernames[$game->whiteId] ?? '?') . ' – ' . ($usernames[$game->blackId] ?? '?')) ?></a>
                                <span class="muted"><?= e($resultText($game)) ?></span></li>
                        <?php endforeach; ?>
                    </ul>
                    <p class="button-row"><a class="button button-secondary" href="<?= e($basePath) ?>/games"><?= e($t->text('home.all_games')) ?></a></p>
                <?php endif; ?>
            </section>
        </div>
    </div>
<?php endif; ?>

<?php if ($tip !== null) : ?>
    <aside class="tip-of-the-day" aria-labelledby="tip-title">
        <h2 id="tip-title"><?= e($t->text('content.tip_of_the_day')) ?>: <?= e($tip['title']) ?></h2>
        <?= $template->render('content/text', ['text' => $tip['body']]) ?>
    </aside>
<?php endif; ?>

<?php if ($news !== []) : ?>
    <section class="news" aria-labelledby="news-title">
        <h2 id="news-title"><?= e($t->text('content.news')) ?></h2>
        <?php foreach ($news as $item) : ?>
            <article>
                <h3><?= e($item['title']) ?> <span class="muted"><?= e(substr($item['created_at'], 0, 10)) ?></span></h3>
                <?= $template->render('content/text', ['text' => $item['body']]) ?>
            </article>
        <?php endforeach; ?>
        <p class="button-row"><a class="button button-secondary" href="<?= e($basePath) ?>/news"><?= e($t->text('content.all_news')) ?></a></p>
    </section>
<?php endif; ?>

<p class="muted"><?= e($t->text('home.version', ['version' => $version])) ?></p>
