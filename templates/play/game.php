<?php
/**
 * The game page (A36, G-04, G-09, G-10, G-11, G-22): the board, the players
 * and their ratings, the clock, the move list, and the game buttons.
 *
 * Works without JavaScript: the board is drawn here as a table of pieces,
 * moves are typed into the move field (UCI like e2e4, or SAN like Nf3), and
 * every button is a plain form. With JavaScript, public/assets/js/board/
 * replaces the table with the drag-and-drop board, shows the opponent's
 * moves live, and adds replay and the planning board.
 *
 * Variables: $game (Game), $moves (list of array{ply, uci, san, fen_after}),
 * $usernames (array of id => name), $viewerId (int), $stateJson (string),
 * $canAbort (bool), $abortRule (string), $whiteRating and $blackRating
 * (array{rating: int, games_played: int, is_newbie: bool, is_provisional: bool}),
 * $boardTheme (string), $basePath, $csrfToken, $t.
 */

use PhpChess\Domain\Chess\Position;
use PhpChess\Domain\Play\Games;

$viewerColour = $game->colourOf($viewerId);
$isPlayer = $viewerColour !== null;
$isViewersTurn = $game->isRunning() && $isPlayer && $game->playerToMoveId() === $viewerId;
$opponentId = $isPlayer ? $game->opponentOf($viewerId) : 0;
$position = $game->position();
$showBlackAtBottom = $viewerColour === Position::BLACK;

$pieceSymbols = [
    'K' => '♔', 'Q' => '♕', 'R' => '♖', 'B' => '♗', 'N' => '♘', 'P' => '♙',
    'k' => '♚', 'q' => '♛', 'r' => '♜', 'b' => '♝', 'n' => '♞', 'p' => '♟',
];

