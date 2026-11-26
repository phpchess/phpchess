<?php
/**
 * The knockout bracket (T-04, A100): one column per round, one box per
 * match, the winner in bold. A match still open says what it waits for
 * (a game, the second game, armageddon).
 *
 * Variables: $tournament (Tournament), $data (TournamentView::of), $viewerId (int), $t.
 */

$names = $data['names'];
$boardsByRound = $data['boardsByRound'];
?>
<?php if ($data['knockout'] === []) : ?>
    <p class="muted"><?= e($t->text('tournament.not_started')) ?></p>
<?php else : ?>
    <div class="bracket">
        <?php foreach ($data['knockout'] as $column) : ?>
            <section class="bracket-round" aria-label="<?= e($t->text('tournament.knockout_round.' . $column['name'], ['round' => $column['round']])) ?>">
                <h3><?= e($t->text('tournament.knockout_round.' . $column['name'], ['round' => $column['round']])) ?></h3>
                <?php foreach ($column['matches'] as $match) : ?>
                    <?php
                    $first = null;
                    foreach ($boardsByRound[$column['round']] ?? [] as $board) {
                        if ($board->matchNumber === $match['match'] && $first === null) {
                            $first = $board;
                        }
                    }
                    if ($first === null) {
                        continue;
                    }
                    $players = $first->blackId === null ? [$first->whiteId] : [$first->whiteId, $first->blackId];
                    ?>
                    <div class="bracket-match">
                        <?php foreach ($players as $playerId) : ?>
                            <div class="<?= $match['winner'] === $playerId ? 'bracket-winner' : '' ?>"><?= e($names[$playerId] ?? '?') ?></div>
                        <?php endforeach; ?>
                        <?php if ($first->blackId === null) : ?>
                            <div class="muted"><?= e($t->text('tournament.bye')) ?></div>
                        <?php elseif (!$match['decided']) : ?>
                            <div class="muted"><?= e($t->text($match['needs'] === '' ? 'tournament.board.playing' : 'tournament.needs.' . $match['needs'])) ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </section>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
