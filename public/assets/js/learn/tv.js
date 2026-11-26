/**
 * Lichess TV on our own board (C5-1, L-01, A75): no iframe.
 *
 * templates/learn/tv.php draws the page; this script asks our own server
 * (GET /learn/tv/now?channel=...) every POLL_SECONDS for the game now on
 * the chosen channel, and shows the players and the game on the shared
 * replay (replay.js, B48): the whole game in the move table, and new moves
 * played one after the other until the next update, so it looks live.
 * Stepping back in the game is allowed; >>| returns to the live game. The browser
 * never talks to lichess.org itself (K9): our server does, and shares one
 * answer between all viewers.
 *
 * The page stops asking while it is hidden (another tab, a locked phone).
 *
 * Library (local, A16): cm-chessboard 8.15.8 in /assets/vendor/.
 */

import {FEN} from '../../vendor/cm-chessboard/src/model/Position.js';
import {Replay} from './replay.js';

// --- Reading what the server put on the page ---

const page = document.getElementById('lichess-tv');
const basePath = page.dataset.basePath;
const playersLine = document.getElementById('lichess-tv-players');
const statusLine = document.getElementById('lichess-tv-status');

/** How often to ask, in seconds. */
const POLL_SECONDS = 5;

/** Our server asks Lichess at most every 10 seconds, so new moves are spread over that time. */
const SPREAD_MILLISECONDS = 10000;

// The board starts with the start position (cm-chessboard has no 'start' word: use FEN.start).
const replay = new Replay(document.getElementById('lichess-tv-replay'), [{fen: FEN.start, san: '', from: '', to: '', comment: ''}], true);

let timer = null;

// --- Asking our server ---

async function showGameNow() {
    const channel = page.querySelector('input[name="channel"]:checked').value;
    try {
        const response = await fetch(basePath + '/learn/tv/now?channel=' + encodeURIComponent(channel), {
            headers: {'Accept': 'application/json'},
            credentials: 'same-origin',
        });
        const answer = await response.json();
        if (!response.ok || answer.error) {
            statusLine.textContent = answer.error || page.dataset.textError;
        } else {
            playersLine.textContent = answer.white + ' – ' + answer.black;
            statusLine.textContent = '';
            replay.updateLive(answer.positions, SPREAD_MILLISECONDS);
        }
    } catch (error) {
        statusLine.textContent = page.dataset.textError;
    }
}

// --- Asking again and again, only while the page is seen ---

function startAsking() {
    stopAsking();
    showGameNow();
    timer = setInterval(showGameNow, POLL_SECONDS * 1000);
}

function stopAsking() {
    if (timer !== null) {
        clearInterval(timer);
        timer = null;
    }
}

page.querySelectorAll('input[name="channel"]').forEach((input) => {
    input.addEventListener('change', () => {
        // The address shows the chosen channel, so a reload or a shared link keeps it.
        const address = new URL(window.location.href);
        address.searchParams.set('channel', input.value);
        window.history.replaceState(null, '', address);
        startAsking();
    });
});

document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
        stopAsking();
    } else {
        startAsking();
    }
});

startAsking();
