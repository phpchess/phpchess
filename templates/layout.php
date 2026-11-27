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
 *   $showStrengthTestPopUp (bool) the strength test pop-up after the first login (B15)
 *   $t (Translator)
 *
 * The page links the app manifest and registers the service worker
 * (UI-05, UI-08), both from this site only (K9).
 * Raw HTML is only printed from variables whose names end in "Html" (K6).
 */

// ===== Menu helpers =====
$isOn = fn (string $name): bool => ($menuSwitches[$name] ?? false) === true;
$link = fn (string $path, string $label, string $icon, bool $isCurrent, int $badge = 0): array => [
    'href' => $basePath . $path, 'label' => $label, 'icon' => $icon, 'isCurrent' => $isCurrent, 'badge' => $badge, 't' => $t,
];
$isAdminPage = str_starts_with($currentPath, '/admin');
$bodyClasses = 'layout-' . $skin->layout . ' board-theme-' . $boardTheme
    . ($user !== null ? ' has-nav' : '') . ($isAdminPage ? ' is-admin-page' : '');
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
    <link rel="stylesheet" href="<?= e($basePath) ?>/assets/css/base.css?v=2">
    <link rel="stylesheet" href="<?= e($basePath) ?>/assets/css/site.css?v=2">
    <link rel="stylesheet" href="<?= e($basePath) ?>/assets/css/play.css?v=7"><?php // ?v=: a new address after a change, so browsers drop the old copy ?>
    <link rel="stylesheet" href="<?= e($basePath) ?>/assets/css/community.css?v=2">
    <script type="module" src="<?= e($basePath) ?>/assets/js/busy-buttons.js"></script>
    <script type="module" src="<?= e($basePath) ?>/assets/js/app/install.js"></script>
    <?php /* The skin comes last, so its colours replace the fallback values in base.css. */ ?>
    <link rel="stylesheet" href="<?= e($basePath) ?>/skin/theme.css">
</head>
<body class="<?= e($bodyClasses) ?>"
      data-base-path="<?= e($basePath) ?>" data-member="<?= $user === null ? 'no' : 'yes' ?>" data-my-move-count="<?= e($user === null ? 0 : $myMoveCount) ?>">
<a class="skip-link" href="#main"><?= e($t->text('layout.skip_to_content')) ?></a>

<header class="site-header">
    <a class="site-brand" href="<?= e($basePath) ?>/">
        <?php if ($skin->logoFile !== '') : ?>
            <img src="<?= e($basePath) ?>/skin/logo" alt="" width="32" height="32">
        <?php endif; ?>
        <span><?= e($siteName) ?></span>
    </a>

    <?php /* ===== A few icon buttons on the right: school, admin, log out (or log in) ===== */ ?>
    <div class="header-actions">
        <?php if ($user === null) : ?>
            <a class="button" href="<?= e($basePath) ?>/login"><?= $template->render('partials/icon', ['icon' => 'login']) ?><?= e($t->text('layout.login')) ?></a>
        <?php else : ?>
            <?php if ($isOn('school') && in_array($user->role, ['teacher', 'admin'], true)) : ?>
                <a class="button button-secondary icon-button" href="<?= e($basePath) ?>/school" title="<?= e($t->text('layout.school')) ?>" aria-label="<?= e($t->text('layout.school')) ?>"<?= str_starts_with($currentPath, '/school') ? ' aria-current="page"' : '' ?>><?= $template->render('partials/icon', ['icon' => 'school']) ?></a>
            <?php endif; ?>
            <?php if ($user->isAdmin()) : ?>
                <a class="button button-secondary icon-button" href="<?= e($basePath) ?>/admin" title="<?= e($t->text('layout.admin')) ?>" aria-label="<?= e($t->text('layout.admin')) ?>"<?= $isAdminPage ? ' aria-current="page"' : '' ?>><?= $template->render('partials/icon', ['icon' => 'admin']) ?></a>
            <?php endif; ?>
            <form method="post" action="<?= e($basePath) ?>/logout">
                <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                <button type="submit" class="button-secondary icon-button" title="<?= e($t->text('layout.logout')) ?>" aria-label="<?= e($t->text('layout.logout')) ?>"><?= $template->render('partials/icon', ['icon' => 'logout']) ?></button>
            </form>
        <?php endif; ?>
    </div>
</header>

