/**
 * Tests for the game page's news plan (B23: no flicker during live play).
 *
 * Run with: node --test "tests/js/*.test.mjs"
 */

import { test } from "node:test";
import assert from "node:assert/strict";
import { planNews, boardShows } from "../../public/assets/js/board/news-plan.js";

const running = {
    state: "running", ply: 12, draw_offered_by: null, rematch_offered_by: null,
    pace_offer: null, time_control: "5+3",
};

test("a short 'unchanged' answer changes nothing", () => {
    assert.deepEqual(planNews(running, { unchanged: true }), { moveBoard: false, swapParts: false, gameEnded: false, reloadPage: false });
});

test("news without a move leaves the board alone and never reloads", () => {
    const plan = planNews(running, { ...running, opponent_idle_seconds: 3 });
    assert.equal(plan.moveBoard, false);
    assert.equal(plan.swapParts, false);
    assert.equal(plan.reloadPage, false);
});

test("an agreed new pace is the one case that reloads", () => {
    assert.equal(planNews(running, { ...running, time_control: null }).reloadPage, true);
});

test("a draw offer swaps the offer parts but does not touch the board", () => {
    const plan = planNews(running, { ...running, draw_offered_by: 7 });
    assert.equal(plan.moveBoard, false);
    assert.equal(plan.swapParts, true);
});

test("a new move moves the board", () => {
    assert.equal(planNews(running, { ...running, ply: 13 }).moveBoard, true);
});

test("the first move removes the abort button", () => {
    assert.equal(planNews({ ...running, ply: 0 }, { ...running, ply: 1 }).swapParts, true);
});

test("the game's end swaps the parts and stops polling", () => {
    const plan = planNews(running, { ...running, ply: 13, state: "finished" });
    assert.equal(plan.gameEnded, true);
    assert.equal(plan.swapParts, true);
});

test("the board already showing the position is recognised", () => {
    const fen = "rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq e3 0 1";
    assert.equal(boardShows("rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR", fen), true);
    assert.equal(boardShows("rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR", fen), false);
});
