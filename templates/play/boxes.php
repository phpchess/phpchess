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

// The variables of one challenge tile (play/challenge_tile, A84).
$tileVariables = fn (Challenge $challenge): array => [
    'challenge' => $challenge, 'viewerId' => $viewerId, 'usernames' => $usernames,
    'basePath' => $basePath, 'csrfToken' => $csrfToken, 't' => $t,
];
?>
<div class="box-grid play-boxes" id="play-boxes" data-version="<?= e($boxesVersion) ?>">

    <!-- ===== My running games ===== -->
    <section class="box" aria-labelledby="box-games-title">
        <h2 id="box-games-title"><?= $template->render('partials/icon', ['icon' => 'games']) ?><?= e($t->text('play.my_games')) ?></h2>
        <?php if ($myGames === []) : ?>
            <p class="muted"><?= e($t->text('play.no_games')) ?></p>
        <?php else : ?>
            <ul class="tile-grid">
                <?php foreach ($myGames as $game) : ?>
                    <?= $template->render('play/game_tile', ['game' => $game, 'usernames' => $usernames, 'viewerId' => $viewerId, 'basePath' => $basePath, 't' => $t]) ?>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <div class="button-row">
            <a class="button button-secondary" href="<?= e($basePath) ?>/games"><?= e($t->text('games.archive_link')) ?></a>
        </div>
    </section>

    <!-- ===== Challenges sent to me ===== -->
    <section class="box" aria-labelledby="box-received-title">
        <h2 id="box-received-title"><?= $template->render('partials/icon', ['icon' => 'messages']) ?><?= e($t->text('play.received_challenges')) ?></h2>
        <?php if ($receivedChallenges === []) : ?>
            <p class="muted"><?= e($t->text('play.no_received_challenges')) ?></p>
        <?php else : ?>
            <ul class="tile-grid">
                <?php foreach ($receivedChallenges as $challenge) : ?>
                    <?= $template->render('play/challenge_tile', $tileVariables($challenge)) ?>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <!-- ===== Open challenges: mine (take back) and other players' (accept) ===== -->
    <section class="box" aria-labelledby="box-open-title">
        <h2 id="box-open-title"><?= $template->render('partials/icon', ['icon' => 'human']) ?><?= e($t->text('play.open_challenges')) ?></h2>
        <?php if ($myChallenges === [] && $openChallenges === []) : ?>
            <p class="muted"><?= e($t->text('play.no_challenges')) ?></p>
        <?php else : ?>
            <ul class="tile-grid">
                <?php foreach ([...$myChallenges, ...$openChallenges] as $challenge) : ?>
                    <?= $template->render('play/challenge_tile', $tileVariables($challenge)) ?>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>
