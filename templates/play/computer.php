<?php
/**
 * Play the computer (AI-03, AI-06, decision A53): every computer player as a
 * card with face, name, rating, personality and bio, and a button to start an
 * unrated game against it. One column on a phone (A18).
 *
 * Variables: $players (list of ComputerPlayer, weakest first),
 * $faces (array of player id => face URL, or null when the player has no face yet),
 * $basePath, $csrfToken, $t.
 */

?>
<h1><?= e($t->text('computer.title')) ?></h1>
<p><?= e($t->text('computer.intro')) ?></p>

<ul class="card-list computer-players">
    <?php foreach ($players as $player) : ?>
        <li class="card computer-player">
            <!-- ===== Who the player is ===== -->
            <?php if ($faces[$player->id] !== null) : ?>
                <img class="computer-face" src="<?= e($faces[$player->id]) ?>" alt="" width="100" height="150" loading="lazy">
            <?php else : ?>
                <img class="computer-face" src="<?= e($basePath) ?>/assets/avatars/knight.svg" alt="" width="100" height="100" loading="lazy">
            <?php endif; ?>
            <p class="card-title"><?= e($player->name) ?> <span class="badge"><?= e($t->text('computer.label')) ?></span></p>
            <p class="muted">
                <?= e($t->text('computer.rating', ['elo' => $player->elo])) ?>
                · <?= e($t->text('computer.personality.' . $player->personality)) ?>
                <?php if ($player->age !== null) : ?>
                    · <?= e($t->text('computer.age', ['age' => $player->age])) ?>
                <?php endif; ?>
            </p>
            <p><?= e($player->bio) ?></p>
            <?php if ($player->slowOnPhones) : ?>
                <p class="hint"><?= e($t->text('computer.slow_on_phones')) ?></p>
            <?php endif; ?>

            <!-- ===== Starting a game ===== -->
            <form method="post" action="<?= e($basePath) ?>/play/computer" class="card-actions inline-form">
                <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                <input type="hidden" name="player" value="<?= e($player->id) ?>">
                <label><?= e($t->text('computer.colour')) ?>
                    <select name="colour">
                        <?php foreach (['random', 'w', 'b'] as $colour) : ?>
                            <option value="<?= e($colour) ?>"><?= e($t->text('computer.colour.' . $colour)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <button type="submit" class="button"><?= e($t->text('computer.play', ['name' => $player->name])) ?></button>
            </form>
        </li>
    <?php endforeach; ?>
</ul>
