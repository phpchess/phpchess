<?php
/**
 * An invite, for whoever opened the link (G-21). Visitors log in or register first.
 *
 * Variables: $challenge (Challenge|null; null when the link is unknown, used
 * or expired), $challengerName (string), $token (string), $isOwnInvite (bool),
 * $user (User|null), $basePath, $csrfToken, $t.
 */
?>
<h1><?= e($t->text('invite.title')) ?></h1>

<?php if ($challenge === null) : ?>
    <p><?= e($t->text('invite.not_valid')) ?></p>
<?php else : ?>
    <p><?= e($t->text('invite.from', ['name' => $challengerName])) ?></p>
    <p class="muted">
        <?= e($t->text('challenge.summary', ['days' => $challenge->daysPerMove])) ?>
        · <?= e($t->text($challenge->isRated ? 'play.rated' : 'game.unrated')) ?>
    </p>
    <?php if ($user === null) : ?>
        <p class="button-row">
            <a class="button" href="<?= e($basePath) ?>/login?next=<?= e(rawurlencode('/invite/' . $token)) ?>"><?= e($t->text('layout.login')) ?></a>
            <a class="button button-secondary" href="<?= e($basePath) ?>/register"><?= e($t->text('register.title')) ?></a>
        </p>
    <?php elseif ($isOwnInvite) : ?>
        <p class="muted"><?= e($t->text('invite.own')) ?></p>
    <?php else : ?>
        <form method="post" action="<?= e($basePath) ?>/invite/<?= e($token) ?>" class="inline-form">
            <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
            <button type="submit" class="button-large"><?= e($t->text('invite.accept')) ?></button>
        </form>
    <?php endif; ?>
<?php endif; ?>
