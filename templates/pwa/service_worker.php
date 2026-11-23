<?php
/**
 * The service worker (UI-05, UI-08, N-03), sent by GET /sw.js.
 *
 * A service worker is a script the browser keeps for the site and runs on
 * its own, even with no page open. phpChess uses it for three things:
 *
 *   1. Offline viewing (UI-08). Pages the member opened (start page, game
 *      list, games) are kept in a cache. With no connection, the kept copy
 *      is shown; without one, the offline page. Pages are always asked from
 *      the server first, so a member never sees an old page while online.
 *      Files under /assets/ change only with a new version, so the cache
 *      answers them first.
 *   2. Push (N-03): shows the message the server sent, sets the app badge
 *      (the number of games waiting for the member's move), and opens the
 *      right page when the member taps the message.
 *   3. Keeping itself up to date: a new version replaces the old caches.
 *
 * Only this site's own addresses are cached or fetched (K9).
 * It is served by a route (not as a file under /assets/), so it may look
 * after every page of the install. It finds the install's base path from
 * its own scope, so this template prints no values at all.
 *
 * Variables: none.
 */
?>
'use strict';

// --- Settings ---

/** The install's address path, such as "/chess" ("" in the web root). */
const BASE_PATH = new URL(self.registration.scope).pathname.replace(/\/$/, '');
const VERSION = 'phpchess-c3-1-1';
const PAGE_CACHE = VERSION + '-pages';
const ASSET_CACHE = VERSION + '-assets';
const OFFLINE_PAGE = BASE_PATH + '/offline';

/** Pages kept for offline viewing: the start page, the game list and the games. */
function isKeptPage(path) {
    return path === BASE_PATH + '/' || path === BASE_PATH + '/games' || /\/games\/\d+$/.test(path);
}

// --- 1. Installing and updating ---

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(PAGE_CACHE).then((cache) => cache.add(OFFLINE_PAGE)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((names) => Promise.all(names.filter((name) => !name.startsWith(VERSION)).map((name) => caches.delete(name))))
            .then(() => self.clients.claim()),
    );
});

// --- 2. Offline viewing ---

self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);
    if (request.method !== 'GET' || url.origin !== self.location.origin) {
        return;
    }

    if (url.pathname.startsWith(BASE_PATH + '/assets/')) {
        event.respondWith(cacheFirst(request));
        return;
    }
    if (request.mode === 'navigate') {
        event.respondWith(networkFirst(request, isKeptPage(url.pathname)));
    }
});

/** Assets: the kept copy, else the network (and keep it). */
async function cacheFirst(request) {
    const kept = await caches.match(request);
    if (kept) {
        return kept;
    }
    const response = await fetch(request);
    if (response.ok) {
        const cache = await caches.open(ASSET_CACHE);
        cache.put(request, response.clone());
    }
    return response;
}

/** Pages: the network first; offline, the kept copy, else the offline page. */
async function networkFirst(request, keepIt) {
    try {
        const response = await fetch(request);
        if (keepIt && response.ok) {
            const cache = await caches.open(PAGE_CACHE);
            cache.put(request, response.clone());
        }
        return response;
    } catch (error) {
        const kept = await caches.match(request);
        return kept || caches.match(OFFLINE_PAGE);
    }
}

// --- 3. Push messages ---

self.addEventListener('push', (event) => {
    let message = {title: 'phpChess', body: '', url: BASE_PATH + '/', badge: 0, tag: 'phpchess'};
    try {
        message = {...message, ...event.data.json()};
    } catch (error) {
        // A message without JSON: show the default text.
    }
    const shown = self.registration.showNotification(message.title, {
        body: message.body,
        tag: message.tag,
        icon: BASE_PATH + '/assets/icons/icon-192.png',
        badge: BASE_PATH + '/assets/icons/badge-96.png',
        data: {url: message.url},
    });
    const badge = 'setAppBadge' in self.navigator
        ? (message.badge > 0 ? self.navigator.setAppBadge(message.badge) : self.navigator.clearAppBadge())
        : Promise.resolve();
    event.waitUntil(Promise.all([shown, badge.catch(() => undefined)]));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const url = event.notification.data && event.notification.data.url ? event.notification.data.url : BASE_PATH + '/';
    event.waitUntil(
        self.clients.matchAll({type: 'window', includeUncontrolled: true}).then((windows) => {
            for (const client of windows) {
                if ('focus' in client && 'navigate' in client) {
                    return client.navigate(url).then((opened) => (opened || client).focus());
                }
            }
            return self.clients.openWindow(url);
        }),
    );
});
