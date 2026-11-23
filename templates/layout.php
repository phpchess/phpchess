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
 *   $boardTheme (string)     the member's board colours (green, brown, blue, grey): mini boards and the board
 *   $flashMessages (list of array{type: string, text: string})
 *   $currentPath (string)    to mark the current menu item
 *   $myMoveCount (int)       games waiting for the member's move: the badge on Play and the app badge (A44, UI-05)
 *   $unreadMessageCount (int) the badge on Messages (C-04)
 *   $menuSwitches (array)    which switched features to show: messages, school, players, push
 *   $serverMessage (array{id, title, body}|null) the admin's server message (C-07)
 *   $isWide (bool)           a wider page (the dashboard, A44)
 *   $t (Translator)
 *
 * The page links the app manifest and registers the service worker
 * (UI-05, UI-08), both from this site only (K9).
 * Raw HTML is only printed from variables whose names end in "Html" (K6).
 */
$isOn = fn (string $name): bool => ($menuSwitches[$name] ?? false) === true;
$link = fn (string $path, string $label, bool $isCurrent, int $badge = 0): array => [
    'href' => $basePath . $path, 'label' => $label, 'isCurrent' => $isCurrent, 'badge' => $badge, 't' => $t,
];
?>
<!doctype html>
<html lang="<?= e($language) ?>"<?php if ($colourScheme !== 'auto') : ?> data-colour-scheme="<?= e($colourScheme) ?>"<?php endif; ?>>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= e($title) ?> - <?= e($siteName) ?></title>
    <link rel="manifest" href="<?= e($basePath) ?>/manifest.webmanifest">
    <link rel="icon" href="<?= e($basePath) ?>/assets/icons/icon-192.png" sizes="192x192">
    <link rel="apple-touch-icon" href="<?= e($basePath) ?>/assets/icons/apple-touch-icon.png">
    <meta name="theme-color" content="#2f6f4f">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <link rel="stylesheet" href="<?= e($basePath) ?>/assets/css/base.css">
    <link rel="stylesheet" href="<?= e($basePath) ?>/assets/css/site.css">
    <link rel="stylesheet" href="<?= e($basePath) ?>/assets/css/play.css">
    <link rel="stylesheet" href="<?= e($basePath) ?>/assets/css/community.css">
    <script type="module" src="<?= e($basePath) ?>/assets/js/busy-buttons.js"></script>
    <script type="module" src="<?= e($basePath) ?>/assets/js/app/install.js"></script>
    <?php /* The skin comes last, so its colours replace the fallback values in base.css. */ ?>
    <link rel="stylesheet" href="<?= e($basePath) ?>/skin/theme.css">
</head>
<body class="layout-<?= e($skin->layout) ?> board-theme-<?= e($boardTheme) ?>"
      data-base-path="<?= e($basePath) ?>" data-member="<?= $user === null ? 'no' : 'yes' ?>" data-my-move-count="<?= e($user === null ? 0 : $myMoveCount) ?>">
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
                <?= $template->render('partials/menu_link', $link('/login', $t->text('layout.login'), $currentPath === '/login')) ?>
            <?php else : ?>
                <?= $template->render('partials/menu_link', $link('/play', $t->text('layout.play'), str_starts_with($currentPath, '/play'), $myMoveCount)) ?>
                <?= $template->render('partials/menu_link', $link('/games', $t->text('layout.my_games'), str_starts_with($currentPath, '/games'))) ?>
                <?php if ($isOn('messages')) : ?>
                    <?= $template->render('partials/menu_link', $link('/messages', $t->text('layout.messages'), str_starts_with($currentPath, '/messages'), $unreadMessageCount)) ?>
                <?php endif; ?>
                <?= $template->render('partials/menu_link', $link('/players', $t->text('layout.players'), str_starts_with($currentPath, '/players'))) ?>
                <?php if ($isOn('school') && in_array($user->role, ['teacher', 'admin'], true)) : ?>
                    <?= $template->render('partials/menu_link', $link('/school', $t->text('layout.school'), str_starts_with($currentPath, '/school'))) ?>
                <?php endif; ?>
                <?= $template->render('partials/menu_link', $link('/account/preferences', $user->username, str_starts_with($currentPath, '/account'))) ?>
                <?php if ($user->isAdmin()) : ?>
                    <?= $template->render('partials/menu_link', $link('/admin', $t->text('layout.admin'), str_starts_with($currentPath, '/admin'))) ?>
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

<main id="main" class="page<?= $isWide ? ' page-wide' : '' ?>">
    <?php if ($serverMessage !== null) : ?>
        <aside class="server-message" role="note" aria-label="<?= e($t->text('content.server_message')) ?>">
            <strong><?= e($serverMessage['title']) ?></strong>
            <?php if ($serverMessage['body'] !== '') : ?><span><?= e($serverMessage['body']) ?></span><?php endif; ?>
        </aside>
    <?php endif; ?>
    <?php foreach ($flashMessages as $message) : ?>
        <p class="notice-<?= $message['type'] === 'error' ? 'error' : 'success' ?>" role="<?= $message['type'] === 'error' ? 'alert' : 'status' ?>"><?= e($message['text']) ?></p>
    <?php endforeach; ?>

    <?= $contentHtml ?>
</main>

<footer class="site-footer">
    <p>
        <a href="<?= e($basePath) ?>/faq"><?= e($t->text('content.faq')) ?></a>
        · <?= e($t->text('layout.footer')) ?>
    </p>
</footer>
</body>
</html>
