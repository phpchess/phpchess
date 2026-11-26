<?php
/**
 * The live boxes of the Play page (F2, A69, A70): my running games,
 * challenges sent to me, and open challenges (mine and other players').
 *
 * Rendered inside the Play page, and alone by GET /play/boxes: lobby.js
 * swaps this part in when "data-version" no longer matches the server's
 * (a move was made, a challenge came or went), without reloading the page.
 *
 * Variables: $viewerId (int), $myGames (list of Game), $receivedChallenges,
 * $myChallenges, $openChallenges (list of Challenge), $usernames (array of id => name),
 * $boxesVersion (string), $basePath, $csrfToken, $t, $template.
 */

use PhpChess\Domain\Play\Challenge;

// The text that says how fast and in what way a challenge is played.
$challengeDetails = function (Challenge $challenge) use ($t): string {
    $pace = $challenge->timeControl !== null
        ? $t->text('play.pace_timed', ['control' => $challenge->timeControl])
        : $t->text('challenge.summary', ['days' => $challenge->daysPerMove]);
    $details = [$pace, $t->text($challenge->isRated ? 'play.rated' : 'game.unrated'), $t->text('challenge.colour.' . $challenge->colour)];
    if ($challenge->startFen !== null) {
        $details[] = $t->text('challenge.custom_position');
    }

    return implode(' · ', $details);
};
?>
<div class="box-grid play-boxes" id="play-boxes" data-version="<?= e($boxesVersion) ?>">

    <!-- ===== My running games ===== -->
    <section class="box" aria-labelledby="box-games-title">
        <h2 id="box-games-title"><?= $template->render('partials/icon', ['name' => 'games']) ?><?= e($t->text('play.my_games')) ?></h2>
        <?php if ($myGames === []) : ?>
            <p class="muted"><?= e($t->text('play.no_games')) ?></p>
        <?php else : ?>
            <ul class="card-list">
                <?php foreach ($myGames as $game) : ?>
                    <?= $template->render('play/game_card', ['game' => $game, 'usernames' => $usernames, 'viewerId' => $viewerId, 'basePath' => $basePath, 't' => $t]) ?>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <div class="button-row">
            <a class="button button-secondary" href="<?= e($basePath) ?>/games"><?= e($t->text('games.archive_link')) ?></a>
        </div>
    </section>

    <!-- ===== Challenges sent to me ===== -->
    <section class="box" aria-labelledby="box-received-title">
        <h2 id="box-received-title"><?= $template->render('partials/icon', ['name' => 'messages']) ?><?= e($t->text('play.received_challenges')) ?></h2>
        <?php if ($receivedChallenges === []) : ?>
            <p class="muted"><?= e($t->text('play.no_received_challenges')) ?></p>
        <?php else : ?>
            <ul class="card-list">
                <?php foreach ($receivedChallenges as $challenge) : ?>
                    <li class="card">
                        <p class="card-title"><?= e($t->text('challenge.from', ['name' => $usernames[$challenge->challengerId] ?? '?'])) ?></p>
                        <p class="muted"><?= e($challengeDetails($challenge)) ?></p>
                        <div class="card-actions">
                            <form method="post" action="<?= e($basePath) ?>/challenges/<?= e($challenge->id) ?>/accept" class="inline-form">
                                <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                                <button type="submit"><?= e($t->text('challenge.accept')) ?></button>
                            </form>
                            <form method="post" action="<?= e($basePath) ?>/challenges/<?= e($challenge->id) ?>/decline" class="inline-form">
                                <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                                <button type="submit" class="button-secondary"><?= e($t->text('challenge.decline')) ?></button>
                            </form>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <!-- ===== Open challenges: mine (take back) and other players' (accept) ===== -->
    <section class="box" aria-labelledby="box-open-title">
        <h2 id="box-open-title"><?= $template->render('partials/icon', ['name' => 'human']) ?><?= e($t->text('play.open_challenges')) ?></h2>
        <?php if ($myChallenges === [] && $openChallenges === []) : ?>
            <p class="muted"><?= e($t->text('play.no_challenges')) ?></p>
        <?php else : ?>
            <ul class="card-list">
                <?php foreach ($myChallenges as $challenge) : ?>
                    <li class="card card-mine">
                        <p class="card-title"><?= e($t->text('challenge.yours.' . $challenge->kind, ['name' => $usernames[$challenge->opponentId ?? 0] ?? ''])) ?></p>
                        <p class="muted"><?= e($challengeDetails($challenge)) ?></p>
                        <div class="card-actions">
                            <form method="post" action="<?= e($basePath) ?>/challenges/<?= e($challenge->id) ?>/cancel" class="inline-form">
                                <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                                <button type="submit" class="button-secondary"><?= e($t->text('challenge.cancel')) ?></button>
                            </form>
                        </div>
                    </li>
                <?php endforeach; ?>
                <?php foreach ($openChallenges as $challenge) : ?>
                    <li class="card">
                        <p class="card-title"><?= e($t->text('challenge.from', ['name' => $usernames[$challenge->challengerId] ?? '?'])) ?></p>
                        <p class="muted"><?= e($challengeDetails($challenge)) ?></p>
                        <div class="card-actions">
                            <form method="post" action="<?= e($basePath) ?>/challenges/<?= e($challenge->id) ?>/accept" class="inline-form">
                                <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                                <button type="submit"><?= e($t->text('challenge.accept')) ?></button>
                            </form>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>