/** A player's name with the rating as A30 says: nothing for a newbie, "?" while provisional. */
$playerLine = function (int $playerId, array $rating) use ($usernames, $t): string {
    $name = e($usernames[$playerId] ?? '?');
    if ($rating['is_newbie']) {
        return $name . ' <span class="muted">(' . e($t->text('rating.newbie')) . ')</span>';
    }

    return $name . ' <span class="muted">(' . e($rating['rating']) . ($rating['is_provisional'] ? '?' : '') . ')</span>';
};
// The player at the bottom is the viewer (or White for watchers); both lines are escaped above.
$topPlayerHtml = $showBlackAtBottom ? $playerLine($game->whiteId, $whiteRating) : $playerLine($game->blackId, $blackRating);
$bottomPlayerHtml = $showBlackAtBottom ? $playerLine($game->blackId, $blackRating) : $playerLine($game->whiteId, $whiteRating);
?>
<div class="game-page" id="game-page"
     data-state="<?= e($stateJson) ?>"
     data-base-path="<?= e($basePath) ?>"
     data-csrf-token="<?= e($csrfToken) ?>"
     data-viewer-colour="<?= e($viewerColour ?? '') ?>"
     data-text-your-move="<?= e($t->text('game.your_move')) ?>"
     data-text-waiting="<?= e($t->text('game.waiting_for', ['name' => '{name}'])) ?>"
     data-text-game-over="<?= e($t->text('game.over')) ?>"
     data-text-planning="<?= e($t->text('game.planning_on')) ?>"
     data-text-move-failed="<?= e($t->text('game.move_failed')) ?>">

    <p class="game-player game-player-top"><?= $topPlayerHtml ?></p>

    <div class="board-frame board-theme-<?= e($boardTheme) ?>" id="board">
        <table class="text-board" aria-label="<?= e($t->text('game.board')) ?>">
            <?php for ($rowIndex = 0; $rowIndex < 8; $rowIndex++) : ?>
                <tr>
                    <?php for ($columnIndex = 0; $columnIndex < 8; $columnIndex++) : ?>
                        <?php
                        $rank = $showBlackAtBottom ? $rowIndex : 7 - $rowIndex;
                        $file = $showBlackAtBottom ? 7 - $columnIndex : $columnIndex;
                        $piece = $position->board[$rank * 8 + $file];
                        $squareName = chr(ord('a') + $file) . ($rank + 1);
                        ?>
                        <td class="<?= ($rank + $file) % 2 === 0 ? 'square-dark' : 'square-light' ?>" title="<?= e($squareName) ?>"><?= e($pieceSymbols[$piece] ?? '') ?></td>
                    <?php endfor; ?>
                </tr>
            <?php endfor; ?>
        </table>
    </div>

    <p class="game-player game-player-bottom"><?= $bottomPlayerHtml ?></p>

    <p class="game-status" id="game-status" role="status" aria-live="polite">
        <?php if (!$game->isRunning()) : ?>
            <?= e($t->text('game.over')) ?>: <?= e($game->pgnResult()) ?>,
            <?= e($t->text('end_reason.' . (str_starts_with((string) $game->endReason, Games::REASON_ADJUDICATION_PREFIX) ? 'adjudicated' : (string) $game->endReason))) ?>
        <?php elseif ($isViewersTurn) : ?>
            <strong><?= e($t->text('game.your_move')) ?></strong>
        <?php else : ?>
            <?= e($t->text('game.waiting_for', ['name' => $usernames[$game->playerToMoveId()] ?? '?'])) ?>
        <?php endif; ?>
    </p>
    <?php if ($game->isRunning() && $game->moveDeadlineAt !== null) : ?>
        <p class="game-clock muted" id="game-clock"><?= e($t->text('game.deadline', ['time' => gmdate('Y-m-d H:i', $game->moveDeadlineAt) . ' UTC'])) ?></p>
    <?php endif; ?>

    <div class="board-tools button-row" id="board-tools" hidden>
        <button type="button" class="button-secondary" data-board-action="flip"><?= e($t->text('game.flip')) ?></button>
        <button type="button" class="button-secondary" data-board-action="first" aria-label="<?= e($t->text('game.first_move')) ?>">⏮</button>
        <button type="button" class="button-secondary" data-board-action="back" aria-label="<?= e($t->text('game.previous_move')) ?>">◀</button>
        <button type="button" class="button-secondary" data-board-action="forward" aria-label="<?= e($t->text('game.next_move')) ?>">▶</button>
        <button type="button" class="button-secondary" data-board-action="last" aria-label="<?= e($t->text('game.last_move')) ?>">⏭</button>
        <button type="button" class="button-secondary" data-board-action="plan" aria-pressed="false"><?= e($t->text('game.planning_board')) ?></button>
    </div>

    <?php if ($isViewersTurn) : ?>
        <form method="post" action="<?= e($basePath) ?>/games/<?= e($game->id) ?>/move" class="form form-row move-form" id="move-form">
            <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
            <input type="hidden" name="ply" value="<?= e($game->ply) ?>">
            <label><?= e($t->text('game.type_move')) ?>
                <input type="text" name="move" autocomplete="off" autocapitalize="off" spellcheck="false" maxlength="10" placeholder="e2e4 / Nf3">
            </label>
            <button type="submit"><?= e($t->text('game.send_move')) ?></button>
        </form>
    <?php endif; ?>

    <h2 class="visually-hidden"><?= e($t->text('game.moves')) ?></h2>
    <ol class="move-list" id="move-list">
        <?php
        // Move numbers as in a score sheet: "1. e4 e5 2. Nf3"; "1... e5" when Black moved first.
        $startPosition = Position::fromFen($game->startFen);
        $moveNumber = $startPosition->fullmoveNumber;
        $isWhiteMove = $startPosition->sideToMove === Position::WHITE;
        ?>
        <?php foreach ($moves as $index => $move) : ?>
            <li>
                <?php if ($isWhiteMove) : ?>
                    <span class="move-number"><?= e($moveNumber) ?>.</span>
                <?php elseif ($index === 0) : ?>
                    <span class="move-number"><?= e($moveNumber) ?>...</span>
                <?php endif; ?>
                <button type="button" class="move-button" data-ply="<?= e($move['ply']) ?>"><?= e($move['san']) ?></button>
            </li>
            <?php
            if (!$isWhiteMove) {
                $moveNumber++;
            }
            $isWhiteMove = !$isWhiteMove;
            ?>
        <?php endforeach; ?>
    </ol>

    <?php if ($isPlayer && $game->isRunning()) : ?>
        <div class="button-row game-buttons">
            <?php if ($game->drawOfferedBy === $opponentId) : ?>
                <p><?= e($t->text('game.draw_offered_to_you')) ?></p>
                <form method="post" action="<?= e($basePath) ?>/games/<?= e($game->id) ?>/draw/accept" class="inline-form">
                    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                    <button type="submit"><?= e($t->text('game.accept_draw')) ?></button>
                </form>
                <form method="post" action="<?= e($basePath) ?>/games/<?= e($game->id) ?>/draw/decline" class="inline-form">
                    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                    <button type="submit" class="button-secondary"><?= e($t->text('game.decline_draw')) ?></button>
                </form>
            <?php elseif ($game->drawOfferedBy === $viewerId) : ?>
                <p class="muted"><?= e($t->text('game.draw_offered_by_you')) ?></p>
            <?php else : ?>
                <form method="post" action="<?= e($basePath) ?>/games/<?= e($game->id) ?>/draw/offer" class="inline-form">
                    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                    <button type="submit" class="button-secondary"><?= e($t->text('game.offer_draw')) ?></button>
                </form>
            <?php endif; ?>

            <?php if ($canAbort && $abortRule !== Games::ABORT_ASK_ADMIN) : ?>
                <form method="post" action="<?= e($basePath) ?>/games/<?= e($game->id) ?>/abort" class="inline-form">
                    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                    <button type="submit" class="button-secondary"><?= e($t->text($abortRule === Games::ABORT_COUNTS_AS_LOSS ? 'game.abort_as_loss' : 'game.abort')) ?></button>
                </form>
            <?php endif; ?>

            <details class="confirm">
                <summary class="button button-secondary"><?= e($t->text('game.resign')) ?></summary>
                <form method="post" action="<?= e($basePath) ?>/games/<?= e($game->id) ?>/resign" class="inline-form">
                    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                    <button type="submit" class="button-danger"><?= e($t->text('game.resign_confirm')) ?></button>
                </form>
            </details>
        </div>

        <?php if ($canAbort && $abortRule === Games::ABORT_ASK_ADMIN) : ?>
            <details class="confirm">
                <summary><?= e($t->text('game.ask_admin_to_abort')) ?></summary>
                <form method="post" action="<?= e($basePath) ?>/games/<?= e($game->id) ?>/abort/request" class="form">
                    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                    <label><?= e($t->text('game.abort_reason')) ?> <input type="text" name="reason" maxlength="300" required></label>
                    <button type="submit"><?= e($t->text('game.send_abort_request')) ?></button>
                </form>
            </details>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($isPlayer && !$game->isRunning()) : ?>
        <div class="button-row game-buttons">
            <?php if ($game->rematchGameId !== null) : ?>
                <a class="button" href="<?= e($basePath) ?>/games/<?= e($game->rematchGameId) ?>"><?= e($t->text('game.go_to_rematch')) ?></a>
            <?php elseif ($game->rematchOfferedBy === $viewerId) : ?>
                <p class="muted"><?= e($t->text('game.rematch_offered_by_you')) ?></p>
            <?php else : ?>
                <form method="post" action="<?= e($basePath) ?>/games/<?= e($game->id) ?>/rematch" class="inline-form">
                    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                    <button type="submit"><?= e($t->text($game->rematchOfferedBy === $opponentId ? 'game.accept_rematch' : 'game.rematch')) ?></button>
                </form>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <p class="game-links">
        <a href="<?= e($basePath) ?>/games/<?= e($game->id) ?>/pgn"><?= e($t->text('game.download_pgn')) ?></a>
        · <?= e($t->text($game->isRated ? 'play.rated' : 'game.unrated')) ?>
        <?php if ($game->daysPerMove !== null) : ?>
            · <?= e($t->text('challenge.summary', ['days' => $game->daysPerMove])) ?>
        <?php endif; ?>
    </p>
</div>
<link rel="stylesheet" href="<?= e($basePath) ?>/assets/vendor/cm-chessboard/assets/chessboard.css">
<link rel="stylesheet" href="<?= e($basePath) ?>/assets/vendor/cm-chessboard/assets/extensions/markers/markers.css">
<link rel="stylesheet" href="<?= e($basePath) ?>/assets/vendor/cm-chessboard/assets/extensions/promotion-dialog/promotion-dialog.css">
<script type="module" src="<?= e($basePath) ?>/assets/js/board/game-page.js"></script>
