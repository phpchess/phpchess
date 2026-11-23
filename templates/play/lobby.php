<?php
/**
 * The Play page (G-05, G-06, G-20, G-21): the one-tap Play button, my
 * running games, challenges (mine, to me, and open ones), and the form for
 * a new challenge or invite link.
 *
 * Variables: $viewerId (int), $myGames (list of Game), $myChallenges and
 * $openChallenges (list of Challenge), $usernames (array of id => name),
 * $opponents (array of id => name), $playDefaults (array{days_per_move: int, rated: bool}),
 * $maximumDays (int), $isFenAllowed (bool), $timeControls (list of "5+3" texts),
 * $basePath, $csrfToken, $t, $template.
 *
 * Every form has a pace choice: correspondence (days per move) or a time
 * control (G-14). The page asks /play/news every few seconds (lobby.js):
 * that keeps my open timed challenges listed, and goes to the game as soon
 * as someone accepts one (G-16).
 */

use PhpChess\Domain\Play\Challenge;

$defaultDays = min($playDefaults['days_per_move'], $maximumDays);

// Mine and the ones sent to me, then the open ones of others (each once).
$listedChallenges = $myChallenges;
foreach ($openChallenges as $openChallenge) {
    if ($openChallenge->challengerId !== $viewerId) {
        $listedChallenges[] = $openChallenge;
    }
}
?>
<h1><?= e($t->text('play.title')) ?></h1>
<p><a href="<?= e($basePath) ?>/rules"><?= e($t->text('rules.link')) ?></a></p>

<form method="post" action="<?= e($basePath) ?>/play/quick" class="form play-now">
    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
    <button type="submit" class="button button-large"><?= e($t->text('play.button')) ?></button>
    <div class="form-row">
        <label><?= e($t->text('play.pace')) ?>
            <select name="time_control">
                <option value=""><?= e($t->text('play.pace_correspondence')) ?></option>
                <?php foreach ($timeControls as $control) : ?>
                    <option value="<?= e($control) ?>"><?= e($t->text('play.pace_timed', ['control' => $control])) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label><?= e($t->text('play.days_per_move')) ?>
            <input type="number" name="days_per_move" min="1" max="<?= e($maximumDays) ?>" value="<?= e($defaultDays) ?>">
        </label>
        <label class="checkbox"><input type="checkbox" name="rated" value="yes"<?= $playDefaults['rated'] ? ' checked' : '' ?>> <?= e($t->text('play.rated')) ?></label>
    </div>
    <p class="hint"><?= e($t->text('play.button_hint')) ?></p>
</form>

<h2><?= e($t->text('play.my_games')) ?></h2>
<?php if ($myGames === []) : ?>
    <p class="muted"><?= e($t->text('play.no_games')) ?></p>
<?php else : ?>
    <ul class="card-list">
        <?php foreach ($myGames as $game) : ?>
            <?= $template->render('play/game_card', ['game' => $game, 'usernames' => $usernames, 'viewerId' => $viewerId, 'basePath' => $basePath, 't' => $t]) ?>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
<p><a href="<?= e($basePath) ?>/games"><?= e($t->text('games.archive_link')) ?></a></p>

<h2><?= e($t->text('play.challenges')) ?></h2>
<?php if ($listedChallenges === []) : ?>
    <p class="muted"><?= e($t->text('play.no_challenges')) ?></p>
