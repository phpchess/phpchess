/**
 * The game page in the browser (G-04, G-10, A36): the drag-and-drop board,
 * live moves, replay, and the planning board.
 *
 * The page works without this script (templates/play/game.php draws a
 * table board and a move field). This script, loaded as a module, replaces
 * the table with cm-chessboard and adds:
 *   - moves by dragging or by tapping two squares, with the legal targets
 *     marked; a pawn reaching the last rank asks which piece it becomes;
 *   - instant feedback from chess.js, while the server decides (K1): each
 *     move is sent to POST /games/{id}/move, and on a refusal the board
 *     goes back to the server's position;
 *   - live updates: the page asks GET /games/{id}/state?since=N every few
 *     seconds (less often while the tab is hidden), see live-updates.js;
 *   - replay buttons and clickable moves to look back through the game;
 *   - the planning board: try moves for both sides; nothing is sent.
 *
 * Libraries (local, A16): cm-chessboard 8.15.8 and chess.js 1.4.0 in /assets/vendor/.
 */

import {Chessboard, COLOR, INPUT_EVENT_TYPE} from '../../vendor/cm-chessboard/src/Chessboard.js';
import {Markers} from '../../vendor/cm-chessboard/src/extensions/markers/Markers.js';
import {PromotionDialog, PROMOTION_DIALOG_RESULT_TYPE} from '../../vendor/cm-chessboard/src/extensions/promotion-dialog/PromotionDialog.js';
import {Chess} from '../../vendor/chess.js/chess.js';
import {startLiveUpdates} from './live-updates.js';

// --- Reading what the server put on the page ---

const page = document.getElementById('game-page');
const basePath = page.dataset.basePath;
const csrfToken = page.dataset.csrfToken;
const viewerColour = page.dataset.viewerColour;   // 'w', 'b' or '' for a watcher
let serverState = JSON.parse(page.dataset.state);

// Every position of the game as FEN, index = ply (0 = the start).
const positions = [];
// Every move as SAN, index = ply - 1.
const sanMoves = [];

let viewedPly = 0;          // the position shown (replay)
let isPlanning = false;     // the planning board is on
let planningGame = null;    // a chess.js game for planning
let board = null;

// --- Building the board ---

function setUpBoard() {
    rebuildPositions(serverState.moves);
    viewedPly = serverState.ply;

    const boardElement = document.getElementById('board');
    boardElement.innerHTML = '';
    board = new Chessboard(boardElement, {
        position: positions[viewedPly],
        orientation: viewerColour === 'b' ? COLOR.black : COLOR.white,
        assetsUrl: basePath + '/assets/vendor/cm-chessboard/assets/',
        style: {cssClass: 'phpchess', showCoordinates: true, animationDuration: 200},
        extensions: [{class: Markers, props: {autoMarkers: null}}, {class: PromotionDialog}],
    });

    keepDraggingWithinTheSecurityPolicy(board);
    document.getElementById('board-tools').hidden = false;
    hideTypedMoveFormWhileBoardWorks();
    updateMoveInput();
}

/**
 * cm-chessboard moves the dragged piece by writing a "style" attribute,
 * which the site's Content-Security-Policy (style-src 'self') blocks.
 * Setting the same values through element.style is allowed, so this
 * replaces that one method of the board's move input; the rest of the
 * vendored library stays unchanged. The fixed parts (pointer-events,
 * position) are in play.css under .cm-chessboard-draggable-piece.
 */
function keepDraggingWithinTheSecurityPolicy(chessboard) {
    const moveInput = chessboard.view.visualMoveInput;
    moveInput.moveDraggablePiece = function (x, y) {
        const halfSquare = this.view.squareHeight / 2;
        this.draggablePiece.style.left = (x - halfSquare) + 'px';
        this.draggablePiece.style.top = (y - halfSquare) + 'px';
    };
}

/** Works out every position from the start and the moves (UCI). */
function rebuildPositions(moves) {
    const replay = new Chess(serverState.start_fen);
    positions.length = 0;
    sanMoves.length = 0;
    positions.push(replay.fen());
    for (const move of moves) {
        const played = replay.move(uciToMove(move.uci));
        positions.push(replay.fen());
        sanMoves.push(played.san);
    }
}

/** The typed-move field stays for keyboard play on desktop; on touch screens the board is enough. */
function hideTypedMoveFormWhileBoardWorks() {
    const form = document.getElementById('move-form');
    if (form && window.matchMedia('(pointer: coarse)').matches) {
        form.hidden = true;
    }
}

