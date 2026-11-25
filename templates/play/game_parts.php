<?php
/**
 * The parts of the game page that change with offers and the game's end
 * (B23): the status line, the game buttons in the icon bar (draw offer,
 * abort, resign) and the offers area (draw, pace, abort request, rematch).
 *
 * game.php renders them when the page loads. During a game,
 * GET /games/{id}/parts renders them again and game-page.js swaps only
 * these three elements, instead of reloading the whole page (which made
 * the board flicker in live games).
 *
 * Variables: $part ("status", "buttons" or "offers"), $game (Game),
 * $viewerId (int), $computer (array|null, see game.php), $canAbort (bool),
 * $abortRule (string), $paceChoices (list of string), $currentPace (string),
 * $basePath, $csrfToken, $t.
 */

use PhpChess\Domain\Chess\Position;
use PhpChess\Domain\Play\Game;
use PhpChess\Domain\Play\Games;

$viewerColour = $game->colourOf($viewerId);
$isPlayer = $viewerColour !== null;
$isViewersTurn = $game->isRunning() && $isPlayer && $game->playerToMoveId() === $viewerId;
$opponentId = $isPlayer ? $game->opponentOf($viewerId) : 0;
$computerOpponent = $computer !== null;
$gamePath = $basePath . '/games/' . $game->id;

/** A pace as words: "5+3" or "3 days per move". */
$paceText = fn (string $pace): string => str_ends_with($pace, Game::PACE_DAYS_SUFFIX)
    ? $t->text('challenge.summary', ['days' => (int) $pace])
    : $t->text('play.pace_timed', ['control' => $pace]);

// Abort only before the first move, and only when the admin lets players do it themselves (A33, A39).
$showAbortButton = $canAbort && $abortRule !== Games::ABORT_ASK_ADMIN;
$showAbortRequest = $canAbort && $abortRule === Games::ABORT_ASK_ADMIN;
?>
<?php if ($part === 'status') : ?>
    <?php // ===== The status line ===== ?>
    <?php if (!$game->isRunning()) : ?>
        <?= e($t->text('game.over')) ?>: <?= e($game->pgnResult()) ?>,
        <?= e($t->text('end_reason.' . (str_starts_with((string) $game->endReason, Games::REASON_ADJUDICATION_PREFIX) ? 'adjudicated' : (string) $game->endReason))) ?>
    <?php elseif ($isViewersTurn) : ?>
        <strong><?= e($t->text('game.your_move')) ?></strong>
    <?php else : ?>
        <?= e($t->text($game->sideToMove() === Position::WHITE ? 'game.waiting_for_white' : 'game.waiting_for_black')) ?>
    <?php endif; ?>
<?php elseif ($part === 'buttons') : ?>
    <?php // ===== The game buttons in the icon bar ===== ?>
    <?php if ($isPlayer && $game->isRunning()) : ?>
        <?php if ($game->drawOfferedBy === null && $computer === null) : ?>
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
<?php else : ?>
    <?php // ===== Offers and requests ===== ?>
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

        <!-- Changing pace (G-23): both players agree -->
        <?php if ($game->paceOfferedBy === $opponentId && $game->paceOffer !== null) : ?>
            <div class="offer-box">
                <p><?= e($t->text('game.pace_offered_to_you', ['pace' => $paceText($game->paceOffer)])) ?></p>
                <div class="button-row">
                    <form method="post" action="<?= e($gamePath) ?>/pace/accept" class="inline-form">
                        <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                        <button type="submit"><?= e($t->text('game.accept_pace')) ?></button>
                    </form>
                    <form method="post" action="<?= e($gamePath) ?>/pace/decline" class="inline-form">
                        <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                        <button type="submit" class="button-secondary"><?= e($t->text('game.decline_pace')) ?></button>
                    </form>
                </div>
            </div>
        <?php elseif ($game->paceOfferedBy === $viewerId && $game->paceOffer !== null) : ?>
            <form method="post" action="<?= e($gamePath) ?>/pace/decline" class="inline-form centred">
                <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                <span class="muted"><?= e($t->text('game.pace_offered_by_you', ['pace' => $paceText($game->paceOffer)])) ?></span>
                <button type="submit" class="button-secondary"><?= e($t->text('game.withdraw_pace')) ?></button>
            </form>
        <?php elseif ($computer !== null) : ?>
            <p class="muted centred"><?= e($t->text('computer.no_draws')) ?></p>
        <?php else : ?>
            <details class="confirm centred">
                <summary><?= e($t->text('game.change_pace')) ?></summary>
                <form method="post" action="<?= e($gamePath) ?>/pace/offer" class="form">
                    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                    <label><?= e($t->text('play.pace')) ?>
                        <select name="pace">
                            <?php foreach ($paceChoices as $pace) : ?>
                                <?php if ($pace !== $currentPace) : ?>
                                    <option value="<?= e($pace) ?>"><?= e($paceText($pace)) ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <p class="hint"><?= e($t->text('game.change_pace_hint')) ?></p>
                    <button type="submit"><?= e($t->text('game.propose_pace')) ?></button>
                </form>
            </details>
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
            <?php if ($game->rematchGameId === null && $computerOpponent) : ?>
                <a class="button" href="<?= e($basePath) ?>/play/computer"><?= e($t->text('computer.link')) ?></a>
            <?php elseif ($game->rematchGameId !== null) : ?>
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
<?php endif; ?>
