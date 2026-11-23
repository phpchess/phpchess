<?php
/**
 * The play settings (A28, A30, A33, A34, A35, A37).
 *
 * Variables: $maxDaysPerMove, $ratingStart, $openDays, $directDays,
 * $inviteDays, $emailDelayMinutes (int); $abortRule, $defaultEmailMode, $planningBoardTheme,
 * $adjudicationReasons (string); $fenInDirect (bool);
 * $allowedEmailModes (list of string);
 * $warnings, $currentAdminPath, $basePath, $csrfToken, $t, $template.
 */

use PhpChess\Domain\Identity\Preferences;
use PhpChess\Domain\Notify\MoveEmails;
use PhpChess\Domain\Play\Games;

?>
<?= $template->render('admin/header', ['warnings' => $warnings, 'currentAdminPath' => $currentAdminPath, 'basePath' => $basePath, 't' => $t]) ?>

<h1><?= e($t->text('admin.play.title')) ?></h1>

<form method="post" action="<?= e($basePath) ?>/admin/play" class="form">
    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">

    <label><?= e($t->text('admin.play.max_days')) ?>
        <input type="number" name="max_days_per_move" min="1" max="31" value="<?= e($maxDaysPerMove) ?>">
    </label>
    <label><?= e($t->text('admin.play.rating_start')) ?>
        <input type="number" name="rating_start" min="100" max="3000" value="<?= e($ratingStart) ?>">
    </label>

    <fieldset>
        <legend><?= e($t->text('admin.play.abort_rule')) ?></legend>
        <?php foreach (Games::ABORT_RULES as $rule) : ?>
            <label class="checkbox"><input type="radio" name="abort_rule" value="<?= e($rule) ?>"<?= $rule === $abortRule ? ' checked' : '' ?>> <?= e($t->text('admin.play.abort_rule.' . $rule)) ?></label>
        <?php endforeach; ?>
    </fieldset>

    <fieldset>
        <legend><?= e($t->text('admin.play.expiry')) ?></legend>
        <label><?= e($t->text('challenge.kind.open')) ?> <input type="number" name="open_days" min="1" max="365" value="<?= e($openDays) ?>"></label>
        <label><?= e($t->text('challenge.kind.direct')) ?> <input type="number" name="direct_days" min="1" max="365" value="<?= e($directDays) ?>"></label>
        <label><?= e($t->text('challenge.kind.invite')) ?> <input type="number" name="invite_days" min="1" max="365" value="<?= e($inviteDays) ?>"></label>
    </fieldset>

    <label class="checkbox"><input type="checkbox" name="fen_in_direct" value="yes"<?= $fenInDirect ? ' checked' : '' ?>> <?= e($t->text('admin.play.fen_in_direct')) ?></label>

    <label><?= e($t->text('admin.play.planning_board_theme')) ?>
        <select name="planning_board_theme">
            <?php foreach (Preferences::BOARD_THEMES as $theme) : ?>
                <option value="<?= e($theme) ?>"<?= $theme === $planningBoardTheme ? ' selected' : '' ?>><?= e($t->text('preferences.board.' . $theme)) ?></option>
            <?php endforeach; ?>
        </select>
    </label>

    <fieldset>
        <legend><?= e($t->text('admin.play.email_modes')) ?></legend>
        <?php foreach (MoveEmails::MODES as $mode) : ?>
            <label class="checkbox"><input type="checkbox" name="email_mode_<?= e($mode) ?>" value="yes"<?= in_array($mode, $allowedEmailModes, true) ? ' checked' : '' ?>> <?= e($t->text('move_email.' . $mode)) ?></label>
        <?php endforeach; ?>
        <label><?= e($t->text('admin.play.email_default')) ?>
            <select name="default_email_mode">
                <?php foreach (MoveEmails::MODES as $mode) : ?>
                    <option value="<?= e($mode) ?>"<?= $mode === $defaultEmailMode ? ' selected' : '' ?>><?= e($t->text('move_email.' . $mode)) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label><?= e($t->text('admin.play.email_delay')) ?>
            <input type="number" name="email_delay_minutes" min="1" max="1440" value="<?= e($emailDelayMinutes) ?>">
        </label>
    </fieldset>

    <label><?= e($t->text('admin.play.adjudication_reasons')) ?>
        <input type="text" name="adjudication_reasons" value="<?= e($adjudicationReasons) ?>">
    </label>
    <p class="hint"><?= e($t->text('admin.play.adjudication_reasons_hint')) ?></p>

    <button type="submit"><?= e($t->text('admin.save')) ?></button>
</form>
