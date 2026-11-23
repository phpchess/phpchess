<?php
/**
 * The rating list (R-04): rated players best first, with their statistics.
 * Players with fewer than 3 games ("newbies", A30) are not listed yet.
 *
 * Variables: $speed (string), $players (list of array{user_id, username,
 * rating, games_played, wins, draws, losses}), $basePath, $t.
 */

use PhpChess\Domain\Rating\EloCalculator;

?>
<h1><?= e($t->text('ratings.title')) ?></h1>
<p class="muted"><?= e($t->text('ratings.speed.' . $speed)) ?> · <?= e($t->text('ratings.explain')) ?></p>

<?php if ($players === []) : ?>
    <p><?= e($t->text('ratings.none')) ?></p>
<?php else : ?>
    <table class="data-table">
        <thead>
            <tr>
                <th scope="col">#</th>
                <th scope="col"><?= e($t->text('ratings.player')) ?></th>
                <th scope="col"><?= e($t->text('ratings.rating')) ?></th>
                <th scope="col"><?= e($t->text('ratings.games')) ?></th>
                <th scope="col"><?= e($t->text('ratings.score')) ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($players as $place => $player) : ?>
                <tr>
                    <td><?= e($place + 1) ?></td>
                    <td><a href="<?= e($basePath) ?>/players/<?= e($player['user_id']) ?>/ratings"><?= e($player['username']) ?></a></td>
                    <td><?= e($player['rating']) ?><?= $player['games_played'] < EloCalculator::PROVISIONAL_GAMES ? '?' : '' ?></td>
                    <td><?= e($player['games_played']) ?></td>
                    <td><?= e($player['wins']) ?> / <?= e($player['draws']) ?> / <?= e($player['losses']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
