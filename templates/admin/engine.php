<?php
/**
 * The admin's engine page (AI-07, decisions A47, A49, A51): the active
 * engine, the depth cap, and every computer player with its settings and
 * its latest calibration result.
 *
 * Variables: $engineName, $engineVersion, $engineLicence, $engineSource,
 * $licenceUrl (string); $depthCap (int);
 * $players (list of ComputerPlayer);
 * $calibration (array{date: string, games: int, players: array<int, array{elo, low, high}>}|null);
 * $warnings, $currentAdminPath, $basePath, $csrfToken, $t, $template.
 */

?>
<?= $template->render('admin/header', ['warnings' => $warnings, 'currentAdminPath' => $currentAdminPath, 'basePath' => $basePath, 't' => $t]) ?>

<h1><?= e($t->text('admin.engine.title')) ?></h1>

<!-- ===== The active engine (A47) ===== -->
<h2><?= e($t->text('admin.engine.active')) ?></h2>
<dl class="facts">
    <dt><?= e($t->text('admin.engine.name')) ?></dt><dd><?= e($engineName) ?></dd>
    <dt><?= e($t->text('admin.engine.version')) ?></dt><dd><?= e($engineVersion) ?></dd>
    <dt><?= e($t->text('admin.engine.licence')) ?></dt>
    <dd><?= e($engineLicence) ?> (<a href="<?= e($licenceUrl) ?>"><?= e($t->text('admin.engine.licence_file')) ?></a>)</dd>
    <dt><?= e($t->text('admin.engine.source')) ?></dt><dd><?= e($engineSource) ?></dd>
</dl>
<p class="hint"><?= e($t->text('admin.engine.howto')) ?></p>

<!-- ===== The depth cap (A49) ===== -->
<form method="post" action="<?= e($basePath) ?>/admin/engine" class="form">
    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
    <label><?= e($t->text('admin.engine.depth_cap')) ?>
        <input type="number" name="depth_cap" min="1" max="10" value="<?= e($depthCap) ?>">
    </label>
    <p class="hint"><?= e($t->text('admin.engine.depth_cap_hint')) ?></p>
    <button type="submit"><?= e($t->text('admin.save')) ?></button>
</form>

<!-- ===== The computer players and their calibration (A51, A53) ===== -->
<h2><?= e($t->text('admin.engine.players')) ?></h2>
<?php if ($calibration === null) : ?>
    <p class="muted"><?= e($t->text('admin.engine.not_calibrated')) ?></p>
<?php else : ?>
    <p class="muted"><?= e($t->text('admin.engine.calibration', ['date' => $calibration['date'], 'games' => $calibration['games']])) ?></p>
<?php endif; ?>
<div class="table-scroll">
    <table class="data-table">
        <thead>
            <tr>
                <th><?= e($t->text('admin.engine.player')) ?></th>
                <th><?= e($t->text('admin.engine.listed')) ?></th>
                <th><?= e($t->text('admin.engine.personality')) ?></th>
                <th><?= e($t->text('admin.engine.depth')) ?></th>
                <th><?= e($t->text('admin.engine.margin')) ?></th>
                <th><?= e($t->text('admin.engine.measured')) ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($players as $player) : ?>
                <?php
                $normal = is_array($player->engine['normal'] ?? null) ? $player->engine['normal'] : [];
                $depth = is_array($normal['depth'] ?? null) ? ($normal['depth']['baseDepth'] ?? '–') : '–';
                $measured = $calibration['players'][$player->id] ?? null;
                ?>
                <tr>
                    <td>#<?= e($player->id) ?> <?= e($player->name) ?></td>
                    <td><?= e($player->elo) ?></td>
                    <td><?= e($t->text('computer.personality.' . $player->personality)) ?></td>
                    <td><?= e(is_scalar($depth) ? $depth : '–') ?></td>
                    <td><?= e(is_scalar($normal['marginCentipawns'] ?? null) ? $normal['marginCentipawns'] : '–') ?></td>
                    <?php $measuredText = $measured === null ? '–' : $measured['elo'] . ' (' . $measured['low'] . '–' . $measured['high'] . ')'; ?>
                    <td><?= e($measuredText) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
