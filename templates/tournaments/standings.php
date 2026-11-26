<?php
/**
 * The standings (T-06, T-07, A101): rank, player, points and each tie-break
 * in the tournament's order; one table per League division. A knockout
 * shows its bracket instead.
 *
 * Variables: $tournament (Tournament), $data (TournamentView::of), $viewerId (int),
 * $basePath, $csrfToken, $t, $template.
 */

use PhpChess\Domain\Tournament\Pairing;
use PhpChess\Domain\Tournament\StandingRow;
use PhpChess\Domain\Tournament\Tournament;
use PhpChess\Domain\Tournament\TournamentPlayer;

$isLeague = $tournament->format === Tournament::FORMAT_LEAGUE;
?>
<?php if ($tournament->format === Tournament::FORMAT_KNOCKOUT) : ?>
    <?= $template->render('tournaments/bracket', ['tournament' => $tournament, 'data' => $data, 'viewerId' => $viewerId, 't' => $t]) ?>
<?php elseif ($data['standings'] === []) : ?>
    <p class="muted"><?= e($t->text('tournament.no_players')) ?></p>
<?php endif; ?>
<?php foreach ($tournament->format === Tournament::FORMAT_KNOCKOUT ? [] : $data['standings'] as $division => $rows) : ?>
    <?php if ($isLeague) : ?>
        <h3><?= e($t->text('league.division_number', ['division' => $division])) ?></h3>
    <?php endif; ?>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col"><?= e($t->text('tournament.player')) ?></th>
                    <th scope="col"><?= e($t->text('tournament.points')) ?></th>
                    <th scope="col"><?= e($t->text('tournament.games_played')) ?></th>
                    <?php foreach ($tournament->tieBreaks as $key) : ?>
                        <th scope="col" title="<?= e($t->text('tie_break.' . $key)) ?>"><?= e($t->text('tie_break_short.' . $key)) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php $divisionSize = count($rows); ?>
                <?php foreach ($rows as $index => $row) : ?>
                    <?php
                    $marks = [];
                    if ($row->player->userId === $viewerId) {
                        $marks[] = 'row-mine';
                    }
                    if ($isLeague && $division > 1 && $index < $tournament->promoteCount) {
                        $marks[] = 'row-up';
                    }
                    if ($isLeague && $division < $tournament->divisions && $index >= $divisionSize - $tournament->relegateCount) {
                        $marks[] = 'row-down';
                    }
                    ?>
                    <tr class="<?= e(implode(' ', $marks)) ?>">
                        <td><?= e($row->rank) ?></td>
                        <td>
                            <?= e($row->player->username) ?>
                            <?php if ($row->player->isComputer) : ?><span class="badge"><?= e($t->text('tournament.computer')) ?></span><?php endif; ?>
                            <?php if ($row->player->isGuest) : ?><span class="badge"><?= e($t->text('tournament.guest')) ?></span><?php endif; ?>
                            <?php if ($row->player->state !== TournamentPlayer::STATE_ACTIVE) : ?><span class="badge"><?= e($t->text('tournament.player_state.' . $row->player->state)) ?></span><?php endif; ?>
                        </td>
                        <td><strong><?= e(Pairing::halvesText($row->halves)) ?></strong></td>
                        <td><?= e($row->gamesPlayed) ?></td>
                        <?php foreach ($tournament->tieBreaks as $key) : ?>
                            <td><?= e(StandingRow::valueText($row->tieBreaks[$key] ?? 0.0)) ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endforeach; ?>
