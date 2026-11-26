/**
 * The puzzle trainer in the browser (C5-1, L-01, A76).
 *
 * templates/puzzles/trainer.php draws the page; this script runs it:
 *
 *   1. "Start" or "Next" asks the server for a puzzle that fits the chosen
 *      motif and level (POST /learn/puzzles/next).
 *   2. A puzzle is a position and a list of moves (UCI). The first move is
 *      the opponent's: it is played after a short pause, then it is the
 *      player's turn.
 *   3. The player moves by dragging or tapping (chess.js checks the move).
 *      Right (the next move of the list, or any mate): the opponent's
 *      reply is played and the player goes on, until the list ends.
 *      Wrong: the move is taken back and the player may try again.
 *   4. "Show me" plays the rest of the solution, one move at a time.
 *
 * The daily puzzle page (A96) puts one puzzle on the page (data-daily-puzzle):
 * then it starts at once, and the choices and "Next" are hidden.
 *
 * The same rules as the strength test (account/strength-test.js), without
 * the scoring: a puzzle here is practice.
 *
 * Libraries (local, A16): cm-chessboard 8.15.8 and chess.js 1.4.0 in /assets/vendor/.
 */

import {Chessboard, COLOR, INPUT_EVENT_TYPE} from '../../vendor/cm-chessboard/src/Chessboard.js';
import {Markers, MARKER_TYPE} from '../../vendor/cm-chessboard/src/extensions/markers/Markers.js';
import {PromotionDialog, PROMOTION_DIALOG_RESULT_TYPE} from '../../vendor/cm-chessboard/src/extensions/promotion-dialog/PromotionDialog.js';
import {Chess} from '../../vendor/chess.js/chess.js';

// --- Reading what the server put on the page ---

const page = document.getElementById('puzzle-trainer');
const basePath = page.dataset.basePath;
const csrfToken = page.dataset.csrfToken;

const startButton = document.getElementById('puzzle-start');
const nextButton = document.getElementById('puzzle-next');
const hintButton = document.getElementById('puzzle-hint');
const lichessLink = document.getElementById('puzzle-on-lichess');
const runningSection = document.getElementById('puzzle-running');
const statusLine = document.getElementById('puzzle-status');
const boardFrame = document.getElementById('puzzle-board');

/** Pause before the opponent moves, in milliseconds, so the player can follow. */
const PAUSE_BEFORE_OPPONENT_MOVE = 700;

// --- The state of the current puzzle ---

let board = null;
let puzzle = null;       // {id, fen, moves, rating}
let game = null;         // chess.js, the position on the board
let nextMoveIndex = 0;   // which move of puzzle.moves comes next
let playerColour = 'w';
let isFinished = false;

startButton.addEventListener('click', askForPuzzle);
nextButton.addEventListener('click', askForPuzzle);
hintButton.addEventListener('click', showSolution);

// --- Talking to the server ---

async function askForPuzzle() {
    const motif = page.querySelector('input[name="motif"]:checked').value;
    const level = page.querySelector('input[name="level"]:checked').value;
    const body = new URLSearchParams({_csrf: csrfToken, motif, level});
    startButton.disabled = true;
    nextButton.disabled = true;
    try {
        const response = await fetch(basePath + '/learn/puzzles/next', {
            method: 'POST',
            headers: {'Accept': 'application/json'},
            credentials: 'same-origin',
            body,
        });
        const answer = await response.json();
        if (!response.ok || answer.error) {
            statusLine.textContent = answer.error || page.dataset.textError;
            runningSection.hidden = false;
        } else {
            showPuzzle(answer.puzzle);
        }
    } catch (error) {
        statusLine.textContent = page.dataset.textError;
        runningSection.hidden = false;
    }
    startButton.disabled = false;
    nextButton.disabled = false;
}

// --- Showing a puzzle ---

function showPuzzle(newPuzzle) {
    puzzle = newPuzzle;
    game = new Chess(puzzle.fen);
    nextMoveIndex = 0;
    isFinished = false;

    // The player is the side that moves after the opponent's first move.
    playerColour = game.turn() === 'w' ? 'b' : 'w';

    runningSection.hidden = false;
    statusLine.textContent = page.dataset.textRating.replace('{rating}', puzzle.rating);
    lichessLink.href = page.dataset.lichessTraining + encodeURIComponent(puzzle.id);
    lichessLink.hidden = true;

    if (board === null) {
        board = new Chessboard(boardFrame, {
            position: puzzle.fen,
            assetsUrl: basePath + '/assets/vendor/cm-chessboard/assets/',
            style: {cssClass: 'phpchess', showCoordinates: true, animationDuration: 300},
            extensions: [{class: Markers, props: {autoMarkers: null}}, {class: PromotionDialog}],
        });
        keepDraggingWithinTheSecurityPolicy(board);
    }
    board.disableMoveInput();
    board.removeMarkers();
    board.setOrientation(playerColour === 'w' ? COLOR.white : COLOR.black);
    board.setPosition(puzzle.fen, false);

    setTimeout(playOpponentMove, PAUSE_BEFORE_OPPONENT_MOVE);
}

