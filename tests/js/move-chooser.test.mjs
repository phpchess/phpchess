/**
 * Tests for the move chooser with fixed engine outputs (BUILD_PLAN C3-2).
 *
 * Feature AI-02, decisions A48, A56 and A57. Run with: node --test tests/js/
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

// --- Human-like play (A56, A57) ---

/** How often each reason comes up over many move numbers. */
function reasonsChosen(scores, settings, times) {
    const counts = {};
    for (let ply = 0; ply < times; ply++) {
        const choice = engine.chooseMove(scores, settings, engine.randomForMove("human", ply));
        counts[choice.reason] = (counts[choice.reason] || 0) + 1;
    }
    return counts;
}

/** A position with a mate, a winning capture, a recapture and quiet moves. */
function tacticScores() {
    return [
        { move: "d1h7", centipawns: 0, mateIn: 1, isCapture: true, givesCheck: true, isGiveaway: false },
        { move: "f3e5", centipawns: 300, mateIn: null, isCapture: true, givesCheck: false, isGiveaway: false },
        { move: "g2g3", centipawns: 20, mateIn: null, isCapture: false, givesCheck: false, isGiveaway: false },
        { move: "a2a3", centipawns: 10, mateIn: null, isCapture: false, givesCheck: false, isGiveaway: false },
    ];
}

test("a giveaway is played about as often as its chance says", () => {
    const counts = reasonsChosen(fixedScores(), { giveawayChance: 0.1 }, 4000);
    assert.ok(Math.abs(counts.giveaway / 4000 - 0.1) < 0.02, "giveaways " + counts.giveaway);
});

test("in a blind spot the player misses mates and winning captures, never sees them otherwise", () => {
    assert.deepEqual([...movesChosen(tacticScores(), { blindSpotChance: 1 }, 100)], ["g2g3"]);
    assert.deepEqual([...movesChosen(tacticScores(), { blindSpotChance: 0 }, 100)], ["d1h7"]);
});

test("a recapture is never hidden by a blind spot", () => {
    const scores = tacticScores().slice(1);
    scores[0].isRecapture = true;
    assert.deepEqual([...movesChosen(scores, { blindSpotChance: 1 }, 100)], ["f3e5"]);
});

test("noise blurs small differences but keeps large ones", () => {
    const blurred = movesChosen(fixedScores(), { noiseCentipawns: 100 }, 400);
    assert.ok(blurred.has("d2d4") && blurred.has("e2e4") && blurred.has("g1f3"), "close moves all played");
    const material = [
        { move: "e4d5", centipawns: 320, mateIn: null, isCapture: true, givesCheck: false, isGiveaway: false },
        { move: "a2a3", centipawns: 0, mateIn: null, isCapture: false, givesCheck: false, isGiveaway: false },
    ];
    assert.deepEqual([...movesChosen(material, { noiseCentipawns: 100 }, 400)], ["e4d5"]);
});

test("without the human-like settings the player plays as before", () => {
    assert.deepEqual(reasonsChosen(tacticScores(), { marginCentipawns: 0 }, 300), { chosen: 300 });
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
