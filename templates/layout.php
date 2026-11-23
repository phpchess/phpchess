<?php
/**
 * The frame of every page of the site (UI-03 mobile first, UI-04 accessibility, UI-01 skins).
 *
 * Variables:
 *   $title (string)          the page title, already translated
 *   $contentHtml (string)    the page itself, already rendered and escaped
 *   $siteName (string), $basePath (string), $csrfToken (string), $language (string)
 *   $user (User|null)        the logged-in account
 *   $skin (Skin)             the active skin (logo, layout)
 *   $colourScheme (string)   "auto", "light" or "dark" (the member's choice)
 *   $flashMessages (list of array{type: string, text: string})
 *   $currentPath (string)    to mark the current menu item
 *   $unreadNoticeCount (int) the badge on the notices link (N-01)
 *   $t (Translator)
 *
 * Raw HTML is only printed from variables whose names end in "Html" (K6).
 */
?>
<!doctype html>
<html lang="<?= e($language) ?>"<?php if ($colourScheme !== 'auto') : ?> data-colour-scheme="<?= e($colourScheme) ?>"<?php endif; ?>>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> - <?= e($siteName) ?></title>
    <link rel="stylesheet" href="<?= e($basePath) ?>/assets/css/base.css">
    <link rel="stylesheet" href="<?= e($basePath) ?>/assets/css/site.css">
    <link rel="stylesheet" href="<?= e($basePath) ?>/assets/css/play.css">
    <?php /* The skin comes last, so its colours replace the fallback values in base.css. */ ?>
    <link rel="stylesheet" href="<?= e($basePath) ?>/skin/theme.css">
</head>
<body class="layout-<?= e($skin->layout) ?>">
<a class="skip-link" href="#main"><?= e($t->text('layout.skip_to_content')) ?></a>

<header class="site-header">
    <a class="site-brand" href="<?= e($basePath) ?>/">
        <?php if ($skin->logoFile !== '') : ?>
            <img src="<?= e($basePath) ?>/skin/logo" alt="" width="32" height="32">
        <?php endif; ?>
        <span><?= e($siteName) ?></span>
    </a>

    <nav class="site-menu" aria-label="<?= e($t->text('layout.menu')) ?>">
        <ul>
            <?php if ($user === null) : ?>
                <li><a href="<?= e($basePath) ?>/login"<?= $currentPath === '/login' ? ' aria-current="page"' : '' ?>><?= e($t->text('layout.login')) ?></a></li>
            <?php else : ?>
                <li><a href="<?= e($basePath) ?>/play"<?= str_starts_with($currentPath, '/play') || str_starts_with($currentPath, '/games') ? ' aria-current="page"' : '' ?>><?= e($t->text('layout.play')) ?></a></li>
                <li><a href="<?= e($basePath) ?>/notices"<?= $currentPath === '/notices' ? ' aria-current="page"' : '' ?>><?= e($t->text('layout.notices')) ?><?php if ($unreadNoticeCount > 0) : ?> <span class="badge" aria-label="<?= e($t->text('layout.unread', ['count' => $unreadNoticeCount])) ?>"><?= e($unreadNoticeCount) ?></span><?php endif; ?></a></li>
                <li><a href="<?= e($basePath) ?>/account/preferences"<?= str_starts_with($currentPath, '/account') ? ' aria-current="page"' : '' ?>><?= e($user->username) ?></a></li>
                <?php if ($user->isAdmin()) : ?>
                    <li><a href="<?= e($basePath) ?>/admin"<?= str_starts_with($currentPath, '/admin') ? ' aria-current="page"' : '' ?>><?= e($t->text('layout.admin')) ?></a></li>
                <?php endif; ?>
                <li>
                    <form method="post" action="<?= e($basePath) ?>/logout" class="inline-form">
                        <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                        <button type="submit" class="link-button"><?= e($t->text('layout.logout')) ?></button>
                    </form>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
</header>

<main id="main" class="page">
    <?php foreach ($flashMessages as $message) : ?>
        <p class="notice-<?= $message['type'] === 'error' ? 'error' : 'success' ?>" role="<?= $message['type'] === 'error' ? 'alert' : 'status' ?>"><?= e($message['text']) ?></p>
    <?php endforeach; ?>

    <?= $contentHtml ?>
</main>

<footer class="site-footer">
    <p><?= e($t->text('layout.footer')) ?></p>
</footer>
</body>
</html>
