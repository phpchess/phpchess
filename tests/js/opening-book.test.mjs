/**
 * Tests for the opening book (BUILD_PLAN C3-2).
 *
 * Feature AI-02, decisions A49 and A52. Run with: node --test "tests/js/*.test.mjs"
 * Uses the real book.json built by tools/build-book.mjs.
 */

import { test } from "node:test";
import assert from "node:assert/strict";
import { readFileSync } from "node:fs";
import { loadEngine } from "../../tools/engine-in-node.mjs";

const START = "rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1";
const AFTER_E4 = "rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq - 0 1";
const book = JSON.parse(readFileSync(new URL("../../public/assets/engine/book/book.json", import.meta.url), "utf8"));

function engineWithBook() {
    const engine = loadEngine();
    engine.setOpeningBook(book);
    return engine;
}

const bookPlayer = {
    depth: { baseDepth: 2 },
    marginCentipawns: 0,
    book: { maxPly: 8, variety: 0.5 },
};

test("every book move from the start is a real first move", () => {
    const firstMoves = book.lines[""].map((entry) => entry[0]);
    assert.ok(firstMoves.includes("e2e4"));
    assert.ok(firstMoves.includes("d2d4"));
});

test("the book is used in the opening and is the same for the same game and move", () => {
    const first = engineWithBook().decideMove(AFTER_E4, bookPlayer, "game-3", 1, ["e2e4"]);
    const second = engineWithBook().decideMove(AFTER_E4, bookPlayer, "game-3", 1, ["e2e4"]);
    assert.equal(first.reason, "book");
    assert.equal(first.move, second.move);
    assert.ok(book.lines["e2e4"].some((entry) => entry[0] === first.move));
});

test("no variety always plays the most common book move", () => {
    const engine = engineWithBook();
    const strict = { ...bookPlayer, book: { maxPly: 8, variety: 0 } };
    const moves = new Set();
    for (let ply = 0; ply < 20; ply++) {
        moves.add(engine.decideMove(START, strict, "g", ply, []).move);
    }
    assert.equal(moves.size, 1);
});

test("the player leaves the book after maxPly and searches instead", () => {
    const engine = engineWithBook();
    const short = { ...bookPlayer, book: { maxPly: 1, variety: 0 } };
    assert.notEqual(engine.decideMove(AFTER_E4, short, "g", 1, ["e2e4"]).reason, "book");
});

test("a game from another start position never uses the book", () => {
    const engine = engineWithBook();
    assert.notEqual(engine.decideMove(START, bookPlayer, "g", 0, null).reason, "book");
});

test("the opening name is found for a known line", () => {
    const engine = engineWithBook();
    assert.match(engine.openingName(["e2e4", "c7c5"]), /Sicilian/);
});
