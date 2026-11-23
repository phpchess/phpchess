<?php
/**
 * One class (SCH-02 to SCH-06): the members with their games, login cards
 * and passwords; adding students; the class limit.
 *
 * Variables: $class (array{id, name, limit_to_class}),
 * $members (list of array{id, username, role, last_seen_at, running, finished}),
 * $basePath, $csrfToken, $t.
 */
$classPath = $basePath . '/school/classes/' . $class['id'];
?>
<h1><?= e($class['name']) ?></h1>
<p><a href="<?= e($basePath) ?>/school">‹ <?= e($t->text('school.title')) ?></a></p>

<form method="post" action="<?= e($classPath) ?>/limit" class="form form-inline">
    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
    <label class="checkbox"><input type="checkbox" name="limit" value="yes"<?= $class['limit_to_class'] ? ' checked' : '' ?>> <?= e($t->text('school.limit_to_class')) ?></label>
    <button type="submit" class="button button-secondary button-small"><?= e($t->text('school.save')) ?></button>
</form>

<h2><?= e($t->text('school.members')) ?></h2>
<?php if ($members === []) : ?>
    <p class="muted"><?= e($t->text('school.no_members')) ?></p>
<?php else : ?>
    <form method="post" action="<?= e($classPath) ?>/cards" class="form-inline">
        <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
        <button type="submit" class="button"><?= e($t->text('school.print_all_cards')) ?></button>
        <span class="muted"><?= e($t->text('school.cards_replace')) ?></span>
    </form>
    <ul class="card-list">
        <?php foreach ($members as $member) : ?>
            <li class="card">
                <p class="card-title">
                    <strong><?= e($member['username']) ?></strong>
                    <span class="muted"><?= e($t->text('role.' . $member['role'])) ?></span>
                </p>
                <p>
                    <a href="<?= e($basePath) ?>/school/students/<?= e($member['id']) ?>/games"><?= e($t->text('school.games_count', ['running' => $member['running'], 'finished' => $member['finished']])) ?></a>
                </p>
                <details>
                    <summary><?= e($t->text('school.manage')) ?></summary>
                    <div class="button-row">
                        <form method="post" action="<?= e($classPath) ?>/cards" class="inline-form">
                            <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                            <input type="hidden" name="user" value="<?= e($member['id']) ?>">
                            <button type="submit" class="button button-secondary button-small"><?= e($t->text('school.new_card')) ?></button>
                        </form>
                        <form method="post" action="<?= e($classPath) ?>/members/<?= e($member['id']) ?>/revoke-card" class="inline-form">
                            <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                            <button type="submit" class="button button-secondary button-small"><?= e($t->text('school.revoke_card')) ?></button>
                        </form>
                        <form method="post" action="<?= e($classPath) ?>/members/<?= e($member['id']) ?>/remove" class="inline-form">
                            <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                            <button type="submit" class="button button-secondary button-small"><?= e($t->text('school.remove_member')) ?></button>
                        </form>
                    </div>
                    <form method="post" action="<?= e($classPath) ?>/members/<?= e($member['id']) ?>/password" class="form form-inline">
                        <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                        <label for="password-<?= e($member['id']) ?>"><?= e($t->text('school.new_password')) ?></label>
                        <input type="text" id="password-<?= e($member['id']) ?>" name="password" minlength="10" autocomplete="off" required>
                        <button type="submit" class="button button-secondary button-small"><?= e($t->text('school.set_password')) ?></button>
                    </form>
                </details>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<h2><?= e($t->text('school.add_students')) ?></h2>
<form method="post" action="<?= e($classPath) ?>/students" class="form">
    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
    <label for="names"><?= e($t->text('school.names_one_per_line')) ?></label>
    <textarea id="names" name="names" rows="8" required></textarea>
    <button type="submit" class="button"><?= e($t->text('school.create_students')) ?></button>
</form>

<h2><?= e($t->text('school.add_existing')) ?></h2>
<form method="post" action="<?= e($classPath) ?>/members" class="form form-inline">
    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
    <label for="username"><?= e($t->text('school.username')) ?></label>
    <input type="text" id="username" name="username" required maxlength="40">
    <button type="submit" class="button button-secondary"><?= e($t->text('school.add')) ?></button>
</form>
