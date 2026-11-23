/**
 * The game page in the browser (G-04, G-10, A36): the drag-and-drop board,
 * live moves, replay, and the planning board.
 *
 * templates/play/game.php draws the page; this script, loaded as a module,
 * replaces the table board with cm-chessboard and runs the page's three modes:
 *
 *   play      The real board. Moves by dragging or by tapping two squares,
 *             with the legal targets marked; a pawn reaching the last rank
 *             asks which piece it becomes. chess.js checks the move at once,
 *             then it is sent to POST /games/{id}/move and the server decides
 *             (K1); on a refusal the board goes back to the server's position.
 *             While the move is on its way the board is dimmed and takes no
 *             input. The opponent's moves come in live (live-updates.js).
 *   replay    The replay bar steps and plays through the game; tapping a move
 *             in the list also starts replay at that move.
 *   planning  A board in other colours with its own title: try moves for both
 *             sides; nothing is sent. "Close planning board" goes back.
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

// The parts of the page each mode shows or hides.
const boardFrame = document.getElementById('board');
const playTools = document.getElementById('play-tools');
const replayTools = document.getElementById('replay-tools');
const planningTools = document.getElementById('planning-tools');
const planningTitle = document.getElementById('planning-title');
const movePanel = document.getElementById('move-panel');

const MODE_PLAY = 'play';
const MODE_REPLAY = 'replay';
const MODE_PLANNING = 'planning';

/** How long one step of replay's "play" takes, normal and fast (milliseconds). */
const REPLAY_STEP_TIME = 1000;
const REPLAY_FAST_STEP_TIME = 300;

// Every position of the game as FEN, index = ply (0 = the start).
const positions = [];
// Every move as SAN, index = ply - 1.
const sanMoves = [];

let mode = MODE_PLAY;
let viewedPly = 0;          // the position shown
let planningGame = null;    // a chess.js game while planning
let isSending = false;      // a move is on its way to the server
let replayTimer = null;     // the running replay "play", or null
let board = null;

// --- Building the board ---

