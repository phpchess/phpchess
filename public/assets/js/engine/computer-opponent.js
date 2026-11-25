/**
 * The computer opponent on the game page (C3-2: AI-01, AI-04, K1).
 *
 * templates/play/game.php loads this script only when the viewer plays a
 * computer player. Whenever it is the computer's turn, the script asks the
 * engine worker (computer-player.worker.js) for a move, then sends that move
 * to POST /games/{id}/computer-move. The server checks it with the same move
 * function as every other move (K1) and answers with the game's new state,
 * which this script hands to game-page.js (event "game-news") to show.
 *
 * game-page.js tells this script about every new state with the event
 * "game-state". The move is the same on every device: it depends only on the
 * position, the player's settings, the game id and the move number.
 *
 * Page data used: data-state, data-base-path, data-csrf-token,
 * data-computer-colour, data-computer-engine (the player's engine block),
 * data-computer-depth-cap (the admin's cap, A49), data-computer-delay (the
 * member's choice: none, short, normal, long; B30), data-text-computer-thinking,
 * data-text-computer-failed.
 */

// --- Reading what the server put on the page ---

const page = document.getElementById('game-page');
const basePath = page.dataset.basePath;
const csrfToken = page.dataset.csrfToken;
const computerColour = page.dataset.computerColour;
const engineBlock = JSON.parse(page.dataset.computerEngine);
const depthCap = parseInt(page.dataset.computerDepthCap, 10);

// The normal start position: only games from here use the opening book.
const START_FEN = 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1';

// How long the computer waits before its move appears (B30, A65), chosen by
// the member in their preferences: an average and how far it may vary
// either way, in milliseconds. "normal" is about 5 seconds, 1 to 9 at random,
// so the computer does not answer unnaturally fast or always alike.
// Even "none" waits a moment, so the move never appears in the same instant
// as the player's own (easier to follow on a phone).
const DELAYS = {
    none: {average: 600, vary: 0},
    short: {average: 2000, vary: 1000},
    normal: {average: 5000, vary: 4000},
    long: {average: 10000, vary: 5000},
};
const delay = DELAYS[page.dataset.computerDelay] || DELAYS.normal;

/** A waiting time for this move: the average, plus or minus up to "vary". */
function thinkingTime() {
    return delay.average + (Math.random() * 2 - 1) * delay.vary;
}

const worker = new Worker(basePath + '/assets/js/engine/computer-player.worker.js');

// The ply the computer is working on, so one turn is never played twice.
let decidingPly = null;

// --- Status line ---

function showStatus(text) {
    document.getElementById('game-status').textContent = text;
}

// --- The computer's turn ---

/** Starts the computer's move when the game is running and it is its turn. */
function takeTurnIfNeeded(state) {
    if (state.state !== 'running' || state.side_to_move !== computerColour || decidingPly === state.ply) {
        return;
    }
    decidingPly = state.ply;
    showStatus(page.dataset.textComputerThinking);

    const movesSoFar = state.start_fen === START_FEN ? state.moves.map((move) => move.uci) : null;
    const startedAt = Date.now();
    worker.onmessage = (event) => {
        const answer = event.data;
        if (answer.type !== 'decision') {
            showStatus(page.dataset.textComputerFailed);
            return;
        }
        // The engine's own time counts towards the wait.
        const wait = Math.max(0, thinkingTime() - (Date.now() - startedAt));
        window.setTimeout(() => sendComputerMove(state.id, state.ply, answer.move), wait);
    };
    worker.postMessage({
        type: 'decide',
        fen: state.fen,
        engineBlock: engineBlock,
        depthCap: depthCap,
        gameId: String(state.id),
        ply: state.ply,
        movesSoFar: movesSoFar,
    });
}

/** Sends the computer's move to the server and hands the answer to the board. */
async function sendComputerMove(gameId, ply, uci) {
    const body = new URLSearchParams({_csrf: csrfToken, ply: String(ply), move: uci});
    try {
        const response = await fetch(basePath + '/games/' + gameId + '/computer-move', {
            method: 'POST',
            headers: {'Accept': 'application/json'},
            body: body,
            credentials: 'same-origin',
        });
        const answer = await response.json();
        if (!response.ok) {
            // Someone else's answer got there first (another tab): the next news shows it.
            decidingPly = null;
            return;
        }
        page.dispatchEvent(new CustomEvent('game-news', {detail: answer}));
    } catch (error) {
        // No connection: try again with the next news from the server.
        decidingPly = null;
        showStatus(page.dataset.textComputerFailed);
    }
}

// --- Start ---

page.addEventListener('game-state', (event) => takeTurnIfNeeded(event.detail));
takeTurnIfNeeded(JSON.parse(page.dataset.state));
