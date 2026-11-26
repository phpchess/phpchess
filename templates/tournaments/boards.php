<?php
/**
 * The boards of one round (T-07, A108), on the tournament page and the
 * organiser's desk.
 *
 * Each board: number, White, Black, score or state, a link to the game
 * (watching: A106 is checked by the game page), and:
 * - for a player of the board: "Ask the organiser" (dispute, A108);
 * - for the organiser ($isManager): decide, more time, start now, replay.
 *
 * Variables: $tournament (Tournament), $data (TournamentView::of), $round (int),
 * $viewerId (int), $isManager (bool), $basePath, $csrfToken, $t, $template.
 */

use PhpChess\Domain\Tournament\Pairing;
use PhpChess\Domain\Tournament\TournamentDesk;

$boards = $data['boardsByRound'][$round] ?? [];
$names = $data['names'];
$tournamentPath = $basePath . '/tournaments/' . $tournament->id;
$stateKey = fn (Pairing $board): string => match (true) {
    $board->needsOrganiser() => 'tournament.board.needs_organiser',
    $board->state === Pairing::STATE_VOID => 'tournament.board.void',
    $board->state === Pairing::STATE_POSTPONED => 'tournament.board.postponed',
    $board->state === Pairing::STATE_PLAYING => 'tournament.board.playing',
    default => 'tournament.board.waiting',
};
?>
<?php if ($boards === []) : ?>
    <p class="muted"><?= e($t->text('tournament.no_boards')) ?></p>
<?php else : ?>
    <div class="table-wrap">
        <table class="data-table tournament-boards">
            <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col"><?= e($t->text('admin.games.white')) ?></th>
                    <th scope="col"><?= e($t->text('tournament.score')) ?></th>
                    <th scope="col"><?= e($t->text('admin.games.black')) ?></th>
                    <th scope="col"><span class="visually-hidden"><?= e($t->text('tournament.actions')) ?></span></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($boards as $board) : ?>
                    <tr<?= $board->involves($viewerId) ? ' class="row-mine"' : '' ?>>
                        <td>
                            <?= e($board->division > 0 ? $board->division . '.' . $board->board : $board->board) ?>
                            <?php if ($board->gameNumber > 1) : ?><small><?= e($board->isArmageddon ? $t->text('tournament.armageddon') : $t->text('tournament.game_number', ['number' => $board->gameNumber])) ?></small><?php endif; ?>
                        </td>
                        <td><?= e($names[$board->whiteId] ?? '?') ?></td>
                        <td>
                            <?php if ($board->isBye()) : ?>
                                <?= e($t->text($board->outcome === Pairing::OUTCOME_ABSENCE_BYE ? 'tournament.absence_bye' : 'tournament.bye')) ?>
                                <?= e(Pairing::halvesText($board->whiteHalves)) ?>
                            <?php elseif ($board->counts()) : ?>
                                <strong><?= e($board->scoreText()) ?></strong>
                            <?php else : ?>
                                <span class="muted"><?= e($t->text($stateKey($board))) ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?= e($board->blackId === null ? '' : ($names[$board->blackId] ?? '?')) ?></td>
                        <td class="card-actions">
                            <?php if ($board->gameId !== null) : ?>
                                <a class="button button-secondary icon-button" href="<?= e($basePath) ?>/games/<?= e($board->gameId) ?>"
                                   aria-label="<?= e($t->text('tournament.open_game')) ?>" title="<?= e($t->text('tournament.open_game')) ?>"><?= $template->render('partials/icon', ['icon' => 'tv']) ?></a>
                            <?php endif; ?>

                            <?php if (!$board->isBye() && $board->involves($viewerId) && !$isManager) : ?>
                                <details class="inline-details">
                                    <summary class="button button-secondary icon-button" aria-label="<?= e($t->text('tournament.dispute')) ?>" title="<?= e($t->text('tournament.dispute')) ?>"><?= $template->render('partials/icon', ['icon' => 'flag']) ?></summary>
                                    <form method="post" action="<?= e($tournamentPath) ?>/boards/<?= e($board->id) ?>/dispute" class="form">
                                        <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                                        <label><?= e($t->text('tournament.dispute_reason')) ?>
                                            <textarea name="reason" rows="3" maxlength="2000" required></textarea>
                                        </label>
                                        <button type="submit" class="button"><?= e($t->text('tournament.dispute_send')) ?></button>
                                    </form>
                                </details>
                            <?php endif; ?>

                            <?php if ($isManager && !$board->isBye() && $board->state !== Pairing::STATE_VOID) : ?>
                                <details class="inline-details">
                                    <summary class="button button-secondary icon-button" aria-label="<?= e($t->text('tournament.board_tools')) ?>" title="<?= e($t->text('tournament.board_tools')) ?>"><?= $template->render('partials/icon', ['icon' => 'more']) ?></summary>
                                    <div class="board-tools">
                                        <form method="post" action="<?= e($tournamentPath) ?>/boards/<?= e($board->id) ?>/decide" class="form form-row">
                                            <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                                            <label><?= e($t->text('tournament.decide')) ?>
                                                <select name="decision">
                                                    <?php foreach (TournamentDesk::DECISIONS as $decision) : ?>
                                                        <option value="<?= e($decision) ?>"><?= e($t->text('tournament.decision.' . $decision)) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </label>
                                            <button type="submit" class="button"><?= e($t->text('tournament.decide')) ?></button>
                                        </form>
                                        <?php if ($board->state === Pairing::STATE_PLAYING && !$tournament->isTimed()) : ?>
                                            <form method="post" action="<?= e($tournamentPath) ?>/boards/<?= e($board->id) ?>/postpone" class="form form-row">
                                                <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                                                <label><?= e($t->text('tournament.more_days')) ?>
                                                    <input type="number" name="days" min="1" max="60" value="3">
                                                </label>
                                                <button type="submit" class="button button-secondary"><?= e($t->text('tournament.postpone')) ?></button>
                                            </form>
                                        <?php endif; ?>
                                        <?php if ($board->state === Pairing::STATE_POSTPONED || $board->state === Pairing::STATE_WAITING) : ?>
                                            <form method="post" action="<?= e($tournamentPath) ?>/boards/<?= e($board->id) ?>/start" class="inline-form">
                                                <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                                                <button type="submit" class="button button-secondary"><?= e($t->text('tournament.start_board')) ?></button>
                                            </form>
                                        <?php endif; ?>
                                        <?php if ($board->gameId !== null) : ?>
                                            <form method="post" action="<?= e($tournamentPath) ?>/boards/<?= e($board->id) ?>/replay" class="inline-form">
                                                <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                                                <button type="submit" class="button button-secondary"><?= e($t->text('tournament.replay')) ?></button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </details>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
