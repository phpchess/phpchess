<?php
/**
 * The guest logins just made (A109), shown once: names and passwords to
 * print or hand out, for example to a visiting class. The passwords are
 * not stored readable; a lost one is set again by an admin.
 *
 * Variables: $tournament (Tournament), $logins (array<string, string>: name => password),
 * $loginUrl (string), $basePath, $t, $template.
 */
?>
<h1><?= e($t->text('tournament.guests_title')) ?></h1>
<p><?= e($t->text('tournament.guests_once')) ?></p>
<p><?= e($t->text('tournament.guests_login_at')) ?> <span class="copyable"><?= e($loginUrl) ?></span></p>

<div class="table-wrap">
    <table class="data-table">
        <thead>
            <tr>
                <th scope="col"><?= e($t->text('tournament.username')) ?></th>
                <th scope="col"><?= e($t->text('tournament.password')) ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($logins as $username => $password) : ?>
                <tr>
                    <td class="copyable"><?= e($username) ?></td>
                    <td class="copyable"><?= e($password) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<p><a class="button" href="<?= e($basePath) ?>/tournaments/<?= e($tournament->id) ?>/manage"><?= $template->render('partials/icon', ['icon' => 'back']) ?> <?= e($t->text('tournament.back_to_desk')) ?></a></p>
