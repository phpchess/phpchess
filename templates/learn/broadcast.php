<?php
/**
 * The games of one broadcast round (A96), each opening on our own board.
 * Only games that passed the content scanner are listed.
 *
 * Variables: $roundId (string), $games (list of array{white: string, black: string,
 * result: string, event: string}), $basePath, $t, $template.
 */
?>
<p><a class="icon-button" href="<?= e($basePath) ?>/learn/lichess" aria-label="<?= e($t->text('lichess.page_title')) ?>" title="<?= e($t->text('lichess.page_title')) ?>"><?= $template->render('partials/icon', ['icon' => 'back']) ?></a></p>
<h1><?= e($games !== [] && $games[0]['event'] !== '' ? $games[0]['event'] : $t->text('lichess.broadcast')) ?></h1>

<?php if ($games === []) : ?>
    <p class="muted"><?= e($t->text('lichess.no_games')) ?></p>
<?php else : ?>
    <ul class="text-list">
        <?php foreach ($games as $index => $game) : ?>
            <li>
                <a href="<?= e($basePath) ?>/learn/lichess/broadcast/<?= e($roundId) ?>/<?= e($index + 1) ?>">
                    <?= e($game['white'] !== '' ? $game['white'] : '?') ?> – <?= e($game['black'] !== '' ? $game['black'] : '?') ?>
                </a>
                <strong><?= e($game['result']) ?></strong>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
