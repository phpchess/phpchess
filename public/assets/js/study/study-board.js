/**
 * The study board in the browser (C6-1, A129-A136).
 *
 * templates/study/board.php draws the parts; this module runs them:
 *   - the board (cm-chessboard), with the last move framed and the
 *     move's drawings (arrows and circles) shown;
 *   - the move tree: the main line, side lines in brackets; a tap on a
 *     move puts it on the board;
 *   - for the owner: play a move on the board (a new move becomes a side
 *     line), symbols, drawings, comment, promote, delete. Every change is
 *     saved at once: the whole tree goes to the server, which checks
 *     every move again (MoveTree::fromList) and answers with the tree
 *     as it was saved.
 *
 * The tree is a list of nodes, the same shape as MoveTree::toList():
 *   {n: number, p: parent, san, u: squares "e2e4", f: position after,
 *    s: symbol codes, c: comment, d: drawings ["Ge2e4", "Rd4"]}
 * Node 0 is the start position. The order of the list is the order of
 * the side lines: the first child of a node is its main line.
 *
 * Drawing (A134): tap a colour button, then tap a square (a circle) or two
 * squares (an arrow). Drawing the same thing again removes it. Tap the
 * colour again to stop drawing and play moves.
 *
 * Comments are only ever put in as text, never as HTML.
 *
 * Libraries (local, A16): cm-chessboard 8.15.8 (with its markers, arrows
 * and promotion dialog extensions) and chess.js 1.4.0, in /assets/vendor/.
 */

import {Chessboard, COLOR, INPUT_EVENT_TYPE} from '../../vendor/cm-chessboard/src/Chessboard.js';
import {Markers, MARKER_TYPE} from '../../vendor/cm-chessboard/src/extensions/markers/Markers.js';
import {Arrows, ARROW_TYPE} from '../../vendor/cm-chessboard/src/extensions/arrows/Arrows.js';
import {PromotionDialog, PROMOTION_DIALOG_RESULT_TYPE} from '../../vendor/cm-chessboard/src/extensions/promotion-dialog/PromotionDialog.js';
import {Chess} from '../../vendor/chess.js/chess.js';

// ===== The colours: drawing letter => arrow type and circle type (A129) =====
const ARROWS = {G: ARROW_TYPE.success, R: ARROW_TYPE.danger, Y: ARROW_TYPE.warning, B: ARROW_TYPE.info};
const CIRCLES = {
    G: {class: 'marker-circle-success', slice: 'markerCircle'},
    R: {class: 'marker-circle-danger', slice: 'markerCircle'},
    Y: {class: 'marker-circle-warning', slice: 'markerCircle'},
    B: {class: 'marker-circle-info', slice: 'markerCircle'},
};

/** The move symbols ($1-$9): one per move. The position symbols are $10-$19. */
const isMoveSymbol = (code) => code >= 1 && code <= 9;

class StudyBoard {

    /** @param {HTMLElement} root  the #study-board element */
    constructor(root) {
        this.root = root;
        this.symbols = JSON.parse(root.dataset.symbols);
        this.isEditable = root.dataset.editable === '1';
        this.current = 0;          // the node on the board
        this.drawColour = null;    // the colour letter while drawing, else null
        this.drawFrom = null;      // the first square of an arrow being drawn
        this.isSaving = false;

        this.setTree(JSON.parse(root.dataset.tree));

        this.movesBox = root.querySelector('[data-study-moves]');
        this.commentBox = root.querySelector('[data-study-comment]');
        this.commentInput = root.querySelector('[data-study-comment-input]');
        this.status = root.querySelector('[data-study-status]');
        this.boardElement = root.querySelector('[data-study-board]');

        this.board = new Chessboard(this.boardElement, {
            position: this.nodes.get(0).f,
            assetsUrl: root.dataset.basePath + '/assets/vendor/cm-chessboard/assets/',
            style: {cssClass: 'phpchess', showCoordinates: true, animationDuration: 200},
            extensions: [
                {class: Markers, props: {autoMarkers: null}},
                {class: Arrows},
                {class: PromotionDialog},
            ],
        });

        this.connectButtons();
        if (this.isEditable) {
            this.board.enableMoveInput((event) => this.onMoveInput(event));
            // Taps while drawing: the square under the finger (cm-chessboard marks every square).
            this.boardElement.addEventListener('pointerdown', (event) => this.onDrawTap(event));
        }
        this.show(0);
    }