// --- Whose turn: switching move input on and off ---

function isViewersTurn() {
    return serverState.state === 'running'
        && viewerColour !== ''
        && serverState.side_to_move === viewerColour;
}

function updateMoveInput() {
    board.disableMoveInput();
    board.removeMarkers();
    if (isPlanning) {
        board.enableMoveInput(handlePlanningInput);
        return;
    }
    const isShowingCurrentPosition = viewedPly === serverState.ply;
    if (isViewersTurn() && isShowingCurrentPosition) {
        board.enableMoveInput(handleRealInput, viewerColour === 'b' ? COLOR.black : COLOR.white);
    }
}

// --- Moving on the real board ---

function handleRealInput(event) {
    const game = new Chess(positions[serverState.ply]);
    return handleInput(event, game, (uci) => sendMove(uci));
}

function handlePlanningInput(event) {
    return handleInput(event, planningGame, () => {
        board.setPosition(planningGame.fen(), true);
    });
}

/**
 * The shared move-input steps for the real and the planning board:
 * mark the legal targets, check the move with chess.js, ask for the
 * promotion piece when needed, then hand the move on.
 */
function handleInput(event, game, onLegalMove) {
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
                askForPromotion(event, game, onLegalMove);
                return true;
            }
            game.move({from: event.squareFrom, to: event.squareTo});
            onLegalMove(event.squareFrom + event.squareTo);
            return true;
        }
        case INPUT_EVENT_TYPE.moveInputCanceled:
            board.removeMarkers();
            return true;
        default:
            return true;
    }
}

function askForPromotion(event, game, onLegalMove) {
    board.showPromotionDialog(event.squareTo, game.turn() === 'w' ? COLOR.white : COLOR.black, (result) => {
        if (result.type !== PROMOTION_DIALOG_RESULT_TYPE.pieceSelected) {
            board.setPosition(game.fen(), true);
            return;
        }
        const piece = result.piece.charAt(1);
        game.move({from: event.squareFrom, to: event.squareTo, promotion: piece});
        board.setPosition(game.fen(), true);
        onLegalMove(event.squareFrom + event.squareTo + piece);
    });
}

// --- Sending a move to the server (K2: it decides) ---

async function sendMove(uci) {
    board.disableMoveInput();
    const body = new URLSearchParams({_csrf: csrfToken, ply: String(serverState.ply), move: uci});
    try {
        const response = await fetch(basePath + '/games/' + serverState.id + '/move', {
            method: 'POST',
            headers: {'Accept': 'application/json'},
            body: body,
            credentials: 'same-origin',
        });
        const answer = await response.json();
        if (!response.ok) {
            showStatus(answer.error ? answer.error.message : page.dataset.textMoveFailed);
            await refreshFromServer();
            return;
        }
        applyServerState(answer);
    } catch (error) {
        showStatus(page.dataset.textMoveFailed);
        await refreshFromServer();
    }
}

// --- Taking in news from the server ---

/** Adds new moves and state from the server (after our move, or from polling). */
function applyServerState(newState) {
    const hadPly = serverState.ply;
    const wasRunning = serverState.state === 'running';
    const offersChanged = newState.draw_offered_by !== serverState.draw_offered_by
        || newState.rematch_offered_by !== serverState.rematch_offered_by;

    const knownMoves = serverState.moves.filter((move) => move.ply <= hadPly);
    const newMoves = newState.moves.filter((move) => move.ply > hadPly);
    serverState = {...newState, moves: [...knownMoves, ...newMoves]};

    // The buttons (draw, rematch, game over) are drawn by the server: reload for those.
    if ((wasRunning && serverState.state !== 'running') || offersChanged) {
        window.location.reload();
        return;
    }
    if (newMoves.length > 0 || serverState.ply !== hadPly) {
        const wasShowingCurrent = viewedPly === hadPly;
        rebuildPositions(serverState.moves);
        if (wasShowingCurrent && !isPlanning) {
            viewedPly = serverState.ply;
            board.setPosition(positions[viewedPly], true);
        }
        drawMoveList();
    }
    showTurnStatus();
    updateMoveInput();
}