<div class="site-frame">
<?php if ($user !== null) : ?>
    <?php /* ===== The main menu: a tab bar at the bottom of a phone, a side bar on wider screens (A68) ===== */ ?>
    <nav class="site-nav" aria-label="<?= e($t->text('layout.menu')) ?>">
        <ul>
            <?= $template->render('partials/menu_link', $link('/play', $t->text('layout.play'), 'play', str_starts_with($currentPath, '/play'), $myMoveCount)) ?>
            <?= $template->render('partials/menu_link', $link('/games', $t->text('layout.my_games'), 'games', str_starts_with($currentPath, '/games'))) ?>
            <?php if ($isOn('messages')) : ?>
                <?= $template->render('partials/menu_link', $link('/messages', $t->text('layout.messages'), 'messages', str_starts_with($currentPath, '/messages'), $unreadMessageCount)) ?>
            <?php endif; ?>
            <?= $template->render('partials/menu_link', $link('/players', $t->text('layout.players'), 'players', str_starts_with($currentPath, '/players'))) ?>
            <?= $template->render('partials/menu_link', $link('/tournaments', $t->text('layout.tournaments'), 'trophy', str_starts_with($currentPath, '/tournaments'))) ?>
            <?= $template->render('partials/menu_link', $link('/learn', $t->text('layout.learn'), 'learn', str_starts_with($currentPath, '/learn'))) ?>
            <?= $template->render('partials/menu_link', $link('/rules', $t->text('layout.rules'), 'rules', $currentPath === '/rules')) ?>
            <?= $template->render('partials/menu_link', $link('/account/preferences', $t->text('layout.me'), 'me', str_starts_with($currentPath, '/account'))) ?>
        </ul>
    </nav>
<?php endif; ?>

<main id="main" class="page<?= $isWide ? ' page-wide' : '' ?><?= $isAdminPage ? ' page-admin' : '' ?>">
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
</div>

<?php
// The site-wide news poll (B21): for members, on every page except those
// that ask the server themselves (the Play page, a game, the strength test).
$hasOwnPoll = str_starts_with($currentPath, '/play') || str_starts_with($currentPath, '/games/') || str_starts_with($currentPath, '/strength-test');
?>
<?php if ($user !== null && !$hasOwnPoll) : ?>
    <?php /* Filled and shown by site-news.js when a timed challenge arrives. */ ?>
    <dialog class="pop-up" id="challenge-pop-up"
            data-text-title="<?= e($t->text('site_news.challenge_title')) ?>"
            data-text-details="<?= e($t->text('site_news.challenge_text')) ?>"
            data-text-rated="<?= e($t->text('site_news.rated')) ?>"
            data-text-unrated="<?= e($t->text('site_news.unrated')) ?>"
            data-text-colour-white="<?= e($t->text('site_news.colour.white')) ?>"
            data-text-colour-black="<?= e($t->text('site_news.colour.black')) ?>"
            data-text-colour-random="<?= e($t->text('site_news.colour.random')) ?>">
        <h2 id="challenge-pop-up-title"></h2>
        <p id="challenge-pop-up-details"></p>
        <div class="button-row">
            <form method="post" id="challenge-pop-up-accept">
                <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                <button type="submit"><?= e($t->text('site_news.accept')) ?></button>
            </form>
            <form method="post" id="challenge-pop-up-decline">
                <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                <button type="submit" class="button-secondary"><?= e($t->text('site_news.decline')) ?></button>
            </form>
        </div>
    </dialog>
    <script type="module" src="<?= e($basePath) ?>/assets/js/app/site-news.js"></script>
<?php endif; ?>

<?php if ($showStrengthTestPopUp) : ?>
    <?php /* The strength test pop-up, once after the first login (B15, A60). */ ?>
    <dialog class="pop-up" id="strength-test-pop-up" open>
        <h2><?= e($t->text('strength_test.pop_up_title')) ?></h2>
        <p><?= e($t->text('strength_test.pop_up_text')) ?></p>
        <?php /* A form with method="dialog" closes the pop-up without any script. */ ?>
        <form method="dialog" class="button-row">
            <a class="button" href="<?= e($basePath) ?>/strength-test"><?= e($t->text('strength_test.start')) ?></a>
            <button type="submit" class="button-secondary"><?= e($t->text('strength_test.later')) ?></button>
        </form>
    </dialog>
<?php endif; ?>

<footer class="site-footer">
    <p>
        <a href="<?= e($basePath) ?>/faq"><?= e($t->text('content.faq')) ?></a>
        · <?= e($t->text('layout.footer')) ?>
    </p>
</footer>
</body>
</html>
