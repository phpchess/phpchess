/**
 * Tests for the GarboChess helpers and one full decision (BUILD_PLAN C3-2).
 *
 * Features AI-01, AI-02, AI-04; decisions A48, A49. Run with: node --test tests/js/
 * Uses the real engine on a few positions with a known right answer.
 */

import { test } from "node:test";
import assert from "node:assert/strict";
import { loadEngine } from "../../tools/engine-in-node.mjs";

// --- Positions ---

const START = "rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1";
// White's knight on f3 can take a black queen left on h4.
const HANGING_QUEEN = "rnb1kbnr/pppp1ppp/8/4p3/4P2q/5N2/PPPP1PPP/RNBQKB1R w KQkq - 2 3";
// Back-rank mate in one: Ra8#.
const BACK_RANK = "6k1/5ppp/8/8/8/8/5PPP/R5K1 w - - 0 1";
// White to move after 1.d4 d5 2.Nc3 e6: Bg5?? is taken by the d8 queen for nothing.
const BISHOP_EN_PRISE = "rnbqkbnr/ppp2ppp/4p3/3p4/3P4/2N5/PPP1PPPP/R1BQKBNR w KQkq - 0 3";

const middlePlayer = {
    depth: { baseDepth: 3, exchangeExtra: 1, endgameExtra: 1, endgamePieces: 4, depthCap: 5 },
    marginCentipawns: 40,
    giveawaySkipChance: 0,
    style: {},
};

// --- Tests ---

test("scores come back for every legal move, best first", () => {
    const engine = loadEngine();
    const scores = engine.scoreAllMoves(START, 2);
    assert.equal(scores.length, 20);
    for (let index = 1; index < scores.length; index++) {
        assert.ok(engine.comparableScore(scores[index - 1]) >= engine.comparableScore(scores[index]));
    }
});

test("winning a hanging queen scores best", () => {
    const engine = loadEngine();
    assert.equal(engine.scoreAllMoves(HANGING_QUEEN, 3)[0].move, "f3h4");
});

test("a mate in one is found and marked", () => {
    const engine = loadEngine();
    const best = engine.scoreAllMoves(BACK_RANK, 2)[0];
    assert.equal(best.move, "a1a8");
    assert.equal(best.mateIn, 1);
});

test("a piece put en prise is marked as a giveaway, normal moves are not", () => {
    const engine = loadEngine();
    const scores = engine.scoreAllMoves(BISHOP_EN_PRISE, 2);
    const byMove = Object.fromEntries(scores.map((scored) => [scored.move, scored]));
    assert.equal(byMove["g1f3"].isGiveaway, false);
    assert.equal(byMove["e2e4"].isGiveaway, false);
    assert.equal(byMove["c1g5"].isGiveaway, true);
});

test("the depth grows in exchanges and endgames and respects the cap", () => {
    const engine = loadEngine();
    const settings = { baseDepth: 3, exchangeExtra: 1, endgameExtra: 2, endgamePieces: 2, depthCap: 5 };
    assert.equal(engine.depthForPosition(START, settings), 3);
    assert.equal(engine.depthForPosition(HANGING_QUEEN, settings), 4); // a capture is on
    assert.equal(engine.depthForPosition(BACK_RANK, settings), 5);     // endgame: 3 + 2
    assert.equal(engine.depthForPosition(BACK_RANK, { ...settings, depthCap: 4 }), 4);
});

test("the same position, player, game and move number give the same move in two separate engines", () => {
    const first = loadEngine().decideMove(BISHOP_EN_PRISE, middlePlayer, "game-42", 4);
    const second = loadEngine().decideMove(BISHOP_EN_PRISE, middlePlayer, "game-42", 4);
    assert.equal(first.move, second.move);
    assert.equal(first.depth, second.depth);
});

test("a full decision never plays a giveaway when the filter is on", () => {
    const engine = loadEngine();
    const wide = { ...middlePlayer, marginCentipawns: 5000 };
    for (let ply = 0; ply < 30; ply++) {
        const decision = engine.decideMove(BISHOP_EN_PRISE, wide, "game-1", ply);
        const chosen = decision.scores.find((scored) => scored.move === decision.move);
        assert.equal(chosen.isGiveaway, false, decision.move + " gives material away");
    }
});
