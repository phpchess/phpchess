<?php
/**
 * One game in a list (G-11, G-13): a small picture of the position, the
 * players, whose turn or the result, and a link to the board. The whole
 * card is the link, so it is easy to hit on a phone.
 *
 * Variables: $game (Game), $usernames (array of id => name), $viewerId (int|null),
 * $basePath, $t.
 */

use PhpChess\Domain\Chess\Position;
use PhpChess\Http\MiniBoard;

$whiteName = $usernames[$game->whiteId] ?? '?';
$blackName = $usernames[$game->blackId] ?? '?';
$isViewersMove = $game->isRunning() && $viewerId !== null && $game->playerToMoveId() === $viewerId;

// The viewer's own pieces at the bottom, as on the big board.
$viewerPlaysBlack = $viewerId !== null && $game->colourOf($viewerId) === Position::BLACK;
$miniBoardHtml = MiniBoard::svg(
    $game->position(),
    $viewerPlaysBlack,
    $basePath . '/assets/vendor/cm-chessboard/assets/pieces/standard.svg',
    $t->text('game.board'),
);
?>
<li class="card game-card<?= $isViewersMove ? ' game-card-your-move' : '' ?>">
    <a class="game-card-link" href="<?= e($basePath) ?>/games/<?= e($game->id) ?>">
        <?= $miniBoardHtml ?>
        <span class="game-card-text">
            <span class="card-title"><?= e($whiteName) ?> – <?= e($blackName) ?></span>
            <span class="muted">
                <?php if ($isViewersMove) : ?>
                    <strong><?= e($t->text('game.your_move')) ?></strong>
                <?php elseif ($game->isRunning()) : ?>
                    <?= e($t->text('game.waiting_for', ['name' => $usernames[$game->playerToMoveId()] ?? '?'])) ?>
                <?php else : ?>
                    <?= e($game->pgnResult()) ?> · <?= e($t->text('end_reason.' . (str_starts_with((string) $game->endReason, 'adjudicated_') ? 'adjudicated' : (string) $game->endReason))) ?>
                <?php endif; ?>
            </span>
            <span class="muted">
                <?php if ($game->isRunning() && $game->moveDeadlineAt !== null) : ?>
                    <?= e($t->text('game.deadline', ['time' => gmdate('Y-m-d H:i', $game->moveDeadlineAt) . ' UTC'])) ?> ·
                <?php endif; ?>
                <?= e($t->text('game.move_count', ['count' => intdiv($game->ply + 1, 2)])) ?>
                <?php if (!$game->isRated) : ?>
                    · <?= e($t->text('game.unrated')) ?>
                <?php endif; ?>
            </span>
        </span>
    </a>
</li>
