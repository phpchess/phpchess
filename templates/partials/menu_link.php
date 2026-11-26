<?php
/**
 * One button of the main menu: an icon, its name and an optional number
 * badge (A44: games waiting, unread messages; F2: A67, A68).
 *
 * Variables: $href (string, full address), $label (string), $icon (string, a name
 * from partials/icon.php), $isCurrent (bool), $badge (int, 0 for none),
 * $t (Translator), $template (Template).
 */
?>
<li>
    <a href="<?= e($href) ?>"<?= $isCurrent ? ' aria-current="page"' : '' ?>>
        <?= $template->render('partials/icon', ['name' => $icon]) ?>
        <span class="nav-label"><?= e($label) ?></span>
        <?php if ($badge > 0) : ?>
            <span class="nav-badge" aria-label="<?= e($t->text('layout.waiting', ['count' => $badge])) ?>"><?= e($badge) ?></span>
        <?php endif; ?>
    </a>
</li>
