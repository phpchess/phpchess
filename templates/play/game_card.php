<?php
/**
 * One game in a list (G-11, G-13): players, whose turn or the result, and a link to the board.
 *
 * Variables: $game (Game), $usernames (array of id => name), $viewerId (int|null),
 * $basePath, $t.
 */

$whiteName = $usernames[$game->whiteId] ?? '?';
$blackName = $usernames[$game->blackId] ?? '?';
$isViewersMove = $game->isRunning() && $viewerId !== null && $game->playerToMoveId() === $viewerId;
?>
<li class="card game-card<?= $isViewersMove ? ' game-card-your-move' : '' ?>">
    <a class="card-title" href="<?= e($basePath) ?>/games/<?= e($game->id) ?>"><?= e($whiteName) ?> – <?= e($blackName) ?></a>
    <p class="muted">
        <?php if ($isViewersMove) : ?>
            <strong><?= e($t->text('game.your_move')) ?></strong>
        <?php elseif ($game->isRunning()) : ?>
            <?= e($t->text('game.waiting_for', ['name' => $usernames[$game->playerToMoveId()] ?? '?'])) ?>
        <?php else : ?>
            <?= e($game->pgnResult()) ?> · <?= e($t->text('end_reason.' . (str_starts_with((string) $game->endReason, 'adjudicated_') ? 'adjudicated' : (string) $game->endReason))) ?>
        <?php endif; ?>
        <?php if ($game->isRunning() && $game->moveDeadlineAt !== null) : ?>
            · <?= e($t->text('game.deadline', ['time' => gmdate('Y-m-d H:i', $game->moveDeadlineAt) . ' UTC'])) ?>
        <?php endif; ?>
        · <?= e($t->text('game.move_count', ['count' => intdiv($game->ply + 1, 2)])) ?>
        <?php if (!$game->isRated) : ?>
            · <?= e($t->text('game.unrated')) ?>
        <?php endif; ?>
    </p>
</li>
