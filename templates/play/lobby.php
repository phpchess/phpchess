<?php
/**
 * The Play page (F2: A69, A70; G-05, G-16, G-20).
 *
 * From top to bottom:
 * 1. three big tiles: Computer, Live, Correspondence. Each opens the new
 *    game pop-up with its own choices already picked;
 * 2. the live boxes (play/boxes): my games, challenges sent to me, open
 *    challenges. lobby.js keeps them up to date without reloading the page;
 * 3. news from the admins, and (later) statistics.
 *
 * The new game pop-up is one form of choice buttons, no text fields:
 * who (computer or person), how long, what strength, what colour, rated or
 * not. It works without JavaScript too: each tile is then a link to
 * "/play?new=..." and the page opens with the pop-up showing.
 *
 * Variables: everything play/boxes needs, plus
 *   $adminNews (list of array{title: string, body: string, created_at: string}),
 *   $openPopUp (string: "", "computer", "live" or "correspondence"),
 *   $playDefaults (array{days_per_move: int, rated: bool}),
 *   $timeControls (list of "5+3" texts), $correspondenceDays (list of int),
 *   $computerBands (array of name => [lowest, highest]), $ratingRanges (list of int),
 *   $basePath, $csrfToken, $t, $template.
 */

// ===== Which choices the pop-up starts with =====
$startsWithPerson = in_array($openPopUp, ['live', 'correspondence'], true);
$startsLive = $openPopUp === 'live' && $timeControls !== [];
$defaultDays = in_array($playDefaults['days_per_move'], $correspondenceDays, true)
    ? $playDefaults['days_per_move']
    : $correspondenceDays[min(1, count($correspondenceDays) - 1)];
$defaultPace = $startsLive ? 'live:' . $timeControls[0] : 'days:' . $defaultDays;

$tiles = [
    'computer' => ['icon' => 'computer', 'title' => 'new_game.tile.computer', 'hint' => 'new_game.tile.computer_hint'],
    'live' => ['icon' => 'live', 'title' => 'new_game.tile.live', 'hint' => 'new_game.tile.live_hint'],
    'correspondence' => ['icon' => 'correspondence', 'title' => 'new_game.tile.correspondence', 'hint' => 'new_game.tile.correspondence_hint'],
];
if ($timeControls === []) {
    unset($tiles['live']);
}
?>
<h1><?= e($t->text('play.title')) ?></h1>

<!--
    ===== 1. The tiles: big icon buttons, each opens the new game pop-up =====
    The name is only read out (aria-label) and shown when pointing at it (title):
    the icon itself says what the tile is (user, F2 review).
-->
<div class="play-tiles">
    <?php foreach ($tiles as $kind => $tile) : ?>
        <a class="play-tile" href="<?= e($basePath) ?>/play?new=<?= e($kind) ?>" data-new-game="<?= e($kind) ?>"
           aria-label="<?= e($t->text($tile['title'])) ?>" title="<?= e($t->text($tile['title']) . ': ' . $t->text($tile['hint'])) ?>">
            <?= $template->render('partials/icon', ['icon' => $tile['icon'], 'size' => 64]) ?>
        </a>
    <?php endforeach; ?>
    <a class="play-tile play-tile-secondary" href="<?= e($basePath) ?>/play/challenge"
       aria-label="<?= e($t->text('new_game.more_choices')) ?>" title="<?= e($t->text('new_game.more_choices')) ?>">
        <?= $template->render('partials/icon', ['icon' => 'more', 'size' => 64]) ?>
    </a>
</div>

<!-- ===== 2. The live boxes ===== -->
<?= $template->render('play/boxes', [
    'viewerId' => $viewerId, 'myGames' => $myGames, 'receivedChallenges' => $receivedChallenges,
    'myChallenges' => $myChallenges, 'openChallenges' => $openChallenges, 'usernames' => $usernames,
    'boxesVersion' => $boxesVersion, 'basePath' => $basePath, 'csrfToken' => $csrfToken, 't' => $t,
]) ?>

<!-- ===== 3. News from the admins (and, later, statistics) ===== -->
<?php if ($adminNews !== []) : ?>
    <section class="box play-news" aria-labelledby="box-news-title">
        <h2 id="box-news-title"><?= $template->render('partials/icon', ['icon' => 'news']) ?><?= e($t->text('play.admin_news')) ?></h2>
        <ul class="card-list">
            <?php foreach ($adminNews as $item) : ?>
                <li class="card">
                    <p class="card-title"><?= e($item['title']) ?></p>
                    <p class="muted"><?= e(mb_strimwidth($item['body'], 0, 160, '…')) ?></p>
                </li>
            <?php endforeach; ?>
        </ul>
        <div class="button-row">
            <a class="button button-secondary" href="<?= e($basePath) ?>/news"><?= e($t->text('play.all_news')) ?></a>
        </div>
    </section>
<?php endif; ?>
<?php /* Statistics (later, F2 A70): a box here, like the news box above. */ ?>

