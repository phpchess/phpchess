<?php
/**
 * The game page (A36, G-04, G-09, G-10, G-11, G-22), in one column so the
 * board can be as big as the screen allows:
 *
 *   header      White (with a white queen) left, the clock in the middle,
 *               Black (with a black queen) right; under it whose turn it is
 *   board       as big as the shorter side of the window allows (play.css)
 *   icon bar    flip, planning board, replay, PGN, then the game buttons
 *               (draw offer, abort, resign)
 *   below       offers and requests, then the move list
 *
 * The page has three modes, switched by game-page.js:
 *   - play:     the real board, the icon bar, the move list as a line;
 *   - replay:   the replay bar (start, play backwards, step back, pause,
 *               play, step on, fast forward, end, PGN, close) and the move
 *               list as a table (number | White | Black), every move clickable;
 *   - planning: try moves for both sides; nothing is sent; no move list.
 *   Replay and planning use the admin's second board colours, so they can
 *   not be taken for the live board.
 *
 * Moves are made on the board by dragging or by tapping two squares; they
 * need JavaScript. Without it the board is drawn as a table of pieces, and
 * the game buttons (draw, resign, ...) still work as plain forms.
 *
 * Icons are drawn once in templates/play/icons.php and used by id.
 *
 * Variables: $game (Game), $moves (list of array{ply, uci, san, fen_after}),
 * $usernames (array of id => name), $viewerId (int), $stateJson (string),
 * $canAbort (bool), $abortRule (string), $whiteRating and $blackRating
 * (array{rating: int, games_played: int, is_newbie: bool, is_provisional: bool}),
 * $boardTheme and $planningBoardTheme (string), $basePath, $csrfToken, $t, $template.
 */

use PhpChess\Domain\Chess\Position;
use PhpChess\Domain\Play\Games;

$viewerColour = $game->colourOf($viewerId);
$isPlayer = $viewerColour !== null;
$isViewersTurn = $game->isRunning() && $isPlayer && $game->playerToMoveId() === $viewerId;
$opponentId = $isPlayer ? $game->opponentOf($viewerId) : 0;
$position = $game->position();
$showBlackAtBottom = $viewerColour === Position::BLACK;
$gamePath = $basePath . '/games/' . $game->id;
$spriteUrl = $basePath . '/assets/vendor/cm-chessboard/assets/pieces/standard.svg';

$pieceSymbols = [
    'K' => '♔', 'Q' => '♕', 'R' => '♖', 'B' => '♗', 'N' => '♘', 'P' => '♙',
    'k' => '♚', 'q' => '♛', 'r' => '♜', 'b' => '♝', 'n' => '♞', 'p' => '♟',
];

/** A player's name with the rating as A30 says: nothing for a newbie, "?" while provisional. */
$playerName = function (int $playerId, array $rating) use ($usernames, $t): string {
    $name = '<strong>' . e($usernames[$playerId] ?? '?') . '</strong>';
    if ($rating['is_newbie']) {
        return $name . ' <span class="muted">(' . e($t->text('rating.newbie')) . ')</span>';
    }

    return $name . ' <span class="muted">(' . e($rating['rating']) . ($rating['is_provisional'] ? '?' : '') . ')</span>';
};
$whitePlayerHtml = $playerName($game->whiteId, $whiteRating);
$blackPlayerHtml = $playerName($game->blackId, $blackRating);

// Abort only before the first move, and only when the admin lets players do it themselves (A33, A39).
$showAbortButton = $canAbort && $abortRule !== Games::ABORT_ASK_ADMIN;
$showAbortRequest = $canAbort && $abortRule === Games::ABORT_ASK_ADMIN;
?>
<?= $template->render('play/icons', []) ?>

