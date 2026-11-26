/**
 * The study game viewer (C5-1, L-03).
 *
 * templates/learn/game.php puts the game on the page as a list of
 * positions (data-positions): the start, then one per move, each with the
 * move that led there and its comment (plain text, checked by the server's
 * content scanner, and still only ever put in as text, never as HTML).
 * This script shows them on a board you can't move
 * pieces on, and walks through them with:
 *   - the four buttons: start, back, forward, end;
 *   - the arrow keys left and right;
 *   - a tap on a move in the move list.
 *
 * The server read and checked every move already (PgnReader), so no chess
 * library is needed here, only the board.
 *
 * Library (local, A16): cm-chessboard 8.15.8 in /assets/vendor/.
 */

import {Chessboard} from '../../vendor/cm-chessboard/src/Chessboard.js';

// --- Reading what the server put on the page ---

const page = document.getElementById('study-game');
const basePath = page.dataset.basePath;
const positions = JSON.parse(page.dataset.positions);
const moveList = document.getElementById('study-game-moves');
const commentBox = document.getElementById('study-game-comment');

let current = 0; // which position is on the board: 0 is the start

const board = new Chessboard(document.getElementById('study-game-board'), {
    position: positions[0].fen,
    assetsUrl: basePath + '/assets/vendor/cm-chessboard/assets/',
    style: {cssClass: 'phpchess', showCoordinates: true, animationDuration: 200},
});

// --- The move list: one button per move, numbered like a score sheet ---

const moveButtons = [];
positions.forEach((position, index) => {
    if (index === 0) {
        return;
    }
    const item = document.createElement('li');
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'study-game-move';
    button.textContent = position.san;
    if (position.comment) {
        button.classList.add('has-comment');
    }
    button.addEventListener('click', () => show(index));
    item.appendChild(button);
    moveList.appendChild(item);
    moveButtons[index] = button;
});

// The comment before the first move, if the game has one.
commentBox.textContent = positions[0].comment || '';

// --- Walking through the game ---

function show(index) {
    current = Math.max(0, Math.min(positions.length - 1, index));
    board.setPosition(positions[current].fen, true);
    commentBox.textContent = positions[current].comment || '';
    moveButtons.forEach((button, buttonIndex) => {
        if (button) {
            button.classList.toggle('is-current', buttonIndex === current);
        }
    });
}

const steps = {
    first: () => show(0),
    previous: () => show(current - 1),
    next: () => show(current + 1),
    last: () => show(positions.length - 1),
};

page.querySelectorAll('[data-step]').forEach((button) => {
    button.addEventListener('click', () => steps[button.dataset.step]());
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'ArrowLeft') {
        steps.previous();
    } else if (event.key === 'ArrowRight') {
        steps.next();
    }
});

// --- Copy the PGN out, to take the game to another chess program (B37) ---

const copyButton = document.getElementById('study-game-copy');
copyButton.addEventListener('click', () => {
    const pgnText = document.getElementById('study-game-pgn').textContent;
    navigator.clipboard.writeText(pgnText).then(() => {
        copyButton.textContent = copyButton.dataset.copied;
    });
});