<?php else : ?>
    <ul class="card-list">
        <?php foreach ($listedChallenges as $challenge) : ?>
            <li class="card">
                <p class="card-title">
                    <?php if ($challenge->challengerId === $viewerId) : ?>
                        <?= e($t->text('challenge.yours.' . $challenge->kind, ['name' => $usernames[$challenge->opponentId ?? 0] ?? ''])) ?>
                    <?php else : ?>
                        <?= e($t->text('challenge.from', ['name' => $usernames[$challenge->challengerId] ?? '?'])) ?>
                    <?php endif; ?>
                </p>
                <p class="muted">
                    <?php if ($challenge->timeControl !== null) : ?>
                        <?= e($t->text('play.pace_timed', ['control' => $challenge->timeControl])) ?>
                    <?php else : ?>
                        <?= e($t->text('challenge.summary', ['days' => $challenge->daysPerMove])) ?>
                    <?php endif; ?>
                    · <?= e($t->text($challenge->isRated ? 'play.rated' : 'game.unrated')) ?>
                    · <?= e($t->text('challenge.colour.' . $challenge->colour)) ?>
                    <?php if ($challenge->startFen !== null) : ?>
                        · <?= e($t->text('challenge.custom_position')) ?>
                    <?php endif; ?>
                </p>
                <div class="card-actions">
                    <?php if ($challenge->challengerId === $viewerId) : ?>
                        <form method="post" action="<?= e($basePath) ?>/challenges/<?= e($challenge->id) ?>/cancel" class="inline-form">
                            <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                            <button type="submit" class="button-secondary"><?= e($t->text('challenge.cancel')) ?></button>
                        </form>
                    <?php else : ?>
                        <form method="post" action="<?= e($basePath) ?>/challenges/<?= e($challenge->id) ?>/accept" class="inline-form">
                            <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                            <button type="submit"><?= e($t->text('challenge.accept')) ?></button>
                        </form>
                        <?php if ($challenge->kind === Challenge::KIND_DIRECT) : ?>
                            <form method="post" action="<?= e($basePath) ?>/challenges/<?= e($challenge->id) ?>/decline" class="inline-form">
                                <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                                <button type="submit" class="button-secondary"><?= e($t->text('challenge.decline')) ?></button>
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<details class="new-challenge">
    <summary><?= e($t->text('challenge.new')) ?></summary>
    <form method="post" action="<?= e($basePath) ?>/challenges" class="form">
        <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
        <fieldset>
            <legend><?= e($t->text('challenge.kind')) ?></legend>
            <label class="checkbox"><input type="radio" name="kind" value="open" checked> <?= e($t->text('challenge.kind.open')) ?></label>
            <label class="checkbox"><input type="radio" name="kind" value="direct"> <?= e($t->text('challenge.kind.direct')) ?></label>
            <label class="checkbox"><input type="radio" name="kind" value="invite"> <?= e($t->text('challenge.kind.invite')) ?></label>
        </fieldset>
        <label><?= e($t->text('challenge.opponent')) ?>
            <input type="text" name="opponent" list="opponent-names" autocomplete="off">
        </label>
        <datalist id="opponent-names">
            <?php foreach ($opponents as $name) : ?>
                <option value="<?= e($name) ?>"></option>
            <?php endforeach; ?>
        </datalist>
        <p class="hint"><?= e($t->text('challenge.opponent_hint')) ?></p>
        <label><?= e($t->text('challenge.colour')) ?>
            <select name="colour">
                <?php foreach (Challenge::COLOURS as $colour) : ?>
                    <option value="<?= e($colour) ?>"><?= e($t->text('challenge.colour.' . $colour)) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label><?= e($t->text('play.pace')) ?>
            <select name="time_control">
                <option value=""><?= e($t->text('play.pace_correspondence')) ?></option>
                <?php foreach ($timeControls as $control) : ?>
                    <option value="<?= e($control) ?>"><?= e($t->text('play.pace_timed', ['control' => $control])) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label><?= e($t->text('play.days_per_move')) ?>: <output id="days-output"><?= e($defaultDays) ?></output>
            <input type="range" name="days_per_move" min="1" max="<?= e($maximumDays) ?>" value="<?= e($defaultDays) ?>" data-shows-in="days-output">
        </label>
        <label class="checkbox"><input type="checkbox" name="rated" value="yes" checked> <?= e($t->text('play.rated')) ?></label>
        <?php if ($isFenAllowed) : ?>
            <label><?= e($t->text('challenge.start_fen')) ?>
                <input type="text" name="start_fen" autocomplete="off" spellcheck="false">
            </label>
            <p class="hint"><?= e($t->text('challenge.start_fen_hint')) ?></p>
        <?php endif; ?>
        <button type="submit"><?= e($t->text('challenge.create')) ?></button>
    </form>
</details>
<script src="<?= e($basePath) ?>/assets/js/play/range-output.js" defer></script>
<script type="module" src="<?= e($basePath) ?>/assets/js/play/lobby.js" id="lobby-script"
        data-news-url="<?= e($basePath) ?>/play/news" data-base-path="<?= e($basePath) ?>"
        data-open-ids="<?= e(implode(',', array_map(fn ($challenge) => $challenge->id, $openChallenges))) ?>"></script>