    // ===== The tree =====

    /** Keeps the list as a map of nodes and a map of children, in order. */
    setTree(list) {
        this.nodes = new Map();
        this.children = new Map();
        for (const node of list) {
            this.nodes.set(node.n, node);
            this.children.set(node.n, []);
        }
        for (const node of list) {
            if (node.n !== 0) {
                this.children.get(node.p).push(node.n);
            }
        }
    }

    /** The tree as a list again, parents before children, for saving. */
    treeList() {
        const list = [];
        const waiting = [0];
        while (waiting.length > 0) {
            const number = waiting.shift();
            const node = this.nodes.get(number);
            list.push({n: node.n, p: node.p, san: node.san, s: node.s, c: node.c, d: node.d});
            waiting.push(...this.children.get(number));
        }
        return list;
    }

    nextNumber() {
        return Math.max(...this.nodes.keys()) + 1;
    }

    // ===== Showing =====

    /** Puts a node on the board: position, last move, drawings, moves, comment, tools. */
    show(number) {
        this.current = number;
        const node = this.nodes.get(number);
        this.board.setPosition(node.f, true);

        this.board.removeMarkers();
        this.board.removeArrows();
        if (node.u) {
            this.board.addMarker(MARKER_TYPE.frame, node.u.slice(0, 2));
            this.board.addMarker(MARKER_TYPE.frame, node.u.slice(2, 4));
        }
        for (const drawing of node.d) {
            if (drawing.length === 5) {
                this.board.addArrow(ARROWS[drawing[0]], drawing.slice(1, 3), drawing.slice(3, 5));
            } else {
                this.board.addMarker(CIRCLES[drawing[0]], drawing.slice(1, 3));
            }
        }

        this.commentBox.textContent = node.c;
        if (this.commentInput) {
            this.commentInput.value = node.c;
        }
        this.root.querySelectorAll('[data-study-symbol]').forEach((button) => {
            button.setAttribute('aria-pressed', node.s.includes(Number(button.dataset.studySymbol)) ? 'true' : 'false');
        });
        this.drawMoves();
    }

    /**
     * Writes the moves as text: "1. e4 e5 2. Nf3 (2. f4 exf4) 2... Nc6".
     * Every move is a button; the one on the board is marked.
     */
    drawMoves() {
        this.movesBox.replaceChildren();
        const start = document.createElement('button');
        start.type = 'button';
        start.className = 'study-move' + (this.current === 0 ? ' study-move-current' : '');
        start.textContent = this.root.dataset.textStart;
        start.addEventListener('click', () => this.show(0));
        this.movesBox.append(start, ' ');
        this.drawLine(this.movesBox, 0, true);
    }

    /** The moves after one node: the main move, its side lines in brackets, then on. */
    drawLine(box, parent, needsNumber) {
        const children = this.children.get(parent);
        if (children.length === 0) {
            return;
        }
        const [main, ...sides] = children;
        this.drawMove(box, main, needsNumber);
        for (const side of sides) {
            const sideBox = document.createElement('span');
            sideBox.className = 'study-side-line';
            sideBox.append('(');
            this.drawMove(sideBox, side, true);
            this.drawLine(sideBox, side, this.nodes.get(side).c !== '');
            sideBox.append(') ');
            box.append(sideBox);
        }
        this.drawLine(box, main, sides.length > 0 || this.nodes.get(main).c !== '');
    }

    /** One move: number when needed, SAN with its symbols, as a button. */
    drawMove(box, number, needsNumber) {
        const node = this.nodes.get(number);
        const before = this.nodes.get(node.p).f.split(' ');
        const isWhite = before[1] === 'w';
        const moveNumber = before[5];

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'study-move' + (number === this.current ? ' study-move-current' : '');
        const prefix = isWhite ? moveNumber + '. ' : (needsNumber ? moveNumber + '... ' : '');
        button.textContent = prefix + node.san + node.s.map((code) => this.symbols[code] ?? '').join('');
        if (node.c !== '') {
            button.title = node.c;
        }
        button.addEventListener('click', () => this.show(number));
        box.append(button, ' ');
    }