<div class="game-page" id="game-page"
     data-state="<?= e($stateJson) ?>"
     data-base-path="<?= e($basePath) ?>"
     data-csrf-token="<?= e($csrfToken) ?>"
     data-viewer-colour="<?= e($viewerColour ?? '') ?>"
     data-board-theme="<?= e($boardTheme) ?>"
     data-planning-board-theme="<?= e($planningBoardTheme) ?>"
     data-text-your-move="<?= e($t->text('game.your_move')) ?>"
     data-text-waiting-white="<?= e($t->text('game.waiting_for_white')) ?>"
     data-text-waiting-black="<?= e($t->text('game.waiting_for_black')) ?>"
     data-text-sending="<?= e($t->text('game.sending_move')) ?>"
     data-text-move-failed="<?= e($t->text('game.move_failed')) ?>"
     data-text-move-queued="<?= e($t->text('game.move_queued')) ?>"
     data-text-days="<?= e($t->text('game.clock_days')) ?>"
     data-text-planning="<?= e($t->text('game.planning_board')) ?>"
     data-text-replay="<?= e($t->text('game.replay')) ?>"
     data-text-white="<?= e($t->text('game.white')) ?>"
     data-text-black="<?= e($t->text('game.black')) ?>">

    <!-- ===== Above the board: White | clock | Black ===== -->
    <div class="game-header">
        <span class="header-player">
            <svg class="header-queen" viewBox="0 0 40 40" aria-label="<?= e($t->text('game.white')) ?>"><use href="<?= e($spriteUrl) ?>#wq"/></svg>
            <span><?= $whitePlayerHtml ?></span>
        </span>
        <span class="header-clock" id="game-clock"
              data-deadline="<?= e($game->isRunning() && $game->moveDeadlineAt !== null ? gmdate('Y-m-d\TH:i:s\Z', $game->moveDeadlineAt) : '') ?>"
              title="<?= e($game->moveDeadlineAt !== null ? $t->text('game.deadline', ['time' => gmdate('Y-m-d H:i', $game->moveDeadlineAt) . ' UTC']) : '') ?>">
            <?php if ($game->isRunning() && $game->moveDeadlineAt !== null) : ?>
                <?= e(gmdate('Y-m-d H:i', $game->moveDeadlineAt)) ?>
            <?php endif; ?>
        </span>
        <span class="header-player header-player-black">
            <span><?= $blackPlayerHtml ?></span>
            <svg class="header-queen" viewBox="0 0 40 40" aria-label="<?= e($t->text('game.black')) ?>"><use href="<?= e($spriteUrl) ?>#bq"/></svg>
        </span>
    </div>

    <p class="game-status" id="game-status" role="status" aria-live="polite">
        <?php if (!$game->isRunning()) : ?>
            <?= e($t->text('game.over')) ?>: <?= e($game->pgnResult()) ?>,
            <?= e($t->text('end_reason.' . (str_starts_with((string) $game->endReason, Games::REASON_ADJUDICATION_PREFIX) ? 'adjudicated' : (string) $game->endReason))) ?>
        <?php elseif ($isViewersTurn) : ?>
            <strong><?= e($t->text('game.your_move')) ?></strong>
        <?php else : ?>
            <?= e($t->text($game->sideToMove() === Position::WHITE ? 'game.waiting_for_white' : 'game.waiting_for_black')) ?>
        <?php endif; ?>
    </p>

    <!-- ===== The board ===== -->
    <h2 class="planning-title" id="planning-title" hidden></h2>

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

    <noscript><p class="notice-error"><?= e($t->text('game.needs_javascript')) ?></p></noscript>

    <!-- ===== Under the board: play mode's icon bar and game buttons ===== -->
    <div class="icon-bar" id="play-tools">
        <button type="button" class="icon-button needs-script" data-board-action="flip" title="<?= e($t->text('game.flip')) ?>" aria-label="<?= e($t->text('game.flip')) ?>">
            <svg class="icon" aria-hidden="true"><use href="#icon-flip"/></svg>
        </button>
        <button type="button" class="icon-button needs-script" data-board-action="plan" title="<?= e($t->text('game.planning_board')) ?>" aria-label="<?= e($t->text('game.planning_board')) ?>">
            <svg class="icon" aria-hidden="true"><use href="#icon-planning"/></svg>
        </button>
        <button type="button" class="icon-button needs-script" data-board-action="replay" title="<?= e($t->text('game.replay')) ?>" aria-label="<?= e($t->text('game.replay')) ?>">
            <svg class="icon" aria-hidden="true"><use href="#icon-replay"/></svg>
        </button>
        <a class="icon-button" href="<?= e($gamePath) ?>/pgn" download title="<?= e($t->text('game.download_pgn')) ?>" aria-label="<?= e($t->text('game.download_pgn')) ?>">
            <svg class="icon" aria-hidden="true"><use href="#icon-download"/></svg>
        </a>

        <?php if ($isPlayer && $game->isRunning()) : ?>
            <?php if ($game->drawOfferedBy === null) : ?>
                <form method="post" action="<?= e($gamePath) ?>/draw/offer" class="inline-form">
                    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                    <button type="submit" class="icon-button" title="<?= e($t->text('game.offer_draw')) ?>" aria-label="<?= e($t->text('game.offer_draw')) ?>">
                        <span class="icon-text" aria-hidden="true">½</span>
                    </button>
                </form>
            <?php endif; ?>

            <?php if ($showAbortButton) : ?>
                <form method="post" action="<?= e($gamePath) ?>/abort" class="inline-form">
                    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                    <?php $abortText = $t->text($abortRule === Games::ABORT_COUNTS_AS_LOSS ? 'game.abort_as_loss' : 'game.abort'); ?>
                    <button type="submit" class="icon-button" title="<?= e($abortText) ?>" aria-label="<?= e($abortText) ?>">
                        <svg class="icon" aria-hidden="true"><use href="#icon-abort"/></svg>
                    </button>
                </form>
            <?php endif; ?>

            <!-- Resign asks once more: the flag opens the "Yes, I resign" button. -->
            <details class="confirm">
                <summary class="icon-button" title="<?= e($t->text('game.resign')) ?>" aria-label="<?= e($t->text('game.resign')) ?>">
                    <svg class="icon" aria-hidden="true"><use href="#icon-flag"/></svg>
                </summary>
                <form method="post" action="<?= e($gamePath) ?>/resign" class="inline-form">
                    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                    <button type="submit" class="button-danger"><?= e($t->text('game.resign_confirm')) ?></button>
                </form>
            </details>
        <?php endif; ?>
    </div>

    <!-- Replay mode: the replay bar -->
    <div class="icon-bar replay-bar" id="replay-tools" hidden>
        <button type="button" class="icon-button" data-board-action="first" title="<?= e($t->text('game.first_move')) ?>" aria-label="<?= e($t->text('game.first_move')) ?>">
            <svg class="icon" aria-hidden="true"><use href="#icon-first"/></svg>
        </button>
        <button type="button" class="icon-button" data-board-action="play-backwards" title="<?= e($t->text('game.play_backwards')) ?>" aria-label="<?= e($t->text('game.play_backwards')) ?>">
            <svg class="icon" aria-hidden="true"><use href="#icon-rewind"/></svg>
        </button>
        <button type="button" class="icon-button" data-board-action="back" title="<?= e($t->text('game.previous_move')) ?>" aria-label="<?= e($t->text('game.previous_move')) ?>">
            <svg class="icon" aria-hidden="true"><use href="#icon-step-back"/></svg>
        </button>
        <button type="button" class="icon-button" data-board-action="pause" title="<?= e($t->text('game.pause')) ?>" aria-label="<?= e($t->text('game.pause')) ?>">
            <svg class="icon" aria-hidden="true"><use href="#icon-pause"/></svg>
        </button>
        <button type="button" class="icon-button" data-board-action="play" title="<?= e($t->text('game.play')) ?>" aria-label="<?= e($t->text('game.play')) ?>">
            <svg class="icon" aria-hidden="true"><use href="#icon-play"/></svg>
        </button>
        <button type="button" class="icon-button" data-board-action="forward" title="<?= e($t->text('game.next_move')) ?>" aria-label="<?= e($t->text('game.next_move')) ?>">
            <svg class="icon" aria-hidden="true"><use href="#icon-step-forward"/></svg>
        </button>
        <button type="button" class="icon-button" data-board-action="fast-forward" title="<?= e($t->text('game.fast_forward')) ?>" aria-label="<?= e($t->text('game.fast_forward')) ?>">
            <svg class="icon" aria-hidden="true"><use href="#icon-fast-forward"/></svg>
        </button>
        <button type="button" class="icon-button" data-board-action="last" title="<?= e($t->text('game.last_move')) ?>" aria-label="<?= e($t->text('game.last_move')) ?>">
            <svg class="icon" aria-hidden="true"><use href="#icon-last"/></svg>
        </button>
        <a class="icon-button" href="<?= e($gamePath) ?>/pgn" download title="<?= e($t->text('game.download_pgn')) ?>" aria-label="<?= e($t->text('game.download_pgn')) ?>">
            <svg class="icon" aria-hidden="true"><use href="#icon-download"/></svg>
        </a>
        <button type="button" class="icon-button" data-board-action="close-replay" title="<?= e($t->text('game.close_replay')) ?>" aria-label="<?= e($t->text('game.close_replay')) ?>">
            <svg class="icon" aria-hidden="true"><use href="#icon-close"/></svg>
        </button>
    </div>

    <!-- Planning mode: one button under the board -->
    <p class="planning-tools" id="planning-tools" hidden>
        <button type="button" class="button-secondary" data-board-action="close-planning"><?= e($t->text('game.close_planning')) ?></button>
    </p>

    <!-- ===== Offers and requests ===== -->
    <?php if ($isPlayer && $game->isRunning()) : ?>
        <?php if ($game->drawOfferedBy === $opponentId) : ?>
            <div class="offer-box">
                <p><?= e($t->text('game.draw_offered_to_you')) ?></p>
                <div class="button-row">
                    <form method="post" action="<?= e($gamePath) ?>/draw/accept" class="inline-form">
                        <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                        <button type="submit"><?= e($t->text('game.accept_draw')) ?></button>
                    </form>
                    <form method="post" action="<?= e($gamePath) ?>/draw/decline" class="inline-form">
                        <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                        <button type="submit" class="button-secondary"><?= e($t->text('game.decline_draw')) ?></button>
                    </form>
                </div>
            </div>
        <?php elseif ($game->drawOfferedBy === $viewerId) : ?>
            <p class="muted centred"><?= e($t->text('game.draw_offered_by_you')) ?></p>
        <?php endif; ?>

        <?php if ($showAbortRequest) : ?>
            <details class="confirm centred">
                <summary><?= e($t->text('game.ask_admin_to_abort')) ?></summary>
                <form method="post" action="<?= e($gamePath) ?>/abort/request" class="form">
                    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                    <label><?= e($t->text('game.abort_reason')) ?> <input type="text" name="reason" maxlength="300" required></label>
                    <button type="submit"><?= e($t->text('game.send_abort_request')) ?></button>
                </form>
            </details>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($isPlayer && !$game->isRunning()) : ?>
        <div class="button-row centred">
            <?php if ($game->rematchGameId !== null) : ?>
                <a class="button" href="<?= e($basePath) ?>/games/<?= e($game->rematchGameId) ?>"><?= e($t->text('game.go_to_rematch')) ?></a>
            <?php elseif ($game->rematchOfferedBy === $viewerId) : ?>
                <p class="muted"><?= e($t->text('game.rematch_offered_by_you')) ?></p>
            <?php else : ?>
                <form method="post" action="<?= e($gamePath) ?>/rematch" class="inline-form">
                    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                    <button type="submit"><?= e($t->text($game->rematchOfferedBy === $opponentId ? 'game.accept_rematch' : 'game.rematch')) ?></button>
                </form>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- ===== The moves (a line in play mode; a table in replay, drawn by the script) ===== -->
    <div class="move-panel" id="move-panel">
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
        <table class="move-table" id="move-table" hidden>
            <thead><tr><th scope="col">#</th><th scope="col"><?= e($t->text('game.white')) ?></th><th scope="col"><?= e($t->text('game.black')) ?></th></tr></thead>
            <tbody></tbody>
        </table>
    </div>

    <p class="game-links muted centred">
        <?= e($t->text($game->isRated ? 'play.rated' : 'game.unrated')) ?>
        <?php if ($game->daysPerMove !== null) : ?>
            · <?= e($t->text('challenge.summary', ['days' => $game->daysPerMove])) ?>
        <?php endif; ?>
    </p>

    <?php if ($chat !== null) : ?>
        <?= $template->render('play/chat', $chat + ['game' => $game, 'viewerId' => $viewerId, 'basePath' => $basePath, 'csrfToken' => $csrfToken, 't' => $t]) ?>
    <?php endif; ?>
</div>
<link rel="stylesheet" href="<?= e($basePath) ?>/assets/vendor/cm-chessboard/assets/chessboard.css">
<link rel="stylesheet" href="<?= e($basePath) ?>/assets/vendor/cm-chessboard/assets/extensions/markers/markers.css">
<link rel="stylesheet" href="<?= e($basePath) ?>/assets/vendor/cm-chessboard/assets/extensions/promotion-dialog/promotion-dialog.css">
<script type="module" src="<?= e($basePath) ?>/assets/js/board/game-page.js"></script>
