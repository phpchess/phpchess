/**
 * Lichess TV on our own board (C5-1, L-01, A75): no iframe.
 *
 * templates/learn/tv.php draws the page; this script asks our own server
 * (GET /learn/tv/now?channel=...) every POLL_SECONDS for the game now on
 * the chosen channel, and shows the players and the position. The browser
 * never talks to lichess.org itself (K9): our server does, and shares one
 * answer between all viewers.
 *
 * The page stops asking while it is hidden (another tab, a locked phone).
 *
 * Library (local, A16): cm-chessboard 8.15.8 in /assets/vendor/.
 */

import {Chessboard} from '../../vendor/cm-chessboard/src/Chessboard.js';

// --- Reading what the server put on the page ---

const page = document.getElementById('lichess-tv');
const basePath = page.dataset.basePath;
const playersLine = document.getElementById('lichess-tv-players');
const statusLine = document.getElementById('lichess-tv-status');

/** How often to ask, in seconds. Our server asks Lichess at most every 10 seconds. */
const POLL_SECONDS = 5;

const board = new Chessboard(document.getElementById('lichess-tv-board'), {
    position: 'start',
    assetsUrl: basePath + '/assets/vendor/cm-chessboard/assets/',
    style: {cssClass: 'phpchess', showCoordinates: false, animationDuration: 300},
});

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
            statusLine.textContent = answer.lastMove;
            board.setPosition(answer.fen, true);
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
    input.addEventListener('change', startAsking);
});

document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
        stopAsking();
    } else {
        startAsking();
    }
});

startAsking();
