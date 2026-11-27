<?php
/**
 * The calendar of a tournament (A114): one line per round with its start
 * and deadline, and the expected finish. Used by the settings form (the
 * plan), the "Calendar" view and the join page.
 *
 * A round that has really started shows its real dates; the rounds after
 * it are planned from there, so a late round moves the rest with it.
 *
 * Variables: $rounds (list of array{round: int, start: int, deadline: int,
 * isActual?: bool}), $icsUrl (string, optional: the .ics download), $t.
 */

$dayFormat = 'D j M Y, H:i';
$last = $rounds === [] ? null : $rounds[count($rounds) - 1];
?>
<div class="tournament-calendar">
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th scope="col"><?= e($t->text('tournament.round')) ?></th>
                    <th scope="col"><?= e($t->text('tournament.calendar_start')) ?></th>
                    <th scope="col"><?= e($t->text('tournament.calendar_deadline')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rounds as $round) : ?>
                    <tr<?= ($round['isActual'] ?? false) ? ' class="is-actual"' : '' ?>>
                        <td><?= e($round['round']) ?></td>
                        <td><?= e(date($dayFormat, $round['start'])) ?></td>
                        <td><?= e(date($dayFormat, $round['deadline'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if ($last !== null) : ?>
        <p><strong><?= e($t->text('tournament.calendar_finish')) ?></strong> <?= e(date($dayFormat, $last['deadline'])) ?></p>
    <?php endif; ?>
    <?php if (($icsUrl ?? '') !== '') : ?>
        <p><a class="button" href="<?= e($icsUrl) ?>" download><?= e($t->text('tournament.calendar_download')) ?></a></p>
    <?php endif; ?>
</div>
