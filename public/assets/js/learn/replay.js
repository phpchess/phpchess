/**
 * The game replay, shared by every board that replays a game (B44, B45,
 * B47, B48): study games, own games, broadcast games and Lichess TV.
 *
 * templates/partials/replay.php draws the parts; this module runs them:
 *   - the board (cm-chessboard, pieces can't be moved), with the two
 *     squares of the last move marked (B47);
 *   - the move table: move number, White, Black; a tap on a move jumps
 *     there, and the current move is marked (B44);
 *   - the seven controls (B45):
 *       |<<  start         <<  back one move    <  play backwards
 *        =   stop          >   play forward     >>  forward one move
 *       >>|  end (on Lichess TV: back to the live game)
 *   - the keys: left and right arrow (one move), Home and End, space (stop).
 *
 * A "position" is what the server sends for every move (LearnController::
 * positionsOf): {fen, san, from, to, comment}. Number 0 is the start.
 *
 * Live games (Lichess TV, B48): updateLive() takes the whole game again
 * each time. New moves are not jumped to; they are played one after the
 * other, spread over the time until the next update, so it looks live.
 * Stepping back stops following the live game; >>| follows it again.
 *
 * Comments are only ever put in as text, never as HTML.
 *
 * Library (local, A16): cm-chessboard 8.15.8 in /assets/vendor/.
 */

import {Chessboard} from '../../vendor/cm-chessboard/src/Chessboard.js';
import {Markers, MARKER_TYPE} from '../../vendor/cm-chessboard/src/extensions/markers/Markers.js';

/** How long one move stays on the board while playing forward or backwards. */
const AUTO_PLAY_MILLISECONDS = 1000;

export class Replay {

    /**
     * @param {HTMLElement} root       the element drawn by partials/replay.php
     * @param {Array} positions        the start and one position per move
     * @param {boolean} isLive         true on Lichess TV: >>| follows the live game
     */
    constructor(root, positions, isLive = false) {
        this.root = root;
        this.positions = positions;
        this.isLive = isLive;
        this.current = 0;          // which position is on the board
        this.timer = null;         // the timer while playing
        this.isFollowing = isLive; // live only: new moves are played as they come

        this.tableBody = root.querySelector('[data-replay-moves]');
        this.tableBox = root.querySelector('.replay-table-box');
        this.commentBox = root.querySelector('[data-replay-comment]');
        this.board = new Chessboard(root.querySelector('[data-replay-board]'), {
            position: positions[0].fen,
            assetsUrl: root.dataset.basePath + '/assets/vendor/cm-chessboard/assets/',
            style: {cssClass: 'phpchess', showCoordinates: true, animationDuration: 200},
            extensions: [{class: Markers, props: {autoMarkers: null}}],
        });

        this.drawTable();
        this.connectControls();
        this.show(0, false);
    }

    // --- Showing one position ---

    /** Puts position number `index` on the board, with its marks, comment and table mark. */
    show(index, animate = true) {
        this.current = Math.max(0, Math.min(this.positions.length - 1, index));
        const position = this.positions[this.current];

        this.board.setPosition(position.fen, animate);
        this.board.removeMarkers();
        if (position.from && position.to) {
            this.board.addMarker(MARKER_TYPE.square, position.from);
            this.board.addMarker(MARKER_TYPE.square, position.to);
        }
        if (this.commentBox) {
            this.commentBox.textContent = position.comment || '';
        }

        this.tableBody.querySelectorAll('button').forEach((button) => {
            const isCurrent = Number(button.dataset.index) === this.current;
            button.classList.toggle('is-current', isCurrent);
            if (isCurrent) {
                this.scrollTableTo(button.closest('tr'));
            }
        });
    }

    /**
     * Scrolls the move table, and only the table, so `row` is in view.
     * (scrollIntoView() would scroll the whole page too and push the
     * board out of sight.)
     */
    scrollTableTo(row) {
        const box = this.tableBox;
        const rowTop = row.offsetTop;
        const rowBottom = rowTop + row.offsetHeight;
        const headerHeight = box.querySelector('thead').offsetHeight;
        if (rowTop - headerHeight < box.scrollTop || rowBottom > box.scrollTop + box.clientHeight) {
            box.scrollTop = rowTop - box.clientHeight / 2;
        }
    }

    // --- The move table ---