    // ===== Buttons =====

    connectButtons() {
        this.root.querySelectorAll('[data-study-action]').forEach((button) => {
            button.addEventListener('click', () => this.onAction(button));
        });
        this.root.querySelectorAll('[data-study-symbol]').forEach((button) => {
            button.addEventListener('click', () => this.toggleSymbol(Number(button.dataset.studySymbol)));
        });
        this.root.querySelectorAll('[data-study-colour]').forEach((button) => {
            button.addEventListener('click', () => this.chooseColour(button.dataset.studyColour));
        });
        document.addEventListener('keydown', (event) => {
            if (event.target.closest('textarea, input')) {
                return;
            }
            const keys = {ArrowLeft: 'back', ArrowRight: 'forward', Home: 'first', End: 'last'};
            if (keys[event.key]) {
                event.preventDefault();
                this.go(keys[event.key]);
            }
        });
    }

    onAction(button) {
        const action = button.dataset.studyAction;
        switch (action) {
            case 'first':
            case 'back':
            case 'forward':
            case 'last':
                this.go(action);
                break;
            case 'flip':
                this.board.setOrientation(this.board.getOrientation() === COLOR.white ? COLOR.black : COLOR.white);
                break;
            case 'save-comment':
                this.nodes.get(this.current).c = this.commentInput.value.replace(/\s+/g, ' ').trim();
                this.save();
                break;
            case 'erase':
                this.nodes.get(this.current).d = [];
                this.save();
                break;
            case 'promote':
                this.promote(this.current);
                this.save();
                break;
            case 'delete':
                if (this.current !== 0 && window.confirm(button.dataset.confirm)) {
                    const parent = this.nodes.get(this.current).p;
                    this.deleteNode(this.current);
                    this.current = parent;
                    this.save();
                }
                break;
        }
    }

    /** Moves through the tree: along the main line from where we are. */
    go(where) {
        const node = this.nodes.get(this.current);
        if (where === 'first') {
            this.show(0);
        } else if (where === 'back') {
            this.show(this.current === 0 ? 0 : node.p);
        } else if (where === 'forward') {
            const next = this.children.get(this.current)[0];
            if (next !== undefined) {
                this.show(next);
            }
        } else if (where === 'last') {
            let number = this.current;
            while (this.children.get(number).length > 0) {
                number = this.children.get(number)[0];
            }
            this.show(number);
        }
    }

    // ===== Editing =====

    /** A move on the board: checked with chess.js, then added (or the known one shown). */
    onMoveInput(event) {
        if (this.drawColour !== null || this.isSaving) {
            return false;
        }
        if (event.type === INPUT_EVENT_TYPE.moveInputStarted) {
            const game = new Chess(this.nodes.get(this.current).f);
            return game.moves({square: event.squareFrom}).length > 0;
        }
        if (event.type !== INPUT_EVENT_TYPE.validateMoveInput) {
            return true;
        }
        const game = new Chess(this.nodes.get(this.current).f);
        const piece = game.get(event.squareFrom);
        const isPromotion = piece && piece.type === 'p' && (event.squareTo[1] === '8' || event.squareTo[1] === '1');
        if (isPromotion) {
            const legal = game.moves({square: event.squareFrom, verbose: true}).some((move) => move.to === event.squareTo);
            if (!legal) {
                return false;
            }
            this.board.showPromotionDialog(event.squareTo, game.turn() === 'w' ? COLOR.white : COLOR.black, (result) => {
                if (result.type === PROMOTION_DIALOG_RESULT_TYPE.pieceSelected) {
                    this.playMove(event.squareFrom, event.squareTo, result.piece.charAt(1));
                } else {
                    this.show(this.current);
                }
            });
            return true;
        }
        return this.playMove(event.squareFrom, event.squareTo, undefined);
    }

    /** Plays a move from the node on the board; true when it was legal. */
    playMove(from, to, promotion) {
        const game = new Chess(this.nodes.get(this.current).f);
        let move;
        try {
            move = game.move({from, to, promotion});
        } catch {
            return false;
        }
        const known = this.children.get(this.current).find((number) => this.nodes.get(number).san === move.san);
        if (known !== undefined) {
            setTimeout(() => this.show(known));
            return true;
        }
        const number = this.nextNumber();
        this.nodes.set(number, {n: number, p: this.current, san: move.san, u: from + to + (promotion ?? ''), f: game.fen(), s: [], c: '', d: []});
        this.children.set(number, []);
        this.children.get(this.current).push(number);
        this.current = number;
        setTimeout(() => this.save());
        return true;
    }

