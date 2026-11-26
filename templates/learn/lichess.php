<?php
/**
 * The players' Lichess page (A96): what the admin turned on, from lichess.org.
 *
 * From top to bottom:
 * 1. the daily puzzle (a tile to our own trainer);
 * 2. live games: one row per Lichess TV channel, with the player and rating,
 *    each opening our TV board on that channel;
 * 3. broadcasts: official events now and soon; a round opens its games.
 * Everything comes through our server; nothing here loads from lichess.org.
 *
 * Variables: $showLive, $showBroadcasts, $showDaily (bool),
 * $live (list of array{channel: string, player: string, rating: int}),
 * $broadcasts (array{active: list<array{name, round, roundId, startsAt}>, upcoming: list<...>}),
 * $problems (list of string: what could not be loaded), $basePath, $t, $template.
 */
?>
<p><a class="icon-button" href="<?= e($basePath) ?>/learn" aria-label="<?= e($t->text('learn.title')) ?>" title="<?= e($t->text('learn.title')) ?>"><?= $template->render('partials/icon', ['icon' => 'back']) ?></a></p>
<h1><?= e($t->text('lichess.page_title')) ?></h1>

<?php foreach ($problems as $problem) : ?>
    <p class="notice notice-error"><?= e($problem) ?></p>
<?php endforeach; ?>

<?php // ===== 1. The daily puzzle ===== ?>
<?php if ($showDaily) : ?>
    <div class="play-tiles">
        <a class="play-tile" href="<?= e($basePath) ?>/learn/lichess/daily"
           aria-label="<?= e($t->text('lichess.daily')) ?>" title="<?= e($t->text('lichess.daily')) ?>">
            <?= $template->render('partials/icon', ['icon' => 'puzzle', 'size' => 64]) ?>
        </a>
    </div>
<?php endif; ?>

<?php // ===== 2. Live games ===== ?>
<?php if ($showLive) : ?>
    <section aria-labelledby="live-title">
        <h2 id="live-title"><?= e($t->text('lichess.live')) ?></h2>
        <ul class="text-list">
            <?php foreach ($live as $entry) : ?>
                <li>
                    <a href="<?= e($basePath) ?>/learn/tv?channel=<?= e(rawurlencode($entry['channel'])) ?>"><?= e($t->text('tv.channel.' . $entry['channel'])) ?></a>
                    <?php if ($entry['player'] !== '') : ?>
                        <span class="muted"><?= e($entry['player']) ?><?= $entry['rating'] > 0 ? ' (' . e($entry['rating']) . ')' : '' ?></span>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endif; ?>

<?php // ===== 3. Broadcasts ===== ?>
<?php if ($showBroadcasts) : ?>
    <section aria-labelledby="broadcasts-title">
        <h2 id="broadcasts-title"><?= e($t->text('lichess.broadcasts')) ?></h2>
        <?php foreach (['active' => 'lichess.broadcasts_now', 'upcoming' => 'lichess.broadcasts_soon'] as $kind => $headingKey) : ?>
            <?php if ($broadcasts[$kind] !== []) : ?>
                <h3><?= e($t->text($headingKey)) ?></h3>
                <ul class="text-list">
                    <?php foreach ($broadcasts[$kind] as $broadcast) : ?>
                        <li>
                            <a href="<?= e($basePath) ?>/learn/lichess/broadcast/<?= e($broadcast['roundId']) ?>"><?= e($broadcast['name']) ?></a>
                            <span class="muted"><?= e($broadcast['round']) ?><?= $broadcast['startsAt'] > 0 ? ' · ' . e(gmdate('Y-m-d H:i', $broadcast['startsAt'])) . ' UTC' : '' ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        <?php endforeach; ?>
        <?php if ($broadcasts['active'] === [] && $broadcasts['upcoming'] === []) : ?>
            <p class="muted"><?= e($t->text('lichess.no_broadcasts')) ?></p>
        <?php endif; ?>
    </section>
<?php endif; ?>

<p class="hint"><?= e($t->text('tv.credit')) ?></p>