    /** Draws the whole table again: one row per move number. */
    drawTable() {
        this.tableBody.textContent = '';
        for (let index = 1; index < this.positions.length; index++) {
            this.addToTable(index);
        }
    }

    /** Adds position `index` (a move) to the table, in a new row or next to White's move. */
    addToTable(index) {
        // Who moved: the side to move before this move, read from the position before.
        const whiteMoved = this.positions[index - 1].fen.split(' ')[1] === 'w';
        const moveNumber = Number(this.positions[index - 1].fen.split(' ')[5]);

        let row = this.tableBody.lastElementChild;
        if (whiteMoved || row === null) {
            row = document.createElement('tr');
            const numberCell = document.createElement('th');
            numberCell.scope = 'row';
            numberCell.textContent = moveNumber + '.';
            row.appendChild(numberCell);
            row.appendChild(document.createElement('td'));
            row.appendChild(document.createElement('td'));
            this.tableBody.appendChild(row);
        }

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'replay-move';
        button.dataset.index = String(index);
        button.textContent = this.positions[index].san;
        if (this.positions[index].comment) {
            button.classList.add('has-comment');
        }
        button.addEventListener('click', () => {
            this.stop();
            this.isFollowing = false;
            this.show(index);
        });
        row.children[whiteMoved ? 1 : 2].appendChild(button);
    }

    // --- The controls ---

    connectControls() {
        const actions = {
            'first': () => this.show(0),
            'back': () => this.show(this.current - 1),
            'play-back': () => this.play(-1),
            'stop': () => {},
            'play': () => this.play(1),
            'forward': () => this.show(this.current + 1),
            'last': () => this.show(this.positions.length - 1),
        };

        this.root.querySelectorAll('[data-replay]').forEach((button) => {
            button.addEventListener('click', () => this.control(button.dataset.replay, actions));
        });

        document.addEventListener('keydown', (event) => {
            const keys = {ArrowLeft: 'back', ArrowRight: 'forward', Home: 'first', End: 'last', ' ': 'stop'};
            if (keys[event.key] && !event.target.closest('input, textarea, select')) {
                event.preventDefault();
                this.control(keys[event.key], actions);
            }
        });
    }

    /** Runs one control. Every control stops the playing first; only >>| follows a live game. */
    control(name, actions) {
        this.stop();
        this.isFollowing = this.isLive && name === 'last';
        actions[name]();
    }

    /** Plays forward (direction 1) or backwards (-1), one move a second, until the end or the start. */
    play(direction) {
        this.stepTowards(direction === 1 ? this.positions.length - 1 : 0, AUTO_PLAY_MILLISECONDS);
    }

    /** Steps one move at a time towards position `target`, one step every `milliseconds`. */
    stepTowards(target, milliseconds) {
        this.stop();
        this.timer = setInterval(() => {
            if (this.current === target) {
                this.stop();
                return;
            }
            this.show(this.current + (target > this.current ? 1 : -1));
        }, milliseconds);
    }

    stop() {
        if (this.timer !== null) {
            clearInterval(this.timer);
            this.timer = null;
        }
    }

    // --- Live games (B48) ---

    /**
     * Takes the whole game again, as the server sent it.
     *
     * @param {Array} positions        the start and one position per move, so far
     * @param {number} spreadMilliseconds  the time until the next update: new moves are spread over it
     */
    updateLive(positions, spreadMilliseconds) {
        const known = this.positions.length;
        const isSameGame = positions.length >= known && positions[known - 1].fen === this.positions[known - 1].fen
            && positions[0].fen === this.positions[0].fen;

        // Another game on the channel: start again, at its live position.
        if (!isSameGame) {
            this.stop();
            this.positions = positions;
            this.drawTable();
            this.isFollowing = true;
            this.show(positions.length - 1, false);
            return;
        }

        // The same game: add the new moves to the table.
        this.positions = positions;
        for (let index = known; index < positions.length; index++) {
            this.addToTable(index);
        }

        // Play the moves still to come one after the other, spread evenly
        // over the time until the next update (4 moves in 10 s: one every 2.5 s).
        const stillToCome = positions.length - 1 - this.current;
        if (this.isFollowing && stillToCome > 0) {
            this.stepTowards(positions.length - 1, Math.max(200, spreadMilliseconds / stillToCome));
        }
    }
}
