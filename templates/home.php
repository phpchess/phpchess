<?php
/**
 * The start page (C1-2: a welcome; C2-1 adds the games).
 *
 * Variables: $version (string), $registrationIsOpen (bool), $user (User|null),
 * $basePath, $t (Translator).
 */
?>
<h1><?= e($t->text('home.heading')) ?></h1>

<?php if ($user === null) : ?>
    <p><?= e($t->text('home.welcome_visitor')) ?></p>
    <p class="button-row">
        <a class="button" href="<?= e($basePath) ?>/login"><?= e($t->text('layout.login')) ?></a>
        <?php if ($registrationIsOpen) : ?>
            <a class="button button-secondary" href="<?= e($basePath) ?>/register"><?= e($t->text('register.title')) ?></a>
        <?php endif; ?>
    </p>
<?php else : ?>
    <p><?= e($t->text('home.welcome_member', ['name' => $user->username])) ?></p>
<?php endif; ?>

<p class="muted"><?= e($t->text('home.version', ['version' => $version])) ?></p>
