<?php
/**
 * One player's rating, statistics and history (R-03), with a simple chart
 * drawn here as SVG (no chart library, no script).
 *
 * Variables: $player (User), $speed (string),
 * $shown (array{rating: int, games_played: int, is_newbie: bool, is_provisional: bool}),
 * $statistics (array{rating, games_played, wins, draws, losses}|null),
 * $history (list of array{game_id: int, rating_before: int, rating_after: int, created_at: string}),
 * $basePath, $t.
 */

// --- The chart: one point per rated game, scaled into a 300 x 120 box ---
$points = [];
if ($history !== []) {
    $values = [$history[0]['rating_before']];
    foreach ($history as $entry) {
        $values[] = $entry['rating_after'];
    }
    $lowest = min($values) - 10;
    $highest = max($values) + 10;
    $stepWidth = count($values) > 1 ? 300 / (count($values) - 1) : 0;
    foreach ($values as $index => $value) {
        $points[] = round($index * $stepWidth, 1) . ',' . round(120 - ($value - $lowest) * 120 / ($highest - $lowest), 1);
    }
}
?>
<h1><?= e($t->text('ratings.history_title', ['name' => $player->username])) ?></h1>
<p>
    <?= e($t->text('ratings.speed.' . $speed)) ?>:
    <?php if ($shown['is_newbie']) : ?>
        <?= e($t->text('rating.newbie')) ?>
    <?php else : ?>
        <strong><?= e($shown['rating']) ?><?= $shown['is_provisional'] ? '?' : '' ?></strong>
    <?php endif; ?>
</p>
<?php if ($statistics !== null) : ?>
    <p class="muted"><?= e($t->text('ratings.statistics', [
        'games' => $statistics['games_played'],
        'wins' => $statistics['wins'],
        'draws' => $statistics['draws'],
        'losses' => $statistics['losses'],
    ])) ?></p>
<?php endif; ?>

<?php if ($points !== []) : ?>
    <svg class="rating-chart" viewBox="-5 -5 310 130" role="img" aria-label="<?= e($t->text('ratings.chart')) ?>">
        <polyline fill="none" stroke="currentColor" stroke-width="2" points="<?= e(implode(' ', $points)) ?>"/>
    </svg>
    <table class="data-table">
        <thead>
            <tr>
                <th scope="col"><?= e($t->text('ratings.date')) ?></th>
                <th scope="col"><?= e($t->text('ratings.game')) ?></th>
                <th scope="col"><?= e($t->text('ratings.rating')) ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach (array_reverse($history) as $entry) : ?>
                <tr>
                    <td><?= e(substr($entry['created_at'], 0, 10)) ?></td>
                    <td><a href="<?= e($basePath) ?>/games/<?= e($entry['game_id']) ?>">#<?= e($entry['game_id']) ?></a></td>
                    <td><?= e($entry['rating_before']) ?> → <?= e($entry['rating_after']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else : ?>
    <p class="muted"><?= e($t->text('ratings.no_history')) ?></p>
<?php endif; ?>
