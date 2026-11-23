<?php
/**
 * A member's avatar (C-02): their uploaded picture, a bundled one, or the
 * first letter of their name in a circle.
 *
 * Variables: $userId (int), $username (string), $avatar (string: '', 'upload' or a bundled name),
 * $size (int: 32, 64 or 128 pixels; community.css has a class for each), $basePath.
 */
?>
<?php if ($avatar === 'upload') : ?>
    <img class="avatar" src="<?= e($basePath) ?>/avatars/<?= e($userId) ?>" alt="" width="<?= e($size) ?>" height="<?= e($size) ?>">
<?php elseif ($avatar !== '') : ?>
    <img class="avatar" src="<?= e($basePath) ?>/assets/avatars/<?= e($avatar) ?>.svg" alt="" width="<?= e($size) ?>" height="<?= e($size) ?>">
<?php else : ?>
    <span class="avatar avatar-letter avatar-<?= e($size) ?>" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($username, 0, 1))) ?></span>
<?php endif; ?>