async function refreshFromServer() {
    const response = await fetch(basePath + '/games/' + serverState.id + '/state?since=0', {headers: {'Accept': 'application/json'}, credentials: 'same-origin'});
    if (response.ok) {
        const freshState = await response.json();
        serverState = {...freshState};
        rebuildPositions(serverState.moves);
        viewedPly = serverState.ply;
        board.setPosition(positions[viewedPly], false);
        drawMoveList();
        updateMoveInput();
    }
}

// --- Status line and move list ---

function showStatus(text) {
    document.getElementById('game-status').textContent = text;
}

function showTurnStatus() {
    if (serverState.state !== 'running') {
        showStatus(page.dataset.textGameOver);
    } else if (isViewersTurn()) {
        showStatus(page.dataset.textYourMove);
    } else {
        const moverName = serverState.side_to_move === 'w' ? serverState.white.name : serverState.black.name;
        showStatus(page.dataset.textWaiting.replace('{name}', moverName));
    }
}

/** Draws the move list again, numbered like a score sheet, the shown move marked. */
function drawMoveList() {
    const list = document.getElementById('move-list');
    list.innerHTML = '';
    const startParts = serverState.start_fen.split(' ');
    let moveNumber = parseInt(startParts[5] || '1', 10);
    let isWhiteMove = startParts[1] === 'w';

    sanMoves.forEach((san, index) => {
        const item = document.createElement('li');
        if (isWhiteMove || index === 0) {
            const number = document.createElement('span');
            number.className = 'move-number';
            number.textContent = moveNumber + (isWhiteMove ? '.' : '...');
            item.appendChild(number);
        }
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'move-button';
        button.dataset.ply = String(index + 1);
        button.textContent = san;
        if (index + 1 === viewedPly) {
            button.setAttribute('aria-current', 'step');
        }
        item.appendChild(button);
        list.appendChild(item);
        if (!isWhiteMove) {
            moveNumber++;
        }
        isWhiteMove = !isWhiteMove;
    });
}

// --- Replay, flip and planning (A36) ---

function showPly(ply) {
    if (isPlanning) {
        stopPlanning();
    }
    viewedPly = Math.max(0, Math.min(ply, serverState.ply));
    board.setPosition(positions[viewedPly], true);
    drawMoveList();
    updateMoveInput();
}

function startPlanning() {
    isPlanning = true;
    planningGame = new Chess(positions[viewedPly]);
    document.querySelector('[data-board-action="plan"]').setAttribute('aria-pressed', 'true');
    showStatus(page.dataset.textPlanning);
    updateMoveInput();
}

function stopPlanning() {
    isPlanning = false;
    planningGame = null;
    document.querySelector('[data-board-action="plan"]').setAttribute('aria-pressed', 'false');
    board.setPosition(positions[viewedPly], true);
    showTurnStatus();
    updateMoveInput();
}

function handleToolButton(action) {
    switch (action) {
        case 'flip':
            board.setOrientation(board.getOrientation() === COLOR.white ? COLOR.black : COLOR.white, true);
            break;
        case 'first':
            showPly(0);
            break;
        case 'back':
            showPly(viewedPly - 1);
            break;
        case 'forward':
            showPly(viewedPly + 1);
            break;
        case 'last':
            showPly(serverState.ply);
            break;
        case 'plan':
            if (isPlanning) {
                stopPlanning();
            } else {
                startPlanning();
            }
            break;
    }
}

// --- Helpers ---

/** "e7e8q" as the move object chess.js expects. */
function uciToMove(uci) {
    return {from: uci.slice(0, 2), to: uci.slice(2, 4), promotion: uci.length > 4 ? uci[4] : undefined};
}

// --- Starting ---

setUpBoard();
drawMoveList();

document.getElementById('board-tools').addEventListener('click', (event) => {
    const button = event.target.closest('[data-board-action]');
    if (button) {
        handleToolButton(button.dataset.boardAction);
    }
});
document.getElementById('move-list').addEventListener('click', (event) => {
    const button = event.target.closest('.move-button');
    if (button) {
        showPly(parseInt(button.dataset.ply, 10));
    }
});
document.addEventListener('keydown', (event) => {
    if (event.target instanceof HTMLInputElement) {
        return;
    }
    if (event.key === 'ArrowLeft') {
        showPly(viewedPly - 1);
    } else if (event.key === 'ArrowRight') {
        showPly(viewedPly + 1);
    }
});

if (serverState.state === 'running') {
    startLiveUpdates({
        stateUrl: () => basePath + '/games/' + serverState.id + '/state?since=' + serverState.ply,
        onNews: applyServerState,
    });
}
