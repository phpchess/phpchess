/**
 * The installable app on every page (UI-05).
 *
 * - Registers the service worker (GET /sw.js), which keeps pages for
 *   offline viewing and shows push messages (templates/pwa/service_worker.php).
 * - After a logout (body data-member="no"), deletes the pages kept for
 *   offline viewing, so the next person on a shared device can't read them.
 * - Sets the app badge on the home-screen icon to the number of games
 *   waiting for the member's move (body data-my-move-count), where the
 *   browser supports it.
 * - Fills a time-zone field marked data-fill-time-zone with the browser's
 *   own time zone, when it is still "UTC" (the notification settings, N-04).
 *
 * Nothing here talks to any other site (K9).
 */

const body = document.body;
const basePath = body.dataset.basePath || '';

// --- The service worker ---

if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register(basePath + '/sw.js', {scope: basePath + '/'}).catch(() => {
        // Without a service worker the site still works, just not offline.
    });
}

// --- Nobody logged in: forget the saved pages ---
// School tablets are shared (B13): after a logout, the next pupil must not
// read the last one's games offline. Assets stay; only kept pages go.

if (body.dataset.member === 'no' && 'caches' in window) {
    caches.keys().then((names) => Promise.all(
        names.filter((name) => name.endsWith('-pages')).map((name) => caches.delete(name)),
    )).catch(() => undefined);
}

// --- The app badge ---

const myMoveCount = parseInt(body.dataset.myMoveCount || '0', 10);
if ('setAppBadge' in navigator) {
    const badge = myMoveCount > 0 ? navigator.setAppBadge(myMoveCount) : navigator.clearAppBadge();
    badge.catch(() => undefined);
}

// --- The time zone in the notification settings ---

const timeZoneField = document.querySelector('[data-fill-time-zone]');
if (timeZoneField instanceof HTMLInputElement && timeZoneField.value === 'UTC') {
    const browserZone = Intl.DateTimeFormat().resolvedOptions().timeZone;
    if (browserZone) {
        timeZoneField.value = browserZone;
    }
}
