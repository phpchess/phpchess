/**
 * The Play page (G-16, A46; F2: A69, A70).
 *
 * Two jobs:
 *
 * 1. The new game pop-up. Each tile (Computer, Live, Correspondence) opens
 *    the pop-up with its own choices already picked. Without this script the
 *    tiles are plain links to "/play?new=...", which show the same pop-up.
 *
 * 2. The live boxes. Asks GET /play/news every few seconds while the page is open:
 *    - the asking itself keeps my open timed challenges in the lobby (the
 *      server lists them only while their poster is here);
 *    - when someone accepted one of them, the answer names the new game and
 *      the page goes there at once (the clock is already running);
 *    - when "boxes_version" changed (a move was made, a challenge came or
 *      went), the boxes are fetched from GET /play/boxes and swapped in,
 *      without reloading the page.
 *
 * Uses the same transport as the game page (transport.js).
 */

import {startTransport} from '../board/transport.js';

const script = document.getElementById('lobby-script');
const basePath = script.dataset.basePath;
const popUp = document.getElementById('new-game');

// ===== 1. The new game pop-up =====

/** Ticks the radio button with this name and value, if the pop-up has one. */
function pick(name, value) {
    const button = popUp.querySelector('input[name="' + name + '"][value="' + value + '"]');
    if (button) {
        button.checked = true;
    }
}

/** Ticks the first radio button whose value starts with the given text. */
function pickFirstStartingWith(name, start) {
    const button = [...popUp.querySelectorAll('input[name="' + name + '"]')].find((input) => input.value.startsWith(start));
    if (button) {
        button.checked = true;
    }
}

document.querySelectorAll('[data-new-game]').forEach((tile) => {
    tile.addEventListener('click', (event) => {
        event.preventDefault();
        const kind = tile.dataset.newGame;
        if (kind === 'computer') {
            pick('opponent', 'computer');
        } else {
            pick('opponent', 'human');
            // Keep the pace already picked when it is of the right kind.
            const picked = popUp.querySelector('input[name="pace"]:checked');
            const wanted = kind === 'live' ? 'live:' : 'days:';
            if (!picked || !picked.value.startsWith(wanted)) {
                pickFirstStartingWith('pace', wanted);
            }
        }
        if (popUp.open) {
            popUp.close();
        }
        popUp.showModal();
    });
});

// The close button: a link back to /play without the script, closes the pop-up with it.
popUp.querySelector('[data-close-pop-up]').addEventListener('click', (event) => {
    event.preventDefault();
    popUp.close();
});

// A pop-up opened by the address (/play?new=...) becomes a proper modal one.
if (popUp.hasAttribute('open')) {
    popUp.close();
    popUp.showModal();
}

// ===== 2. The live boxes =====

/** Fetches the boxes again and puts them in place of the old ones. */
async function refreshBoxes() {
    const response = await fetch(script.dataset.boxesUrl, {credentials: 'same-origin'});
    if (!response.ok) {
        return;
    }
    const template = document.createElement('template');
    template.innerHTML = (await response.text()).trim();
    const newBoxes = template.content.getElementById('play-boxes');
    const oldBoxes = document.getElementById('play-boxes');
    // Never swap while a button inside is waiting for the server (busy-buttons.js).
    if (newBoxes && oldBoxes && !oldBoxes.querySelector('.is-busy')) {
        oldBoxes.replaceWith(newBoxes);
    }
}

startTransport({
    url: () => script.dataset.newsUrl,
    onNews: (answer) => {
        if (answer.new_game_id) {
            window.location.href = basePath + '/games/' + answer.new_game_id;
            return;
        }
        const shownVersion = document.getElementById('play-boxes')?.dataset.version;
        if (answer.boxes_version && answer.boxes_version !== shownVersion) {
            refreshBoxes().catch(() => {
                // No network for a moment: the next answer tries again.
            });
        }
    },
});
