<?php
/**
 * One player card in the League editor (A103): the name, the rating, and
 * four arrow buttons (up, down, to the division above, to the one below)
 * for people who would rather tap than drag.
 *
 * Variables: $player (TournamentPlayer), $isEditable (bool), $t, $template.
 */
?>
<li class="league-card" data-user-id="<?= e($player->userId) ?>">
    <span class="league-card-name"><?= e($player->username) ?>
        <?php if ($player->rating > 0) : ?><small class="muted"><?= e($player->rating) ?></small><?php endif; ?>
    </span>
    <?php if ($isEditable) : ?>
        <span class="league-card-buttons">
            <?php foreach (['up' => 'league.move_up', 'down' => 'league.move_down', 'previous' => 'league.move_division_up', 'next' => 'league.move_division_down'] as $icon => $labelKey) : ?>
                <button type="button" class="icon-button button-secondary button" data-league-move="<?= e($icon) ?>"
                        aria-label="<?= e($t->text($labelKey)) ?>" title="<?= e($t->text($labelKey)) ?>"><?= $template->render('partials/icon', ['icon' => $icon, 'size' => 18]) ?></button>
            <?php endforeach; ?>
        </span>
    <?php endif; ?>
</li>
