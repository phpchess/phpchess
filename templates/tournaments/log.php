<?php
/**
 * The tournament log (A107, A108): every organiser decision and every
 * automatic step (pairing, deadlines, forfeits), newest first, so players
 * can see why something happened.
 *
 * Variables: $data (TournamentView::of: log, names), $t, plus the shared part variables.
 */
?>
<?php if ($data['log'] === []) : ?>
    <p class="muted"><?= e($t->text('tournament.log_empty')) ?></p>
<?php else : ?>
    <ul class="text-list tournament-log">
        <?php foreach ($data['log'] as $line) : ?>
            <li>
                <time class="muted"><?= e($line['createdAt'] === null ? '' : gmdate('Y-m-d H:i', $line['createdAt'])) ?></time>
                <strong><?= e($t->text('tournament.log.' . $line['action'])) ?></strong>
                <?= e($line['details']) ?>
                <?php if ($line['userId'] !== null) : ?>
                    <span class="muted">(<?= e($data['names'][$line['userId']] ?? ('#' . $line['userId'])) ?>)</span>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
