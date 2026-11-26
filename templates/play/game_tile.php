<?php
/**
 * One running game as a tile (F2, decision A84: icons over text).
 *
 * The whole tile is a button to the game: a micro board of the current
 * position (the viewer's pieces at the bottom), the opponent's name ("name vs"
 * when they play White, "vs name" when they play Black) and icons: the pace,
 * and a "your move" mark when it is the viewer's turn.
 *
 * Variables: $game (Game), $viewerId (int), $usernames (array of id => name), $basePath, $t, $template.
 */

use PhpChess\Domain\Chess\Position;
use PhpChess\Http\MicroBoard;

$viewerPlaysBlack = $game->colourOf($viewerId) === Position::BLACK;
$opponentName = $usernames[$viewerPlaysBlack ? $game->whiteId : $game->blackId] ?? '?';
$nameLine = $viewerPlaysBlack
    ? $t->text('tile.name_vs', ['name' => $opponentName])
    : $t->text('tile.vs_name', ['name' => $opponentName]);
$isViewersMove = $game->playerToMoveId() === $viewerId;
$boardHtml = MicroBoard::svg($game->position(), $viewerPlaysBlack, $t->text('game.board'));

$isLive = $game->timeControl !== null;
$paceValue = $isLive ? (string) $game->timeControl : (string) $game->daysPerMove;
$paceWords = $isLive
    ? $t->text('play.pace_timed', ['control' => $game->timeControl])
    : $t->text('challenge.summary', ['days' => $game->daysPerMove]);
?>
<li class="tile game-tile<?= $isViewersMove ? ' tile-your-move' : '' ?>">
    <a class="tile-link" href="<?= e($basePath) ?>/games/<?= e($game->id) ?>" aria-label="<?= e($nameLine . ($isViewersMove ? ' · ' . $t->text('game.your_move') : '')) ?>">
        <?= $boardHtml ?>
        <span class="tile-name"><?= e($nameLine) ?></span>
        <span class="tile-icons">
            <span class="tile-icon" title="<?= e($paceWords) ?>">
                <?= $template->render('partials/icon', ['icon' => $isLive ? 'live' : 'correspondence', 'size' => 18]) ?><span><?= e($paceValue) ?></span>
            </span>
            <?php if ($isViewersMove) : ?>
                <span class="tile-icon tile-icon-strong" title="<?= e($t->text('game.your_move')) ?>"><?= $template->render('partials/icon', ['icon' => 'play', 'size' => 18]) ?></span>
            <?php endif; ?>
        </span>
    </a>
</li>
