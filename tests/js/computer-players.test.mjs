/**
 * Tests for the computer player list and their engine settings (BUILD_PLAN C3-2).
 *
 * Feature AI-03, decisions A48, A50 and A53. Run with: node --test "tests/js/*.test.mjs"
 * Checks the file itself and one quick decision per personality; plays no games.
 */

import { test } from "node:test";
import assert from "node:assert/strict";
import { readFileSync } from "node:fs";
import { loadEngine } from "../../tools/engine-in-node.mjs";
import { engineBlockFor } from "../../tools/build-player-settings.mjs";

const players = JSON.parse(readFileSync(new URL("../../src/Domain/Computer/computer-players.json", import.meta.url), "utf8"));
const START = "rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1";

test("61 players: the Wood Pusher and 12 of each of the five personalities", () => {
    assert.equal(players.length, 61);
    const counts = {};
    for (const player of players) {
        counts[player.personality] = (counts[player.personality] || 0) + 1;
    }
    assert.deepEqual(counts, { Random: 1, Conservative: 12, Aggressive: 12, Balanced: 12, Uneven: 12, Trader: 12 });
    assert.equal(new Set(players.map((player) => player.id)).size, 61);
});

test("the engine settings in the file match the strength table (run tools/build-player-settings.mjs)", () => {
    for (const player of players) {
        assert.deepEqual(player.engine, engineBlockFor(player), "player " + player.id);
    }
});

test("the sacrifice filter is skipped only below 500 (A48)", () => {
    for (const player of players.filter((candidate) => candidate.personality !== "Random")) {
        const skip = player.engine.normal.giveawaySkipChance;
        if (player.elo >= 500) {
            assert.equal(skip, 0, player.name);
        } else {
            assert.ok(skip > 0, player.name);
        }
    }
});

test("players from 1400 up are marked slow on phones, the others are not (A53)", () => {
    for (const player of players) {
        assert.equal(player.engine.slowOnPhones, player.elo >= 1400, player.name);
    }
});

test("an Uneven player plays low, normal and high games, the same for the same game id", () => {
    const engine = loadEngine();
    const uneven = players.find((player) => player.personality === "Uneven");
    const forms = new Set();
    for (let game = 0; game < 60; game++) {
        const settings = engine.settingsForGame(uneven.engine, "game-" + game);
        assert.equal(settings, engine.settingsForGame(uneven.engine, "game-" + game));
        forms.add(settings === uneven.engine.low ? "low" : settings === uneven.engine.high ? "high" : "normal");
    }
    assert.deepEqual([...forms].sort(), ["high", "low", "normal"]);
});

test("one player of each personality decides a legal first move", () => {
    const engine = loadEngine();
    const legal = engine.listLegalMoves(START).map((scored) => scored.move);
    for (const personality of ["Random", "Conservative", "Aggressive", "Balanced", "Uneven", "Trader"]) {
        const player = players.find((candidate) => candidate.personality === personality);
        const settings = engine.settingsForGame(player.engine, "game-1");
        const decision = engine.decideMove(START, settings, "game-1", 0, null);
        assert.ok(legal.includes(decision.move), personality + " played " + decision.move);
    }
});
