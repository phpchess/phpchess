<?php
/**
 * One link of the main menu, with an optional number badge (A44: games waiting, unread messages).
 *
 * Variables: $href (string, full address), $label (string), $isCurrent (bool),
 * $badge (int, 0 for none), $t (Translator).
 */
?>
<li><a href="<?= e($href) ?>"<?= $isCurrent ? ' aria-current="page"' : '' ?>><?= e($label) ?><?php if ($badge > 0) : ?> <span class="badge" aria-label="<?= e($t->text('layout.waiting', ['count' => $badge])) ?>"><?= e($badge) ?></span><?php endif; ?></a></li>
