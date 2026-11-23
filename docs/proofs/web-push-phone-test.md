# Web Push on real phones: the A18 decision test (C2-2)

A18 says: if Web Push is not good enough on a real iPhone and a real Android phone, a native app becomes its own chunk. This is the test, ready to run.

## What is needed first
- **The site on HTTPS with a real certificate, reachable from the phones.** Phones accept service workers and push only from a secure address; `pc6.localhost` can't be reached from a phone. Use the shared host planned for the release check, or another HTTPS test address.
- **A working cron or pseudo-cron** on that host: the push job runs every minute.
- Two accounts, for example `anna` (on the phone) and `ben` (on a computer).
- An iPhone with iOS 16.4 or later, and an Android phone with Chrome.

## Steps
1. As admin, open **Admin → Switches** and turn on **Push notifications**.
2. **iPhone**: open the site in Safari, then Share → **Add to Home Screen**, and open phpChess from the new icon. (Push works only in the installed app on iPhone.)
   **Android**: open the site in Chrome. Optionally use "Install app" / "Add to Home screen".
3. Log in as `anna` → *your name* → **Notifications**. Set "My games" to **Push to my devices**, save, then tap **Turn on push here** and allow.
4. On the computer, log in as `ben`, start a game against `anna` and make a move.
5. Within about a minute the phone should show "Your move against ben".

## What to check (write the results under each phone)
| Check | iPhone | Android |
|---|---|---|
| The permission question appears only after the tap | | |
| The push arrives within about a minute, with the screen locked | | |
| Tapping it opens that game | | |
| The app icon shows the number of games waiting (badge) | | |
| Quiet hours (set from now to 2 hours later): no push; it arrives afterwards | | |
| "Stop push" on the notifications page: no more pushes | | |
| Offline: airplane mode, the start page and opened games still show | | |
| Offline move: make a move in airplane mode; it is sent when back online | | |

## Decision
- All good on both phones: Web Push stays, no native app (A18).
- Not good enough: note what failed; a native app becomes its own chunk.
