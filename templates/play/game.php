<?php
/**
 * The game page (A36, G-04, G-09, G-10, G-11, G-22): a big board with the
 * players above and below it, an icon bar under it, and beside it (or
 * below it on a phone) the game status, the game buttons and the moves.
 *
 * The page has three modes, switched by game-page.js:
 *   - play:     the real board; the icon bar (flip, planning board, replay,
 *               PGN) and the move list;
 *   - replay:   step and play through the game with the replay bar
 *               (start, play backwards, step back, pause, play, step on,
 *               fast forward, end) and the move list;
 *   - planning: a board in other colours (the admin's choice) with its own
 *               title, for trying moves; nothing is sent; no move list.
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

$pieceSymbols = [
    'K' => '♔', 'Q' => '♕', 'R' => '♖', 'B' => '♗', 'N' => '♘', 'P' => '♙',
    'k' => '♚', 'q' => '♛', 'r' => '♜', 'b' => '♝', 'n' => '♞', 'p' => '♟',
];

/** A player's name with the rating as A30 says: nothing for a newbie, "?" while provisional. */
$playerLine = function (int $playerId, array $rating) use ($usernames, $t): string {
    $name = '<strong>' . e($usernames[$playerId] ?? '?') . '</strong>';
    if ($rating['is_newbie']) {
        return $name . ' <span class="muted">(' . e($t->text('rating.newbie')) . ')</span>';
    }

    return $name . ' <span class="muted">(' . e($rating['rating']) . ($rating['is_provisional'] ? '?' : '') . ')</span>';
};
// The player at the bottom is the viewer (or White for watchers); both lines are escaped above.
$topPlayerHtml = $showBlackAtBottom ? $playerLine($game->whiteId, $whiteRating) : $playerLine($game->blackId, $blackRating);
$bottomPlayerHtml = $showBlackAtBottom ? $playerLine($game->blackId, $blackRating) : $playerLine($game->whiteId, $whiteRating);

// Abort only before the first move, and only when the admin allows players to do it themselves (A33).
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
     data-text-waiting="<?= e($t->text('game.waiting_for', ['name' => '{name}'])) ?>"
     data-text-game-over="<?= e($t->text('game.over')) ?>"
     data-text-sending="<?= e($t->text('game.sending_move')) ?>"
     data-text-move-failed="<?= e($t->text('game.move_failed')) ?>">

    <!-- ===== The board column ===== -->
    <section class="board-column" aria-label="<?= e($t->text('game.board')) ?>">
        <p class="player-bar"><?= $topPlayerHtml ?></p>

        <h2 class="planning-title" id="planning-title" hidden><?= e($t->text('game.planning_board')) ?></h2>

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

        <p class="player-bar"><?= $bottomPlayerHtml ?></p>

        <noscript><p class="notice-error"><?= e($t->text('game.needs_javascript')) ?></p></noscript>

        <!-- Play mode: the icon bar (shown by the script) -->
        <div class="icon-bar" id="play-tools" hidden>
            <button type="button" class="icon-button" data-board-action="flip" title="<?= e($t->text('game.flip')) ?>" aria-label="<?= e($t->text('game.flip')) ?>">
                <svg class="icon" aria-hidden="true"><use href="#icon-flip"/></svg>
            </button>
            <button type="button" class="icon-button" data-board-action="plan" title="<?= e($t->text('game.planning_board')) ?>" aria-label="<?= e($t->text('game.planning_board')) ?>">
                <svg class="icon" aria-hidden="true"><use href="#icon-planning"/></svg>
            </button>
            <button type="button" class="icon-button" data-board-action="replay" title="<?= e($t->text('game.replay')) ?>" aria-label="<?= e($t->text('game.replay')) ?>">
                <svg class="icon" aria-hidden="true"><use href="#icon-replay"/></svg>
            </button>
            <a class="icon-button" href="<?= e($gamePath) ?>/pgn" download title="<?= e($t->text('game.download_pgn')) ?>" aria-label="<?= e($t->text('game.download_pgn')) ?>">
                <svg class="icon" aria-hidden="true"><use href="#icon-download"/></svg>
            </a>
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
    </section>

    <!-- ===== Beside the board: status, game buttons, moves ===== -->
    <section class="game-side" id="game-side">
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
                <p class="muted"><?= e($t->text('game.draw_offered_by_you')) ?></p>
            <?php endif; ?>

            <div class="icon-bar game-buttons">
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

                <details class="confirm">
                    <summary class="icon-button" title="<?= e($t->text('game.resign')) ?>" aria-label="<?= e($t->text('game.resign')) ?>">
                        <svg class="icon" aria-hidden="true"><use href="#icon-flag"/></svg>
                    </summary>
                    <form method="post" action="<?= e($gamePath) ?>/resign" class="inline-form">
                        <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                        <button type="submit" class="button-danger"><?= e($t->text('game.resign_confirm')) ?></button>
                    </form>
                </details>
            </div>

            <?php if ($showAbortRequest) : ?>
                <details class="confirm">
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
            <div class="button-row game-buttons">
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
        </div>

        <p class="game-links muted">
            <?= e($t->text($game->isRated ? 'play.rated' : 'game.unrated')) ?>
            <?php if ($game->daysPerMove !== null) : ?>
                · <?= e($t->text('challenge.summary', ['days' => $game->daysPerMove])) ?>
            <?php endif; ?>
        </p>
    </section>
</div>
<link rel="stylesheet" href="<?= e($basePath) ?>/assets/vendor/cm-chessboard/assets/chessboard.css">
<link rel="stylesheet" href="<?= e($basePath) ?>/assets/vendor/cm-chessboard/assets/extensions/markers/markers.css">
<link rel="stylesheet" href="<?= e($basePath) ?>/assets/vendor/cm-chessboard/assets/extensions/promotion-dialog/promotion-dialog.css">
<script type="module" src="<?= e($basePath) ?>/assets/js/board/game-page.js"></script>
