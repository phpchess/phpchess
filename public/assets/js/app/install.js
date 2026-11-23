/**
 * The installable app on every page (UI-05).
 *
 * - Registers the service worker (GET /sw.js), which keeps pages for
 *   offline viewing and shows push messages (templates/pwa/service_worker.php).
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
