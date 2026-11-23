<?php
/**
 * The member's preferences (ID-13): language, light/dark, board, pieces, email notices.
 *
 * Variables: $preferences (Preferences), $languages (list of string),
 * $basePath, $csrfToken, $t.
 */

use PhpChess\Domain\Identity\Preferences;

?>
<h1><?= e($t->text('preferences.title')) ?></h1>

<form method="post" action="<?= e($basePath) ?>/account/preferences" class="form">
    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">

    <label><?= e($t->text('preferences.language')) ?>
        <select name="language">
            <?php foreach ($languages as $language) : ?>
                <option value="<?= e($language) ?>"<?= $language === $preferences->language ? ' selected' : '' ?>><?= e($t->text('language.' . $language)) ?></option>
            <?php endforeach; ?>
        </select>
    </label>

    <fieldset>
        <legend><?= e($t->text('preferences.colour_scheme')) ?></legend>
        <?php foreach (Preferences::COLOUR_SCHEMES as $scheme) : ?>
            <label class="checkbox"><input type="radio" name="colour_scheme" value="<?= e($scheme) ?>"<?= $scheme === $preferences->colourScheme ? ' checked' : '' ?>> <?= e($t->text('preferences.colour_scheme.' . $scheme)) ?></label>
        <?php endforeach; ?>
    </fieldset>

    <label><?= e($t->text('preferences.board')) ?>
        <select name="board_theme">
            <?php foreach (Preferences::BOARD_THEMES as $board) : ?>
                <option value="<?= e($board) ?>"<?= $board === $preferences->boardTheme ? ' selected' : '' ?>><?= e($t->text('preferences.board.' . $board)) ?></option>
            <?php endforeach; ?>
        </select>
    </label>

    <label><?= e($t->text('preferences.pieces')) ?>
        <select name="piece_set">
            <?php foreach (Preferences::PIECE_SETS as $pieces) : ?>
                <option value="<?= e($pieces) ?>"<?= $pieces === $preferences->pieceSet ? ' selected' : '' ?>><?= e($t->text('preferences.pieces.' . $pieces)) ?></option>
            <?php endforeach; ?>
        </select>
    </label>

    <label class="checkbox"><input type="checkbox" name="email_notices" value="yes"<?= $preferences->emailNotices ? ' checked' : '' ?>> <?= e($t->text('preferences.email_notices')) ?></label>

    <button type="submit"><?= e($t->text('preferences.button')) ?></button>
</form>

<p><a href="<?= e($basePath) ?>/account/password"><?= e($t->text('password.title')) ?></a></p>
