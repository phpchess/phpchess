<?php
/**
 * School: my classes (SCH-02), and a form for a new one.
 *
 * Variables: $classes (list of array{id, name, limit_to_class, member_count}), $basePath, $csrfToken, $t.
 */
?>
<h1><?= e($t->text('school.title')) ?></h1>

<?php if ($classes === []) : ?>
    <p class="muted"><?= e($t->text('school.no_classes')) ?></p>
<?php else : ?>
    <ul class="card-list">
        <?php foreach ($classes as $class) : ?>
            <li class="card">
                <a class="card-link" href="<?= e($basePath) ?>/school/classes/<?= e($class['id']) ?>">
                    <strong><?= e($class['name']) ?></strong>
                    <span class="muted"><?= e($t->text('school.member_count', ['count' => $class['member_count']])) ?></span>
                    <?php if ($class['limit_to_class']) : ?>
                        <span class="muted">· <?= e($t->text('school.limited')) ?></span>
                    <?php endif; ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<h2><?= e($t->text('school.new_class')) ?></h2>
<form method="post" action="<?= e($basePath) ?>/school/classes" class="form">
    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
    <label for="name"><?= e($t->text('school.class_name')) ?></label>
    <input type="text" id="name" name="name" required maxlength="80">
    <button type="submit" class="button"><?= e($t->text('school.create_class')) ?></button>
</form>