    toggleSymbol(code) {
        const node = this.nodes.get(this.current);
        if (this.current === 0) {
            return;
        }
        if (node.s.includes(code)) {
            node.s = node.s.filter((other) => other !== code);
        } else {
            // One move symbol and one position symbol at most.
            node.s = node.s.filter((other) => isMoveSymbol(other) !== isMoveSymbol(code)).concat([code]);
        }
        this.save();
    }

    /** Makes a move's line the main line, from the start down to it (as on Lichess). */
    promote(number) {
        while (number !== 0) {
            const parent = this.nodes.get(number).p;
            const siblings = this.children.get(parent);
            this.children.set(parent, [number, ...siblings.filter((other) => other !== number)]);
            number = parent;
        }
    }

    deleteNode(number) {
        const parent = this.nodes.get(number).p;
        this.children.set(parent, this.children.get(parent).filter((other) => other !== number));
        const waiting = [number];
        while (waiting.length > 0) {
            const gone = waiting.shift();
            waiting.push(...this.children.get(gone));
            this.nodes.delete(gone);
            this.children.delete(gone);
        }
    }

    // ===== Drawing =====

    chooseColour(letter) {
        this.drawColour = this.drawColour === letter ? null : letter;
        this.drawFrom = null;
        this.root.querySelectorAll('[data-study-colour]').forEach((button) => {
            button.setAttribute('aria-pressed', button.dataset.studyColour === this.drawColour ? 'true' : 'false');
        });
        this.board.removeMarkers(MARKER_TYPE.dot);
    }

    /** A tap while a colour is chosen: the first square, then a circle or an arrow. */
    onDrawTap(event) {
        if (this.drawColour === null) {
            return;
        }
        const squareElement = event.target.closest('[data-square]');
        if (!squareElement) {
            return;
        }
        event.preventDefault();
        event.stopPropagation();
        const square = squareElement.getAttribute('data-square');
        if (this.drawFrom === null) {
            this.drawFrom = square;
            this.board.addMarker(MARKER_TYPE.dot, square);
            return;
        }
        const drawing = this.drawColour + (this.drawFrom === square ? square : this.drawFrom + square);
        this.drawFrom = null;
        const node = this.nodes.get(this.current);
        // The same squares again: the same colour removes it, another colour replaces it.
        const squares = drawing.slice(1);
        const old = node.d.find((other) => other.slice(1) === squares);
        node.d = node.d.filter((other) => other.slice(1) !== squares);
        if (old !== drawing) {
            node.d.push(drawing);
        }
        this.save();
    }

    // ===== Saving =====

    /** Sends the whole tree; shows the tree the server saved, or says it failed. */
    async save() {
        this.show(this.current);
        this.isSaving = true;
        this.setStatus(this.root.dataset.textSaving);
        const form = new FormData();
        form.append('_csrf', this.root.dataset.csrf);
        form.append('tree', JSON.stringify(this.treeList()));
        try {
            const response = await fetch(this.root.dataset.saveUrl, {method: 'POST', body: form, credentials: 'same-origin'});
            const answer = await response.json();
            if (!response.ok || !answer.ok) {
                throw new Error(answer.error || this.root.dataset.textSaveFailed);
            }
            this.setTree(answer.tree);
            if (!this.nodes.has(this.current)) {
                this.current = 0;
            }
            this.setStatus(this.root.dataset.textSaved);
        } catch (error) {
            this.setStatus(error.message || this.root.dataset.textSaveFailed);
        }
        this.isSaving = false;
        this.show(this.current);
    }

    setStatus(text) {
        if (this.status) {
            this.status.textContent = text;
        }
    }
}

// ===== Start =====
const root = document.getElementById('study-board');
if (root) {
    new StudyBoard(root);
}

// A form that asks before it is sent (removing a board).
document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (!window.confirm(form.dataset.confirm)) {
            event.preventDefault();
        }
    });
});
