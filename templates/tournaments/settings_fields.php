<?php
/**
 * The settings of a tournament as form fields (T-01, A107), shared by the
 * create page and the organiser's settings view.
 *
 * Easy first: name, format, pace and who may join are big buttons; every
 * other setting has a sensible default and waits behind "More settings".
 *
 * Variables: $values (array<string, string|int|bool>: the current settings,
 * or [] for a new tournament), $timeControls (list of string), $maximumDays (int),
 * $lockFormat (bool: true once players are paired), $t, $template.
 */

use PhpChess\Domain\Tournament\TieBreaks;
use PhpChess\Domain\Tournament\Tournament;
use PhpChess\Domain\Tournament\TournamentRounds;

$value = fn (string $key, string|int|bool $default): string|int|bool => $values[$key] ?? $default;
$format = (string) $value('format', Tournament::FORMAT_SWISS);
$pace = (string) $value('pace', Tournament::PACE_CORRESPONDENCE);
$formatIcons = [
    Tournament::FORMAT_SWISS => 'swiss',
    Tournament::FORMAT_ROUND_ROBIN => 'circle',
    Tournament::FORMAT_KNOCKOUT => 'bracket',
    Tournament::FORMAT_LEAGUE => 'league',
];
?>
<label><?= e($t->text('tournament.field.name')) ?>
    <input type="text" name="name" maxlength="100" required value="<?= e((string) $value('name', '')) ?>">
</label>

<?php if (!$lockFormat) : ?>
    <fieldset class="choice-group choice-group-large">
        <legend><?= e($t->text('tournament.field.format')) ?></legend>
        <?php foreach ($formatIcons as $formatKey => $icon) : ?>
            <label class="choice"><input type="radio" name="format" value="<?= e($formatKey) ?>"<?= ($format === $formatKey) ? ' checked' : '' ?>>
                <?= $template->render('partials/icon', ['icon' => $icon, 'size' => 32]) ?><?= e($t->text('tournament.format.' . $formatKey)) ?>
                <small><?= e($t->text('tournament.format_hint.' . $formatKey)) ?></small></label>
        <?php endforeach; ?>
    </fieldset>
<?php else : ?>
    <input type="hidden" name="format" value="<?= e($format) ?>">
<?php endif; ?>

<fieldset class="choice-group">
    <legend><?= $template->render('partials/icon', ['icon' => 'correspondence']) ?><?= e($t->text('new_game.correspondence')) ?></legend>
    <?php foreach ([1, 2, 3, 5, 7] as $days) : ?>
        <?php if ($days <= $maximumDays) : ?>
            <label class="choice"><input type="radio" name="pace_choice" value="days:<?= e($days) ?>" data-pace="correspondence" data-days="<?= e($days) ?>"<?= ($pace === Tournament::PACE_CORRESPONDENCE && (int) $value('days_per_move', 3) === $days) ? ' checked' : '' ?>>
                <?= e($t->text('new_game.days', ['days' => $days])) ?><small><?= e($t->text('new_game.per_move')) ?></small></label>
        <?php endif; ?>
    <?php endforeach; ?>
</fieldset>
<?php if ($timeControls !== []) : ?>
    <fieldset class="choice-group">
        <legend><?= $template->render('partials/icon', ['icon' => 'live']) ?><?= e($t->text('new_game.live')) ?></legend>
        <?php foreach ($timeControls as $control) : ?>
            <label class="choice"><input type="radio" name="pace_choice" value="live:<?= e($control) ?>"<?= ($pace === Tournament::PACE_TIMED && $value('time_control', '') === $control) ? ' checked' : '' ?>>
                <?= e($control) ?><small><?= e($t->text('new_game.minutes_seconds')) ?></small></label>
        <?php endforeach; ?>
    </fieldset>
<?php endif; ?>
<?php // A pace button wins over the pace fields under "More settings" (TournamentManageController::settingsFrom). ?>

<fieldset class="choice-group">
    <legend><?= e($t->text('tournament.field.registration')) ?></legend>
    <?php foreach (Tournament::REGISTRATIONS as $registration) : ?>
        <label class="choice"><input type="radio" name="registration" value="<?= e($registration) ?>"<?= ($value('registration', Tournament::REGISTRATION_OPEN) === $registration) ? ' checked' : '' ?>>
            <?= e($t->text('tournament.registration.' . $registration)) ?><small><?= e($t->text('tournament.registration_hint.' . $registration)) ?></small></label>
    <?php endforeach; ?>
</fieldset>

