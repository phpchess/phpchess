<?php
/**
 * One icon, drawn by code as inline SVG (F2, decision A67).
 *
 * Use:  <?= $template->render('partials/icon', ['icon' => 'computer']) ?>
 *
 * Every icon is a simple line drawing on a 24 x 24 grid. It uses
 * "currentColor", so it takes the colour of the text or button around it:
 * a new look (skin colours, dark mode) restyles every icon at once, and
 * there are no image files to redraw.
 *
 * To add an icon: add one line to $drawings below, with the name and the
 * SVG shapes. An unknown name draws an empty circle, so a typo is visible
 * but never breaks the page.
 *
 * Variables: $icon (string, the icon's name; not "$name", which Template::render() uses itself), $size (int, optional: width and height in pixels, default 24).
 */

// ===== The drawings: name => SVG shapes (fixed text, never from the user) =====
$drawings = [
    // Navigation
    'play' => '<path d="M8 5v14l11-7z"/>',
    'games' => '<rect x="3" y="3" width="18" height="18" rx="1"/><path d="M3 12h18M12 3v18"/><path d="M3 3h9v9H3zM12 12h9v9h-9z" fill="currentColor"/>',
    'players' => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c0-3.6 2.9-6 6.5-6s6.5 2.4 6.5 6"/><circle cx="17" cy="9" r="2.5"/><path d="M16.5 14c2.8 0 5 1.8 5 5"/>',
    'messages' => '<path d="M3 5h18v11H9l-5 4v-4H3z"/>',
    'school' => '<path d="M2 9l10-5 10 5-10 5z"/><path d="M6 11v5c3 2.5 9 2.5 12 0v-5"/>',
    'me' => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-7 8-7s8 2.6 8 7"/>',
    'admin' => '<circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3M4.9 4.9l2.1 2.1M17 17l2.1 2.1M4.9 19.1L7 17M17 7l2.1-2.1"/>',
    'logout' => '<path d="M14 4h5v16h-5"/><path d="M10 8l-4 4 4 4M6 12h10"/>',
    'login' => '<path d="M10 4H5v16h5"/><path d="M14 8l4 4-4 4M18 12H8"/>',
    'back' => '<path d="M15 5l-7 7 7 7"/>',
    'close' => '<path d="M6 6l12 12M18 6L6 18"/>',
    'check' => '<path d="M4 12.5l5 5L20 6.5"/>',
    // Kinds of game
    'computer' => '<rect x="3" y="4" width="18" height="12" rx="1"/><path d="M8 20h8M12 16v4"/><path d="M9 9h.01M15 9h.01M9 12.5c1.8 1 4.2 1 6 0"/>',
    'human' => '<circle cx="8" cy="7" r="3"/><circle cx="16" cy="7" r="3"/><path d="M2 20c0-3.3 2.7-6 6-6s6 2.7 6 6M14 14.2c.6-.1 1.3-.2 2-.2 3.3 0 6 2.7 6 6"/>',
    'live' => '<circle cx="12" cy="13" r="8"/><path d="M12 9v4l3 2M10 2h4"/>',
    'correspondence' => '<rect x="3" y="5" width="18" height="14" rx="1"/><path d="M3 6l9 7 9-7"/>',
    'strength' => '<path d="M4 20V14M10 20V10M16 20V6M22 20V3"/>',
    'colour' => '<circle cx="12" cy="12" r="9"/><path d="M12 3a9 9 0 0 1 0 18z" fill="currentColor"/>',
    'white' => '<circle cx="12" cy="12" r="8"/>',
    'black' => '<circle cx="12" cy="12" r="8" fill="currentColor"/>',
    'random' => '<circle cx="12" cy="12" r="8"/><path d="M12 4a8 8 0 0 1 0 16z" fill="currentColor"/>',
    'rated' => '<path d="M12 3l2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1-4.4-4.3 6.1-.9z"/>',
    'more' => '<circle cx="5" cy="12" r="1.5" fill="currentColor"/><circle cx="12" cy="12" r="1.5" fill="currentColor"/><circle cx="19" cy="12" r="1.5" fill="currentColor"/>',
    'news' => '<path d="M4 9v6h3l6 4V5L7 9z"/><path d="M17 9c1.3 1.6 1.3 4.4 0 6"/>',
    'rules' => '<path d="M13 2.5l8.5 8.5-3 3L10 5.5z"/><path d="M12.5 10.5L4 19"/><path d="M3 21.5h10"/>', // a gavel: head, handle, block
    'link' => '<path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1"/><path d="M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1"/>',
    'stats' => '<path d="M3 3v18h18"/><path d="M7 15l4-4 3 3 6-6"/>',
    // Tournaments (C4-1)
    'trophy' => '<path d="M7 4h10v5a5 5 0 0 1-10 0z"/><path d="M7 6H4a3 3 0 0 0 3 4M17 6h3a3 3 0 0 1-3 4"/><path d="M12 14v4M8 21h8M9 18h6"/>', // a cup
    'bracket' => '<path d="M3 5h5v4H3M3 15h5v4H3M8 7h3v10H8M11 12h4M15 9h6v6h-6z"/>', // a knockout bracket
    'swiss' => '<path d="M10 3h4v7h7v4h-7v7h-4v-7H3v-4h7z"/>', // a cross
    'circle' => '<circle cx="12" cy="12" r="8"/><circle cx="12" cy="4" r="1.5" fill="currentColor"/><circle cx="19" cy="15" r="1.5" fill="currentColor"/><circle cx="5" cy="15" r="1.5" fill="currentColor"/>', // everyone meets everyone
    'league' => '<path d="M4 5h16M4 12h16M4 19h16"/><path d="M8 3l-2 2 2 2M16 17l2 2-2 2"/>', // divisions, up and down
    'away' => '<circle cx="12" cy="12" r="9"/><path d="M5.6 5.6l12.8 12.8"/>',
    'calendar' => '<rect x="3" y="5" width="18" height="16" rx="1"/><path d="M3 10h18M8 3v4M16 3v4"/>', // a calendar page
    'search' => '<circle cx="10" cy="10" r="6"/><path d="M14.5 14.5L20 20"/>', // a magnifying glass
    'check_in' => '<circle cx="12" cy="12" r="9"/><path d="M8 12l3 3 5-6"/>', // a ticked circle
    'flag' => '<path d="M5 21V4M5 4h11l-2 4 2 4H5"/>',
    'pause' => '<path d="M8 5v14M16 5v14"/>',
    'add' => '<path d="M12 5v14M5 12h14"/>',
    'up' => '<path d="M5 15l7-7 7 7"/>',
    'down' => '<path d="M5 9l7 7 7-7"/>',
    'undo' => '<path d="M9 14L4 9l5-5"/><path d="M4 9h11a5 5 0 0 1 0 10h-3"/>',
    'download' => '<path d="M12 4v12M7 11l5 5 5-5"/><path d="M4 20h16"/>',
    // Learning (C5-1)
    'learn' => '<path d="M4 4h6a2 2 0 0 1 2 2v14a2 2 0 0 0-2-2H4z"/><path d="M20 4h-6a2 2 0 0 0-2 2v14a2 2 0 0 1 2-2h6z"/>', // an open book
    'puzzle' => '<path d="M5 8h3a2 2 0 1 1 4 0h3v3a2 2 0 1 1 0 4v4H5v-4a2 2 0 1 0 0-4z"/>', // a puzzle piece
    'tv' => '<rect x="3" y="6" width="18" height="13" rx="1"/><path d="M8 2l4 4 4-4"/>',
    'upload' => '<path d="M12 16V4M7 9l5-5 5 5"/><path d="M4 16v4h16v-4"/>',
    'next' => '<path d="M9 5l7 7-7 7"/>',
    'previous' => '<path d="M15 5l-7 7 7 7"/>',
    'first' => '<path d="M17 5l-7 7 7 7M7 5v14"/>',
    'last' => '<path d="M7 5l7 7-7 7M17 5v14"/>',
    'remove' => '<path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13"/>',
    'hint' => '<path d="M9 18h6M10 21h4"/><path d="M12 3a6 6 0 0 0-3.5 10.9V16h7v-2.1A6 6 0 0 0 12 3z"/>',
    // The seven replay controls (B45): |<<  <<  <  =  >  >>  >>|
    'replay-first' => '<path d="M5 5v14M13 6l-6 6 6 6M20 6l-6 6 6 6"/>',
    'replay-back' => '<path d="M12 6l-6 6 6 6M19 6l-6 6 6 6"/>',
    'replay-play-back' => '<path d="M17 5v14L6 12z"/>',
    'replay-stop' => '<path d="M6 9h12M6 15h12"/>',
    'replay-play' => '<path d="M7 5v14l11-7z"/>',
    'replay-forward' => '<path d="M5 6l6 6-6 6M12 6l6 6-6 6"/>',
    'replay-last' => '<path d="M4 6l6 6-6 6M11 6l6 6-6 6M19 5v14"/>',
];

$shapes = $drawings[$icon] ?? '<circle cx="12" cy="12" r="8"/>';
$pixels = $size ?? 24;
$svgHtml = '<svg class="icon icon-' . htmlspecialchars($icon, ENT_QUOTES) . '" width="' . (int) $pixels . '" height="' . (int) $pixels
    . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"'
    . ' aria-hidden="true" focusable="false">' . $shapes . '</svg>';
?>
<?= $svgHtml ?>