/** Plays the next move of the list for the opponent, then it is the player's turn. */
function playOpponentMove() {
    playListedMove();
    statusLine.textContent = page.dataset.textYourMove;
    board.enableMoveInput(handleInput, playerColour === 'w' ? COLOR.white : COLOR.black);
}

/** Plays the next move of the list on the board and marks its squares. */
function playListedMove() {
    const uci = puzzle.moves[nextMoveIndex];
    game.move(uciToMove(uci));
    nextMoveIndex++;
    board.setPosition(game.fen(), true);
    board.removeMarkers();
    board.addMarker(MARKER_TYPE.square, uci.substring(0, 2));
    board.addMarker(MARKER_TYPE.square, uci.substring(2, 4));
}

// --- The player's move ---

function handleInput(event) {
    switch (event.type) {
        case INPUT_EVENT_TYPE.moveInputStarted: {
            const moves = game.moves({square: event.squareFrom, verbose: true});
            board.removeMarkers();
            board.addLegalMovesMarkers(moves);
            return moves.length > 0;
        }
        case INPUT_EVENT_TYPE.validateMoveInput: {
            board.removeMarkers();
            const candidates = game.moves({square: event.squareFrom, verbose: true}).filter((move) => move.to === event.squareTo);
            if (candidates.length === 0) {
                return false;
            }
            if (candidates[0].promotion) {
                askForPromotion(event);
                return true;
            }
            checkPlayerMove(event.squareFrom + event.squareTo);
            return true;
        }
        case INPUT_EVENT_TYPE.moveInputCanceled:
            board.removeMarkers();
            return true;
        default:
            return true;
    }
}

function askForPromotion(event) {
    board.showPromotionDialog(event.squareTo, playerColour === 'w' ? COLOR.white : COLOR.black, (result) => {
        if (result.type !== PROMOTION_DIALOG_RESULT_TYPE.pieceSelected) {
            board.setPosition(game.fen(), true);
            return;
        }
        checkPlayerMove(event.squareFrom + event.squareTo + result.piece.charAt(1));
    });
}

/** Right move: go on or finish. Wrong move: take it back, try again. */
function checkPlayerMove(uci) {
    const expected = puzzle.moves[nextMoveIndex];
    game.move(uciToMove(uci));
    board.setPosition(game.fen(), true);

    // Any mate is right too, even when the list has another one.
    const isRight = uci === expected || game.isCheckmate();
    if (!isRight) {
        game.undo();
        setTimeout(() => board.setPosition(game.fen(), true), 300);
        statusLine.textContent = page.dataset.textWrong;
        return;
    }

    nextMoveIndex++;
    if (nextMoveIndex >= puzzle.moves.length || game.isCheckmate()) {
        finish(page.dataset.textSolved);
        return;
    }
    board.disableMoveInput();
    setTimeout(playOpponentMove, PAUSE_BEFORE_OPPONENT_MOVE);
}

// --- "Show me" and the end ---

/** Plays the rest of the solution, one move at a time. */
function showSolution() {
    if (puzzle === null || isFinished) {
        return;
    }
    board.disableMoveInput();
    const playRest = () => {
        if (nextMoveIndex >= puzzle.moves.length) {
            finish('');
            return;
        }
        playListedMove();
        setTimeout(playRest, PAUSE_BEFORE_OPPONENT_MOVE);
    };
    playRest();
}

function finish(message) {
    isFinished = true;
    board.disableMoveInput();
    if (message !== '') {
        statusLine.textContent = message;
    }
    lichessLink.hidden = false;
    nextButton.focus();
}

// --- Helpers ---

/** "e7e8q" → {from: 'e7', to: 'e8', promotion: 'q'} for chess.js. */
function uciToMove(uci) {
    const move = {from: uci.substring(0, 2), to: uci.substring(2, 4)};
    if (uci.length > 4) {
        move.promotion = uci.charAt(4);
    }
    return move;
}

/**
 * cm-chessboard moves the dragged piece by writing a "style" attribute,
 * which the site's Content-Security-Policy blocks; element.style is allowed
 * (the same fix as in board/game-page.js).
 */
function keepDraggingWithinTheSecurityPolicy(chessboard) {
    const moveInput = chessboard.view.visualMoveInput;
    moveInput.moveDraggablePiece = function (x, y) {
        const halfSquare = this.view.squareHeight / 2;
        this.draggablePiece.style.left = (x - halfSquare) + 'px';
        this.draggablePiece.style.top = (y - halfSquare) + 'px';
    };
}

// --- The daily puzzle: one puzzle given by the page, started at once (A96) ---

if (page.dataset.dailyPuzzle) {
    page.querySelectorAll('.choice-group').forEach((group) => { group.hidden = true; });
    startButton.hidden = true;
    nextButton.hidden = true;
    showPuzzle(JSON.parse(page.dataset.dailyPuzzle));
}