<details class="more-settings">
    <summary><?= e($t->text('tournament.more_settings')) ?></summary>

    <label><?= e($t->text('tournament.field.description')) ?>
        <textarea name="description" rows="3" maxlength="2000"><?= e((string) $value('description', '')) ?></textarea>
    </label>

    <h3><?= e($t->text('tournament.field.pace')) ?></h3>
    <label><?= e($t->text('tournament.field.pace')) ?>
        <select name="pace">
            <option value="correspondence"<?= $pace === Tournament::PACE_CORRESPONDENCE ? ' selected' : '' ?>><?= e($t->text('new_game.correspondence')) ?></option>
            <option value="timed"<?= $pace === Tournament::PACE_TIMED ? ' selected' : '' ?>><?= e($t->text('new_game.live')) ?></option>
        </select>
    </label>
    <label><?= e($t->text('play.days_per_move')) ?>
        <input type="number" name="days_per_move" min="1" max="<?= e($maximumDays) ?>" value="<?= e((string) $value('days_per_move', 3)) ?>">
    </label>
    <label><?= e($t->text('tournament.field.time_control')) ?>
        <input type="text" name="time_control" maxlength="20" value="<?= e((string) $value('time_control', '')) ?>" placeholder="10+5">
    </label>
    <label><?= e($t->text('tournament.field.round_days')) ?>
        <input type="number" name="round_days" min="1" max="120" value="<?= e((string) $value('round_days', 14)) ?>">
    </label>
    <p class="hint"><?= e($t->text('tournament.field.round_days_hint')) ?></p>

    <h3><?= e($t->text('tournament.field.rules')) ?></h3>
    <fieldset class="choice-group">
        <legend><?= e($t->text('tournament.field.forfeit_rule')) ?></legend>
        <?php foreach (TournamentRounds::FORFEIT_RULES as $rule) : ?>
            <label class="choice"><input type="radio" name="forfeit_rule" value="<?= e($rule) ?>"<?= ($value('forfeit_rule', TournamentRounds::FORFEIT_LOSS_ON_TIME) === $rule) ? ' checked' : '' ?>>
                <?= e($t->text('tournament.forfeit_short.' . $rule)) ?></label>
        <?php endforeach; ?>
    </fieldset>
    <label class="checkbox"><input type="checkbox" name="is_rated" value="yes"<?= ((bool) $value('is_rated', true)) ? ' checked' : '' ?>> <?= e($t->text('play.rated')) ?></label>
    <label class="checkbox"><input type="checkbox" name="confirm_pairings" value="yes"<?= ((bool) $value('confirm_pairings', false)) ? ' checked' : '' ?>> <?= e($t->text('tournament.field.confirm_pairings')) ?></label>
    <label class="checkbox"><input type="checkbox" name="allow_computers" value="yes"<?= ((bool) $value('allow_computers', false)) ? ' checked' : '' ?>> <?= e($t->text('tournament.field.allow_computers')) ?></label>
    <label><?= e($t->text('tournament.field.max_players')) ?>
        <input type="number" name="max_players" min="0" max="200" value="<?= e((string) $value('max_players', 0)) ?>">
    </label>

    <h3><?= e($t->text('tournament.format.swiss')) ?></h3>
    <label><?= e($t->text('tournament.field.rounds_planned')) ?>
        <input type="number" name="rounds_planned" min="0" max="30" value="<?= e((string) $value('rounds_planned', 0)) ?>">
    </label>
    <p class="hint"><?= e($t->text('tournament.field.rounds_planned_hint')) ?></p>
    <?php foreach (['bye_halves' => 2, 'absence_bye_halves' => 1] as $field => $default) : ?>
        <label><?= e($t->text('tournament.field.' . $field)) ?>
            <select name="<?= e($field) ?>">
                <?php foreach ([0 => '0', 1 => '½', 2 => '1'] as $halves => $label) : ?>
                    <option value="<?= e($halves) ?>"<?= (int) $value($field, $default) === $halves ? ' selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
    <?php endforeach; ?>

    <h3><?= e($t->text('tournament.format.round_robin')) ?> · <?= e($t->text('tournament.format.league')) ?></h3>
    <label class="checkbox"><input type="checkbox" name="double_round" value="yes"<?= ((bool) $value('double_round', false)) ? ' checked' : '' ?>> <?= e($t->text('tournament.field.double_round')) ?></label>
    <?php foreach (['divisions' => 1, 'promote_count' => 2, 'relegate_count' => 2] as $field => $default) : ?>
        <label><?= e($t->text('tournament.field.' . $field)) ?>
            <input type="number" name="<?= e($field) ?>" min="<?= $field === 'divisions' ? '1' : '0' ?>" max="20" value="<?= e((string) $value($field, $default)) ?>">
        </label>
    <?php endforeach; ?>

    <h3><?= e($t->text('tournament.format.knockout')) ?></h3>
    <label><?= e($t->text('tournament.field.knockout_games')) ?>
        <select name="knockout_games">
            <option value="1"<?= (int) $value('knockout_games', 1) === 1 ? ' selected' : '' ?>>1</option>
            <option value="2"<?= (int) $value('knockout_games', 1) === 2 ? ' selected' : '' ?>>2</option>
        </select>
    </label>
    <p class="hint"><?= e($t->text('tournament.field.knockout_games_hint')) ?></p>
    <label class="checkbox"><input type="checkbox" name="third_place" value="yes"<?= ((bool) $value('third_place', false)) ? ' checked' : '' ?>> <?= e($t->text('tournament.field.third_place')) ?></label>

    <h3><?= e($t->text('tournament.field.tie_breaks')) ?></h3>
    <label><?= e($t->text('tournament.field.tie_breaks')) ?>
        <input type="text" name="tie_breaks" value="<?= e((string) $value('tie_breaks', '')) ?>" placeholder="<?= e(implode(',', TieBreaks::defaultsFor($format))) ?>">
    </label>
    <p class="hint"><?= e($t->text('tournament.field.tie_breaks_hint', ['keys' => implode(', ', TieBreaks::ALL)])) ?></p>
</details>
