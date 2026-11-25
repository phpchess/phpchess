/**
 * Plays one game between two computer players (development only: calibration).
 *
 * Feature AI-05, decision A51. Both sides use decideMove() from the engine
 * files the phones run (loaded by tools/engine-in-node.mjs). This file is the
 * referee: it applies the moves and ends the game on mate, stalemate,
 * threefold repetition, the 50-move rule, too little material, or a length
 * limit (a long game counts as a draw).
 */

// --- The start position and limits ---

export const START = "rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1";

// A game this long is stopped and counted as a draw (300 half-moves = 150 moves).
const MAX_PLIES = 300;

// --- Small helpers about positions ---

/** The position part of a FEN that matters for repetition (board, side, castling, en passant). */
function repetitionKey(fen) {
    return fen.split(" ").slice(0, 4).join(" ");
}

/** True when neither side can ever mate: bare kings, or king and one minor piece against a king. */
function tooLittleMaterial(fen) {
    const pieces = fen.split(" ")[0].replace(/[^a-zA-Z]/g, "").replace(/[kK]/g, "");
    return pieces === "" || /^[nNbB]$/.test(pieces);
}

/**
 * Plays the move (text like "e2e4" or "e7e8q") in the engine and returns
 * { fen, resetsFiftyMoveCount }, or throws if the move is not legal.
 */
function applyMove(engine, fen, moveText) {
    engine.loadPosition(fen);
    const move = engine.GenerateValidMoves().find((candidate) => engine.FormatMove(candidate) === moveText);
    if (move === undefined) {
        throw new Error("Illegal move " + moveText + " in " + fen);
    }
    const from = move & 0xFF;
    const isPawnMove = (engine.g_board[from] & 0x7) === 1;
    const resetsFiftyMoveCount = isPawnMove || engine.isCaptureMove(move);
    engine.MakeMove(move);
    return { fen: engine.GetFen() + " 0 1", resetsFiftyMoveCount: resetsFiftyMoveCount };
}

/** Whether the side to move has no legal move, and whether it is in check. */
function sideToMoveState(engine, fen) {
    engine.loadPosition(fen);
    return { noMoves: engine.GenerateValidMoves().length === 0, inCheck: engine.g_inCheck };
}

// --- One game ---

/**
 * Plays a game. white and black are player settings (see garbochess-engine.js).
 * gameId makes the game's random choices; different ids give different games.
 * Returns { result: "1-0" | "0-1" | "1/2-1/2", reason, moves: ["e2e4", ...],
 * choices: [why each move was chosen: "book", "chosen", "mistake", ...] }.
 */
export function playGame(engine, white, black, gameId) {
    let fen = START;
    const moves = [];
    const choices = [];
    const seen = new Map([[repetitionKey(fen), 1]]);
    let halfMovesSinceProgress = 0;

    for (let ply = 0; ply < MAX_PLIES; ply++) {
        const whiteToMove = ply % 2 === 0;
        const state = sideToMoveState(engine, fen);
        if (state.noMoves) {
            if (!state.inCheck) {
                return { result: "1/2-1/2", reason: "stalemate", moves: moves, choices: choices };
            }
            return { result: whiteToMove ? "0-1" : "1-0", reason: "mate", moves: moves, choices: choices };
        }

        const player = whiteToMove ? white : black;
        const decision = engine.decideMove(fen, player, gameId, ply, moves);
        const applied = applyMove(engine, fen, decision.move);
        fen = applied.fen;
        moves.push(decision.move);
        choices.push(decision.reason);
        halfMovesSinceProgress = applied.resetsFiftyMoveCount ? 0 : halfMovesSinceProgress + 1;

        const key = repetitionKey(fen);
        seen.set(key, (seen.get(key) || 0) + 1);
        if (seen.get(key) >= 3) {
            return { result: "1/2-1/2", reason: "repetition", moves: moves, choices: choices };
        }
        if (halfMovesSinceProgress >= 100) {
            return { result: "1/2-1/2", reason: "50 moves", moves: moves, choices: choices };
        }
        if (tooLittleMaterial(fen)) {
            return { result: "1/2-1/2", reason: "material", moves: moves, choices: choices };
        }
    }
    return { result: "1/2-1/2", reason: "length limit", moves: moves, choices: choices };
}
