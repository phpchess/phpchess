<?php
/**
 * A list of study games (C5-1, L-03): players, result, event and date,
 * each a link to the game on a board.
 *
 * Variables: $games (list of array{id: int, white: string, black: string,
 * event: string, played_on: string, result: string, opening: string}), $basePath, $t.
 */
?>
<ul class="text-list">
    <?php foreach ($games as $game) : ?>
        <li>
            <a href="<?= e($basePath) ?>/learn/games/<?= e($game['id']) ?>">
                <?= e($game['white'] !== '' ? $game['white'] : '?') ?> – <?= e($game['black'] !== '' ? $game['black'] : '?') ?>
            </a>
            <strong><?= e($game['result']) ?></strong>
            <span class="muted">
                <?= e(implode(' · ', array_filter([$game['opening'], $game['event'], $game['played_on']], static fn (string $part): bool => $part !== '' && !str_contains($part, '?')))) ?>
            </span>
        </li>
    <?php endforeach; ?>
</ul>
