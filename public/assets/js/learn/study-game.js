/**
 * The study game viewer (C5-1, L-03): study games, own games and
 * broadcast games.
 *
 * templates/learn/game.php puts the game on the page as a list of
 * positions (data-positions): the start, then one per move, each with the
 * move that led there, its two squares and its comment (plain text,
 * checked by the server's content scanner). The shared replay (replay.js)
 * shows them: board with the last move marked, move table and the seven
 * controls (B44, B45, B47).
 *
 * The server read and checked every move already (PgnReader), so no chess
 * library is needed here, only the board.
 */

import {Replay} from './replay.js';

// --- The replay ---

const page = document.getElementById('study-game');
new Replay(document.getElementById('study-game-replay'), JSON.parse(page.dataset.positions));

// --- Copy the PGN out, to take the game to another chess program (B37) ---

const copyButton = document.getElementById('study-game-copy');
copyButton.addEventListener('click', () => {
    const pgnText = document.getElementById('study-game-pgn').textContent;
    navigator.clipboard.writeText(pgnText).then(() => {
        copyButton.textContent = copyButton.dataset.copied;
    });
});
