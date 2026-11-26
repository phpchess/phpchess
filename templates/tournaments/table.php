<?php
/**
 * The crosstable (T-07): Round Robin and League show who scored what
 * against whom; Swiss shows each player's rounds ("5w1" = against number 5,
 * with White, won); knockout shows the bracket.
 *
 * Variables: $tournament (Tournament), $data (TournamentView::of), $viewerId (int),
 * $basePath, $csrfToken, $t, $template.
 */

use PhpChess\Domain\Tournament\Pairing;
use PhpChess\Domain\Tournament\Tournament;
?>
<?php if ($tournament->format === Tournament::FORMAT_KNOCKOUT) : ?>
    <?= $template->render('tournaments/bracket', ['tournament' => $tournament, 'data' => $data, 'viewerId' => $viewerId, 't' => $t]) ?>

<?php elseif ($tournament->format === Tournament::FORMAT_SWISS) : ?>
    <div class="table-wrap">
        <table class="data-table crosstable">
            <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col"><?= e($t->text('tournament.player')) ?></th>
                    <?php for ($round = 1; $round <= $tournament->currentRound; $round++) : ?>
                        <th scope="col"><?= e($round) ?></th>
                    <?php endfor; ?>
                    <th scope="col"><?= e($t->text('tournament.points')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data['standings'][0] ?? [] as $index => $row) : ?>
                    <tr class="<?= $row->player->userId === $viewerId ? 'row-mine' : '' ?>">
                        <td><?= e($index + 1) ?></td>
                        <td><?= e($row->player->username) ?></td>
                        <?php for ($round = 1; $round <= $tournament->currentRound; $round++) : ?>
                            <td><?= e($data['swissTable'][$row->player->userId][$round] ?? '') ?></td>
                        <?php endfor; ?>
                        <td><strong><?= e(Pairing::halvesText($row->halves)) ?></strong></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

<?php else : ?>
    <?php foreach ($data['standings'] as $division => $rows) : ?>
        <?php if ($tournament->format === Tournament::FORMAT_LEAGUE) : ?>
            <h3><?= e($t->text('league.division_number', ['division' => $division])) ?></h3>
        <?php endif; ?>
        <?php $cross = $data['crosstables'][$division] ?? []; ?>
        <div class="table-wrap">
            <table class="data-table crosstable">
                <thead>
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col"><?= e($t->text('tournament.player')) ?></th>
                        <?php foreach ($rows as $index => $column) : ?>
                            <th scope="col" title="<?= e($column->player->username) ?>"><?= e($index + 1) ?></th>
                        <?php endforeach; ?>
                        <th scope="col"><?= e($t->text('tournament.points')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $index => $row) : ?>
                        <tr class="<?= $row->player->userId === $viewerId ? 'row-mine' : '' ?>">
                            <td><?= e($index + 1) ?></td>
                            <td><?= e($row->player->username) ?></td>
                            <?php foreach ($rows as $column) : ?>
                                <?php if ($column->player->userId === $row->player->userId) : ?>
                                    <td class="crosstable-self" aria-hidden="true"></td>
                                <?php else : ?>
                                    <td><?= e(implode(' ', $cross[$row->player->userId][$column->player->userId] ?? [])) ?></td>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <td><strong><?= e(Pairing::halvesText($row->halves)) ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