function setUpBoard() {
    rebuildPositions(serverState.moves);
    viewedPly = serverState.ply;

    boardFrame.innerHTML = '';
    board = new Chessboard(boardFrame, {
        position: positions[viewedPly],
        orientation: viewerColour === 'b' ? COLOR.black : COLOR.white,
        assetsUrl: basePath + '/assets/vendor/cm-chessboard/assets/',
        style: {cssClass: 'phpchess', showCoordinates: true, animationDuration: 200},
        extensions: [{class: Markers, props: {autoMarkers: null}}, {class: PromotionDialog}],
    });

    keepDraggingWithinTheSecurityPolicy(board);
    showMode(MODE_PLAY);
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

// --- The three modes ---

/** Shows the parts of the page that belong to a mode and hides the others. */
function showMode(newMode) {
    stopReplayTimer();
    mode = newMode;

    playTools.hidden = mode !== MODE_PLAY;
    replayTools.hidden = mode !== MODE_REPLAY;
    planningTools.hidden = mode !== MODE_PLANNING;
    planningTitle.hidden = mode !== MODE_PLANNING;
    movePanel.hidden = mode === MODE_PLANNING;

    // The planning board gets the admin's planning colours instead of the member's own.
    const theme = mode === MODE_PLANNING ? page.dataset.planningBoardTheme : page.dataset.boardTheme;
    boardFrame.className = 'board-frame board-theme-' + theme + (mode === MODE_PLANNING ? ' is-planning' : '');
    planningTitle.className = 'planning-title board-theme-' + page.dataset.planningBoardTheme;

    if (mode === MODE_PLANNING) {
        planningGame = new Chess(positions[viewedPly]);
    } else {
        planningGame = null;
    }
    if (mode === MODE_PLAY) {
        viewedPly = serverState.ply;
        showTurnStatus();
    }
    board.setPosition(positions[viewedPly], true);
    drawMoveList();
    updateMoveInput();
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
    if (mode === MODE_PLANNING) {
        board.enableMoveInput(handlePlanningInput);
    } else if (mode === MODE_PLAY && isViewersTurn() && !isSending) {
        board.enableMoveInput(handleRealInput, viewerColour === 'b' ? COLOR.black : COLOR.white);
    }
}

// --- Moving on the board ---

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

/** Dims the board and blocks input while the server works on the move. */
function setSending(sending) {
    isSending = sending;
    boardFrame.classList.toggle('is-busy', sending);
    boardFrame.setAttribute('aria-busy', sending ? 'true' : 'false');
    if (sending) {
        showStatus(page.dataset.textSending);
    }
    updateMoveInput();
}

async function sendMove(uci) {
    setSending(true);
    const body = new URLSearchParams({_csrf: csrfToken, ply: String(serverState.ply), move: uci});
    try {
        const response = await fetch(basePath + '/games/' + serverState.id + '/move', {
            method: 'POST',
            headers: {'Accept': 'application/json'},
            body: body,
            credentials: 'same-origin',
        });
        const answer = await response.json();
        setSending(false);
        if (!response.ok) {
            await refreshFromServer();
            showStatus(answer.error ? answer.error.message : page.dataset.textMoveFailed);
            return;
        }
        applyServerState(answer);
    } catch (error) {
        setSending(false);
        await refreshFromServer();
        showStatus(page.dataset.textMoveFailed);
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

    // The game buttons (draw, abort, rematch, game over) are drawn by the server: reload for those.
    const abortButtonMustGo = hadPly === 0 && serverState.ply > 0;
    if ((wasRunning && serverState.state !== 'running') || offersChanged || abortButtonMustGo) {
        window.location.reload();
        return;
    }
    if (serverState.ply !== hadPly) {
        rebuildPositions(serverState.moves);
        if (mode === MODE_PLAY) {
            viewedPly = serverState.ply;
            board.setPosition(positions[viewedPly], true);
        }
        drawMoveList();
    }
    if (mode === MODE_PLAY) {
        showTurnStatus();
    }
    updateMoveInput();
}

async function refreshFromServer() {
    const response = await fetch(basePath + '/games/' + serverState.id + '/state?since=0', {headers: {'Accept': 'application/json'}, credentials: 'same-origin'});
    if (response.ok) {
        serverState = {...(await response.json())};
        rebuildPositions(serverState.moves);
        showMode(MODE_PLAY);
    }
}

// --- Status line and move list ---

function showStatus(text) {
    document.getElementById('game-status').textContent = text;
}

function showTurnStatus() {
    if (serverState.state !== 'running') {
        return;   // the server's "Game over: 1-0, checkmate" stays
    }
    if (isViewersTurn()) {
        showStatus(page.dataset.textYourMove);
    } else {
        const moverName = serverState.side_to_move === 'w' ? serverState.white.name : serverState.black.name;
        showStatus(page.dataset.textWaiting.replace('{name}', moverName));
    }
}

/** Draws the move list again, numbered like a score sheet; in replay the shown move is marked. */
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

    const current = list.querySelector('[aria-current="step"]');
    if (current) {
        current.scrollIntoView({block: 'nearest'});
    }
}

// --- Replay (A36) ---

/** Shows one position of the game, in replay mode. */
function showPly(ply) {
    if (mode !== MODE_REPLAY) {
        showMode(MODE_REPLAY);
    }
    viewedPly = Math.max(0, Math.min(ply, serverState.ply));
    board.setPosition(positions[viewedPly], true);
    drawMoveList();
}

/** Steps through the game by itself: direction +1 (play) or -1 (backwards). */
function startReplayTimer(direction, stepTime) {
    stopReplayTimer();
    replayTimer = window.setInterval(() => {
        const next = viewedPly + direction;
        if (next < 0 || next > serverState.ply) {
            stopReplayTimer();
            return;
        }
        showPly(next);
    }, stepTime);
}

function stopReplayTimer() {
    if (replayTimer !== null) {
        window.clearInterval(replayTimer);
        replayTimer = null;
    }
}

// --- The buttons under the board ---

function handleToolButton(action) {
    switch (action) {
        case 'flip':
            board.setOrientation(board.getOrientation() === COLOR.white ? COLOR.black : COLOR.white, true);
            break;
        case 'plan':
            showMode(MODE_PLANNING);
            break;
        case 'close-planning':
        case 'close-replay':
            showMode(MODE_PLAY);
            break;
        case 'replay':
            showMode(MODE_REPLAY);
            break;
        case 'first':
            stopReplayTimer();
            showPly(0);
            break;
        case 'play-backwards':
            startReplayTimer(-1, REPLAY_FAST_STEP_TIME);
            break;
        case 'back':
            stopReplayTimer();
            showPly(viewedPly - 1);
            break;
        case 'pause':
            stopReplayTimer();
            break;
        case 'play':
            if (viewedPly >= serverState.ply) {
                showPly(0);
            }
            startReplayTimer(1, REPLAY_STEP_TIME);
            break;
        case 'forward':
            stopReplayTimer();
            showPly(viewedPly + 1);
            break;
        case 'fast-forward':
            startReplayTimer(1, REPLAY_FAST_STEP_TIME);
            break;
        case 'last':
            stopReplayTimer();
            showPly(serverState.ply);
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

page.addEventListener('click', (event) => {
    const toolButton = event.target.closest('[data-board-action]');
    if (toolButton) {
        handleToolButton(toolButton.dataset.boardAction);
        return;
    }
    const moveButton = event.target.closest('.move-button');
    if (moveButton) {
        stopReplayTimer();
        showPly(parseInt(moveButton.dataset.ply, 10));
    }
});
document.addEventListener('keydown', (event) => {
    if (event.target instanceof HTMLInputElement || mode === MODE_PLANNING) {
        return;
    }
    if (event.key === 'ArrowLeft') {
        handleToolButton('back');
    } else if (event.key === 'ArrowRight') {
        handleToolButton('forward');
    } else if (event.key === 'Escape' && mode === MODE_REPLAY) {
        showMode(MODE_PLAY);
    }
});

if (serverState.state === 'running') {
    startLiveUpdates({
        stateUrl: () => basePath + '/games/' + serverState.id + '/state?since=' + serverState.ply,
        onNews: applyServerState,
    });
}
