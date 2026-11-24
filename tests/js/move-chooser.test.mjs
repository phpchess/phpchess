/**
 * Tests for the move chooser with fixed engine outputs (BUILD_PLAN C3-2).
 *
 * Feature AI-02, decision A48. Run with: node --test tests/js/
 * No search is needed here: the scored moves are written out by hand, so each
 * test shows exactly which moves may and may not be chosen.
 */

import { test } from "node:test";
import assert from "node:assert/strict";
import { loadEngine } from "../../tools/engine-in-node.mjs";

const engine = loadEngine();

// --- A position's moves as the engine would score them, best first ---

function fixedScores() {
    return [
        { move: "d2d4", centipawns: 40, mateIn: null, isCapture: false, givesCheck: false, isGiveaway: false },
        { move: "e2e4", centipawns: 35, mateIn: null, isCapture: false, givesCheck: false, isGiveaway: false },
        { move: "g1f3", centipawns: 10, mateIn: null, isCapture: false, givesCheck: false, isGiveaway: false },
        { move: "d1h5", centipawns: -300, mateIn: null, isCapture: false, givesCheck: false, isGiveaway: true },
    ];
}

/** The set of moves chosen over many move numbers. */
function movesChosen(scores, settings, times) {
    const seen = new Set();
    for (let ply = 0; ply < times; ply++) {
        seen.add(engine.chooseMove(scores, settings, engine.randomForMove("game", ply)).move);
    }
    return seen;
}

// --- Tests ---

test("the same seed always gives the same random numbers", () => {
    const first = engine.randomForMove("game-7", 12);
    const second = engine.randomForMove("game-7", 12);
    for (let count = 0; count < 5; count++) {
        assert.equal(first(), second());
    }
    assert.notEqual(engine.randomForMove("game-7", 13)(), engine.randomForMove("game-7", 12)());
});

test("a margin of 0 always plays the best move", () => {
    assert.deepEqual([...movesChosen(fixedScores(), { marginCentipawns: 0 }, 50)], ["d2d4"]);
});

test("only moves within the margin are played", () => {
    assert.deepEqual([...movesChosen(fixedScores(), { marginCentipawns: 10 }, 200)].sort(), ["d2d4", "e2e4"]);
});

test("a giveaway is never played while the filter is on, even with a huge margin", () => {
    assert.equal(movesChosen(fixedScores(), { marginCentipawns: 10000 }, 300).has("d1h5"), false);
});

test("a player that always skips the filter can play the giveaway", () => {
    const settings = { marginCentipawns: 10000, giveawaySkipChance: 1 };
    assert.equal(movesChosen(fixedScores(), settings, 300).has("d1h5"), true);
});

test("the wood pusher plays every legal move sometimes", () => {
    assert.equal(movesChosen(fixedScores(), { randomMover: true }, 300).size, 4);
});

test("style bonuses change which moves are within the margin", () => {
    const scores = fixedScores();
    scores[2].isCapture = true; // g1f3 now "captures"
    const aggressive = { marginCentipawns: 0, style: { captureBonus: 50 } };
    assert.deepEqual([...movesChosen(scores, aggressive, 20)], ["g1f3"]);
});

test("mate beats any material and a quicker mate beats a slower one", () => {
    const scores = [
        { move: "a1a8", centipawns: 0, mateIn: 3, isCapture: false, givesCheck: true, isGiveaway: false },
        { move: "b1b8", centipawns: 0, mateIn: 1, isCapture: false, givesCheck: true, isGiveaway: false },
        { move: "c1c8", centipawns: 900, mateIn: null, isCapture: true, givesCheck: false, isGiveaway: false },
    ];
    assert.deepEqual([...movesChosen(scores, { marginCentipawns: 0 }, 20)], ["b1b8"]);
});
