<?php
/**
 * A list of study games (C5-1, L-03): players, result, event and date,
 * each a link to the game on a board.
 *
 * Variables: $games (list of array{id: int, white: string, black: string,
 * event: string, played_on: string, result: string, opening: string, is_kept: bool, created_at: int}),
 * $ownGameDays (int|null: set for a player's own list; 0 = never removed), $basePath, $t.
 */
$ownGameDays ??= null;
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
            <?php if ($ownGameDays !== null) : ?>
                <span class="muted">· <?= e($game['is_kept'] || $ownGameDays === 0 ? $t->text('study.kept_short') : $t->text('study.until', ['date' => gmdate('Y-m-d', $game['created_at'] + $ownGameDays * 86400)])) ?></span>
            <?php endif; ?>
        </li>
    <?php endforeach; ?>
</ul>
