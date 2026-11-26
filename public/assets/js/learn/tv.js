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
 * When the game ends (it has a result, or the channel moved on to another
 * game), the page does not jump away: it asks once for the game it was
 * watching (?game=<id>), plays its last moves, and asks in a box over the
 * board "Game ended: White won. Next game?".
 *   Yes: the channel's new game, live.
 *   No:  stay on the finished game, with all the replay controls; the
 *        "Next game" button under the board goes on later.
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
const endBox = document.getElementById('lichess-tv-ended');
const endText = document.getElementById('lichess-tv-ended-text');
const nextGameButton = document.getElementById('lichess-tv-next');

/** How often to ask, in seconds. */
const POLL_SECONDS = 5;

/** Our server asks Lichess at most every 10 seconds, so new moves are spread over that time. */
const SPREAD_MILLISECONDS = 10000;

/** The last moves of a finished game are played faster: one every this many milliseconds. */
const LAST_MOVES_MILLISECONDS = 600;

// The board starts with the start position (cm-chessboard has no 'start' word: use FEN.start).
const replay = new Replay(document.getElementById('lichess-tv-replay'), [{fen: FEN.start, san: '', from: '', to: '', comment: ''}], true);

let timer = null;       // the timer that asks again and again
let watchingId = '';    // the Lichess id of the game on our board
let hasEnded = false;   // true from the end of a game until the player picks the next one

// --- Asking our server ---

function channel() {
    return page.querySelector('input[name="channel"]:checked').value;
}

/** Asks our server; gives the answer, or null after showing what went wrong. */
async function ask(extra) {
    try {
        const response = await fetch(basePath + '/learn/tv/now?channel=' + encodeURIComponent(channel()) + extra, {
            headers: {'Accept': 'application/json'},
            credentials: 'same-origin',
        });
        const answer = await response.json();
        if (!response.ok || answer.error) {
            statusLine.textContent = answer.error || page.dataset.textError;
            return null;
        }
        statusLine.textContent = '';
        return answer;
    } catch (error) {
        statusLine.textContent = page.dataset.textError;
        return null;
    }
}

async function showGameNow() {
    if (hasEnded) {
        return;
    }
    const answer = await ask('');
    if (answer === null || hasEnded) {
        return;
    }

    // The channel moved on: the game we were watching is over.
    if (watchingId !== '' && answer.gameId !== watchingId) {
        const finished = await ask('&game=' + encodeURIComponent(watchingId));
        if (finished !== null) {
            showEnd(finished);
            return;
        }
    }

    watchingId = answer.gameId;
    playersLine.textContent = answer.white + ' – ' + answer.black;
    replay.updateLive(answer.positions, SPREAD_MILLISECONDS);

    // Still on the channel, but already decided.
    if (answer.result !== '*') {
        showEnd(answer);
    }
}

// --- The end of a game ---

/** Plays the game's last moves, then asks "Next game?" in the box over the board. */
function showEnd(game) {
    hasEnded = true;
    stopAsking();
    replay.isFollowing = true; // the last moves are played even after stepping back
    replay.updateLive(game.positions, LAST_MOVES_MILLISECONDS * game.positions.length);
    replay.isLive = false; // from now on >>| is just "end of the game"

    const resultKey = {'1-0': 'textWhiteWon', '0-1': 'textBlackWon', '1/2-1/2': 'textDraw'}[game.result] || 'textEnded';
    endText.textContent = page.dataset[resultKey];

    // Wait until the last move is on the board, then ask.
    const waitForLastMove = setInterval(() => {
        if (replay.current === replay.positions.length - 1) {
            clearInterval(waitForLastMove);
            endBox.hidden = false;
            endBox.querySelector('[data-answer="yes"]').focus();
        }
    }, 250);
}

/** The channel's next game, live again. */
function goToNextGame() {
    endBox.hidden = true;
    nextGameButton.hidden = true;
    hasEnded = false;
    watchingId = '';
    replay.isLive = true;
    replay.isFollowing = true;
    startAsking();
}

endBox.querySelector('[data-answer="yes"]').addEventListener('click', goToNextGame);
endBox.querySelector('[data-answer="no"]').addEventListener('click', () => {
    // Stay on the finished game in replay mode; the next game is one button away.
    endBox.hidden = true;
    nextGameButton.hidden = false;
});
nextGameButton.addEventListener('click', goToNextGame);

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
        goToNextGame();
    });
});

document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
        stopAsking();
    } else if (!hasEnded) {
        startAsking();
    }
});

startAsking();
