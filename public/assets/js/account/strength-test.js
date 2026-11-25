/**
 * The strength test in the browser (B15, A60).
 *
 * templates/account/strength_test.php draws the page; this script runs the test:
 *
 *   1. "Start" asks the server for the first puzzle (POST /strength-test/start).
 *   2. A puzzle is a position and a list of moves (UCI). The first move is
 *      the opponent's: it is played on the board after a short pause, and
 *      then it is the player's turn.
 *   3. The player moves by dragging or tapping (chess.js checks the move).
 *      The move is right when it is the next move of the list, or any move
 *      that gives mate. Right: the opponent's reply is played and the player
 *      goes on, until the list ends ("solved"). Wrong: the right move is
 *      shown for a moment ("missed").
 *   4. The answer goes to POST /strength-test/answer; the server sends the
 *      next puzzle, or after 10 puzzles the result.
 *
 * There is no clock: the player takes as long as they like.
 *
 * Libraries (local, A16): cm-chessboard 8.15.8 and chess.js 1.4.0 in /assets/vendor/.
 */

import {Chessboard, COLOR, INPUT_EVENT_TYPE} from '../../vendor/cm-chessboard/src/Chessboard.js';
import {Markers, MARKER_TYPE} from '../../vendor/cm-chessboard/src/extensions/markers/Markers.js';
import {PromotionDialog, PROMOTION_DIALOG_RESULT_TYPE} from '../../vendor/cm-chessboard/src/extensions/promotion-dialog/PromotionDialog.js';
import {Chess} from '../../vendor/chess.js/chess.js';

// --- Reading what the server put on the page ---

const page = document.getElementById('strength-test');
const basePath = page.dataset.basePath;
const csrfToken = page.dataset.csrfToken;

const startButton = document.getElementById('strength-test-start');
const introSection = document.getElementById('strength-test-intro');
const runningSection = document.getElementById('strength-test-running');
const doneSection = document.getElementById('strength-test-done');
const statusLine = document.getElementById('strength-test-status');
const resultLine = document.getElementById('strength-test-result');
const boardFrame = document.getElementById('strength-test-board');

/** Pauses, in milliseconds, so the player can follow the moves. */
const PAUSE_BEFORE_OPPONENT_MOVE = 700;
const PAUSE_AFTER_ANSWER = 1500;

// --- The state of the current puzzle ---

let board = null;
let puzzle = null;       // {id, fen, moves}
let game = null;         // chess.js, the position on the board
let nextMoveIndex = 0;   // which move of puzzle.moves comes next
let playerColour = 'w';
let counterText = '';    // "Puzzle 3 of 10"

if (startButton) {
    startButton.addEventListener('click', () => {
        startButton.disabled = true;
        askServer('/strength-test/start', {});
    });
}

// --- Talking to the server ---

async function askServer(path, values) {
    const body = new URLSearchParams({_csrf: csrfToken, ...values});
    try {
        const response = await fetch(basePath + path, {
            method: 'POST',
            headers: {'Accept': 'application/json'},
            credentials: 'same-origin',
            body,
        });
        const answer = await response.json();
        if (!response.ok || answer.error) {
            showError(answer.error || page.dataset.textError);
        } else if (answer.done) {
            showResult(answer);
        } else {
            counterText = page.dataset.textNumber.replace('{number}', answer.number).replace('{total}', answer.total);
            showPuzzle(answer.puzzle);
        }
    } catch (error) {
        showError(page.dataset.textError);
    }
}

function sendAnswer(solved) {
    board.disableMoveInput();
    statusLine.textContent = counterText + ': ' + (solved ? page.dataset.textSolved : page.dataset.textMissed);
    setTimeout(() => askServer('/strength-test/answer', {puzzle_id: puzzle.id, solved: solved ? 'yes' : 'no'}), PAUSE_AFTER_ANSWER);
}

// --- Showing a puzzle ---

function showPuzzle(newPuzzle) {
    puzzle = newPuzzle;
    game = new Chess(puzzle.fen);
    nextMoveIndex = 0;

    // The player is the side that moves after the opponent's first move.
    playerColour = game.turn() === 'w' ? 'b' : 'w';

    introSection.hidden = true;
    runningSection.hidden = false;
    statusLine.textContent = counterText;

    if (board === null) {
        board = new Chessboard(boardFrame, {
            position: puzzle.fen,
            assetsUrl: basePath + '/assets/vendor/cm-chessboard/assets/',
            style: {cssClass: 'phpchess', showCoordinates: true, animationDuration: 300},
            extensions: [{class: Markers, props: {autoMarkers: null}}, {class: PromotionDialog}],
        });
        keepDraggingWithinTheSecurityPolicy(board);
    }
    board.removeMarkers();
    board.setOrientation(playerColour === 'w' ? COLOR.white : COLOR.black);
    board.setPosition(puzzle.fen, false);

    setTimeout(playOpponentMove, PAUSE_BEFORE_OPPONENT_MOVE);
}

/** Plays the next move of the list for the opponent, then it is the player's turn. */
function playOpponentMove() {
    const uci = puzzle.moves[nextMoveIndex];
    game.move(uciToMove(uci));
    nextMoveIndex++;
    board.setPosition(game.fen(), true);
    board.removeMarkers();
    board.addMarker(MARKER_TYPE.square, uci.substring(0, 2));
    board.addMarker(MARKER_TYPE.square, uci.substring(2, 4));

    statusLine.textContent = counterText + ': ' + page.dataset.textYourMove;
    board.enableMoveInput(handleInput, playerColour === 'w' ? COLOR.white : COLOR.black);
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

/** Right move: go on or finish. Wrong move: show the right one, then "missed". */
function checkPlayerMove(uci) {
    const expected = puzzle.moves[nextMoveIndex];
    game.move(uciToMove(uci));
    board.setPosition(game.fen(), true);

    // Any mate is right too, even when the list has another one.
    const isRight = uci === expected || game.isCheckmate();
    if (!isRight) {
        game.undo();
        board.setPosition(game.fen(), true);
        board.addMarker(MARKER_TYPE.frame, expected.substring(0, 2));
        board.addMarker(MARKER_TYPE.frame, expected.substring(2, 4));
        sendAnswer(false);
        return;
    }

    nextMoveIndex++;
    if (nextMoveIndex >= puzzle.moves.length || game.isCheckmate()) {
        sendAnswer(true);
        return;
    }
    board.disableMoveInput();
    setTimeout(playOpponentMove, PAUSE_BEFORE_OPPONENT_MOVE);
}

// --- The end ---

function showResult(answer) {
    runningSection.hidden = true;
    doneSection.hidden = false;
    resultLine.textContent = page.dataset.textResult
        .replace('{solved}', answer.solved)
        .replace('{band}', answer.band)
        .replace('{rating}', answer.rating);
}

function showError(message) {
    statusLine.textContent = message;
    introSection.hidden = false;
    if (startButton) {
        startButton.disabled = false;
    }
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
