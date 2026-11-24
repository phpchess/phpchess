/**
 * Tests for the calibration tools: the referee and the Elo fit (BUILD_PLAN C3-2).
 *
 * Feature AI-05, decision A51. Run with: node --test "tests/js/*.test.mjs"
 */

import { test } from "node:test";
import assert from "node:assert/strict";
import { loadEngine } from "../../tools/engine-in-node.mjs";
import { playGame } from "../../tools/calibration/play-game.mjs";
import { expectedScore, fitRatings } from "../../tools/calibration/elo-fit.mjs";

const randomMover = { randomMover: true };

test("two random movers finish a legal game with a proper ending", () => {
    const engine = loadEngine();
    const game = playGame(engine, randomMover, randomMover, "test-game");
    assert.ok(["1-0", "0-1", "1/2-1/2"].includes(game.result));
    assert.ok(game.moves.length > 0);
    assert.ok(["mate", "stalemate", "repetition", "50 moves", "material", "length limit"].includes(game.reason));
});

test("the same game id replays the same game", () => {
    const first = playGame(loadEngine(), randomMover, randomMover, "replay");
    const second = playGame(loadEngine(), randomMover, randomMover, "replay");
    assert.deepEqual(first.moves, second.moves);
});

test("the Elo formula gives about 76 % for a 200-point favourite", () => {
    assert.equal(Math.round(expectedScore(1200, 1000) * 100), 76);
});

test("the fit finds the rating difference behind a 76 % score", () => {
    const games = [];
    for (let number = 0; number < 1000; number++) {
        // 760 wins for "strong" against the anchor, 240 losses.
        games.push({ white: "strong", black: "anchor", score: number < 760 ? 1 : 0 });
    }
    const ratings = fitRatings(games, { name: "anchor", elo: 0 });
    assert.ok(Math.abs(ratings.strong.elo - 200) <= 2, "got " + ratings.strong.elo);
    assert.ok(ratings.strong.low < 200 && ratings.strong.high > 200);
    assert.equal(ratings.anchor.elo, 0);
});
