/**
 * "Turn on push here" on the notification settings page (N-03).
 *
 * After the member taps the button (never before: A18, and browsers insist):
 *   1. the browser asks the member to allow notifications;
 *   2. the service worker subscribes at the browser maker's push service,
 *      with the site's public VAPID key (data-vapid-public-key);
 *   3. the subscription (an address and two keys) is sent to our server,
 *      POST /account/push/subscribe, which stores it.
 * The push service is contacted by the browser itself, not by this page,
 * so the page's own rule "connect only to this site" (K9) still holds.
 * On an iPhone this works only in the app installed from Safari's Share
 * menu ("Add to Home Screen"), from iOS 16.4.
 */

const section = document.getElementById('push-section');
const button = document.getElementById('push-button');
const status = document.getElementById('push-status');

/** The VAPID key comes as URL-safe base64; the browser wants the bytes. */
function keyBytes(base64Url) {
    const padding = '='.repeat((4 - (base64Url.length % 4)) % 4);
    const base64 = (base64Url + padding).replace(/-/g, '+').replace(/_/g, '/');
    return Uint8Array.from(atob(base64), (character) => character.charCodeAt(0));
}

/** A key of the subscription as URL-safe base64, as the server stores it. */
function keyText(subscription, name) {
    const bytes = new Uint8Array(subscription.getKey(name));
    return btoa(String.fromCharCode(...bytes)).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
}

async function turnOnPush() {
    if (!('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window)) {
        status.textContent = section.dataset.textUnsupported;
        return;
    }
    const permission = await Notification.requestPermission();
    if (permission !== 'granted') {
        status.textContent = section.dataset.textDenied;
        return;
    }
    try {
        const registration = await navigator.serviceWorker.ready;
        const subscription = await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: keyBytes(section.dataset.vapidPublicKey),
        });
        const response = await fetch(section.dataset.subscribeUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'Accept': 'application/json'},
            body: new URLSearchParams({
                _csrf: section.dataset.csrfToken,
                endpoint: subscription.endpoint,
                p256dh: keyText(subscription, 'p256dh'),
                auth: keyText(subscription, 'auth'),
            }),
        });
        status.textContent = response.ok ? section.dataset.textDone : section.dataset.textFailed;
        if (response.ok) {
            window.setTimeout(() => window.location.reload(), 1500);
        }
    } catch (error) {
        status.textContent = section.dataset.textFailed;
    }
}

if (section && button && status) {
    button.addEventListener('click', turnOnPush);
}