<!-- ===== The new game pop-up ===== -->
<dialog class="pop-up pop-up-large" id="new-game" aria-labelledby="new-game-title"<?= $openPopUp !== '' ? ' open' : '' ?>>
    <form method="post" action="<?= e($basePath) ?>/play/start" class="new-game-form">
        <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">

        <div class="pop-up-head">
            <h2 id="new-game-title"><?= e($t->text('new_game.title')) ?></h2>
            <a class="button button-secondary icon-button" href="<?= e($basePath) ?>/play" data-close-pop-up aria-label="<?= e($t->text('new_game.close')) ?>" title="<?= e($t->text('new_game.close')) ?>"><?= $template->render('partials/icon', ['icon' => 'close']) ?></a>
        </div>

        <div class="pop-up-body">
            <!-- Step 1: who -->
            <fieldset class="choice-group choice-group-large">
                <legend><?= e($t->text('new_game.opponent')) ?></legend>
                <label class="choice"><input type="radio" name="opponent" value="computer"<?= $startsWithPerson ? '' : ' checked' ?>>
                    <?= $template->render('partials/icon', ['icon' => 'computer', 'size' => 32]) ?><?= e($t->text('new_game.opponent.computer')) ?></label>
                <label class="choice"><input type="radio" name="opponent" value="human"<?= $startsWithPerson ? ' checked' : '' ?>>
                    <?= $template->render('partials/icon', ['icon' => 'human', 'size' => 32]) ?><?= e($t->text('new_game.opponent.human')) ?></label>
            </fieldset>

            <!-- Step 2 (person only): how long -->
            <?php if ($timeControls !== []) : ?>
                <fieldset class="choice-group for-human">
                    <legend><?= $template->render('partials/icon', ['icon' => 'live']) ?><?= e($t->text('new_game.live')) ?></legend>
                    <?php foreach ($timeControls as $control) : ?>
                        <label class="choice"><input type="radio" name="pace" value="live:<?= e($control) ?>"<?= $defaultPace === 'live:' . $control ? ' checked' : '' ?>>
                            <?= e($control) ?><small><?= e($t->text('new_game.minutes_seconds')) ?></small></label>
                    <?php endforeach; ?>
                </fieldset>
            <?php endif; ?>
            <fieldset class="choice-group for-human">
                <legend><?= $template->render('partials/icon', ['icon' => 'correspondence']) ?><?= e($t->text('new_game.correspondence')) ?></legend>
                <?php foreach ($correspondenceDays as $days) : ?>
                    <label class="choice"><input type="radio" name="pace" value="days:<?= e($days) ?>"<?= $defaultPace === 'days:' . $days ? ' checked' : '' ?>>
                        <?= e($t->text('new_game.days', ['days' => $days])) ?><small><?= e($t->text('new_game.per_move')) ?></small></label>
                <?php endforeach; ?>
            </fieldset>

            <!-- Step 3: what strength -->
            <fieldset class="choice-group for-computer">
                <legend><?= $template->render('partials/icon', ['icon' => 'strength']) ?><?= e($t->text('new_game.strength')) ?></legend>
                <?php $isFirst = true; ?>
                <?php foreach ($computerBands as $band => [$lowest, $highest]) : ?>
                    <label class="choice"><input type="radio" name="band" value="<?= e($band) ?>"<?= $isFirst ? ' checked' : '' ?>>
                        <?= e($t->text('new_game.band.' . $band)) ?><small><?= e($highest >= 3000 ? $t->text('new_game.rating_from', ['low' => $lowest]) : $t->text('new_game.rating_between', ['low' => $lowest, 'high' => $highest])) ?></small></label>
                    <?php $isFirst = false; ?>
                <?php endforeach; ?>
            </fieldset>
            <div class="button-row for-computer">
                <a class="button button-secondary" href="<?= e($basePath) ?>/play/computer"><?= $template->render('partials/icon', ['icon' => 'players']) ?><?= e($t->text('new_game.pick_computer')) ?></a>
            </div>
            <fieldset class="choice-group for-human">
                <legend><?= $template->render('partials/icon', ['icon' => 'strength']) ?><?= e($t->text('new_game.opponent_strength')) ?></legend>
                <?php foreach ($ratingRanges as $range) : ?>
                    <label class="choice"><input type="radio" name="rating_range" value="<?= e($range) ?>"<?= $range === 0 ? ' checked' : '' ?>>
                        <?= e($range === 0 ? $t->text('new_game.range_any') : $t->text('new_game.range', ['points' => $range])) ?></label>
                <?php endforeach; ?>
            </fieldset>

            <!-- Step 4: what colour -->
            <fieldset class="choice-group">
                <legend><?= $template->render('partials/icon', ['icon' => 'colour']) ?><?= e($t->text('new_game.colour')) ?></legend>
                <?php foreach (['white', 'random', 'black'] as $colour) : ?>
                    <label class="choice"><input type="radio" name="colour" value="<?= e($colour) ?>"<?= $colour === 'random' ? ' checked' : '' ?>>
                        <?= $template->render('partials/icon', ['icon' => $colour, 'size' => 28]) ?><?= e($t->text('new_game.colour.' . $colour)) ?></label>
                <?php endforeach; ?>
            </fieldset>

            <!-- Step 5 (person only): rated or not -->
            <fieldset class="choice-group for-human">
                <legend><?= $template->render('partials/icon', ['icon' => 'rated']) ?><?= e($t->text('new_game.rated')) ?></legend>
                <label class="choice"><input type="radio" name="rated" value="yes"<?= $playDefaults['rated'] ? ' checked' : '' ?>> <?= e($t->text('play.rated')) ?></label>
                <label class="choice"><input type="radio" name="rated" value="no"<?= $playDefaults['rated'] ? '' : ' checked' ?>> <?= e($t->text('game.unrated')) ?></label>
            </fieldset>
            <p class="hint for-computer"><?= e($t->text('new_game.computer_note')) ?></p>
        </div>

        <div class="pop-up-foot">
            <button type="submit"><?= $template->render('partials/icon', ['icon' => 'play']) ?><?= e($t->text('new_game.start')) ?></button>
        </div>
    </form>
</dialog>

<script type="module" src="<?= e($basePath) ?>/assets/js/play/lobby.js" id="lobby-script"
        data-news-url="<?= e($basePath) ?>/play/news" data-boxes-url="<?= e($basePath) ?>/play/boxes"
        data-base-path="<?= e($basePath) ?>"></script>
