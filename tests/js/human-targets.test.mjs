/**
 * Tests for the people's targets used to tune the computer players (BUILD_PLAN C3-2).
 *
 * Feature AI-05, decisions A55 and A56. Run with: node --test "tests/js/*.test.mjs"
 * Uses a small made-up people's profile, so no games are judged.
 */

import { test } from "node:test";
import assert from "node:assert/strict";
import { peopleAt, playsLike } from "../../tools/calibration/human-targets.mjs";

// A made-up profile: every 100 points lose 10 centipawns less per move.
const people = {};
for (let band = 600; band <= 2100; band += 100) {
    const loss = 100 - (band - 600) / 10;
    people[band] = { averageLoss: loss, mistakes: loss / 5, blunders: loss / 10, giveaways: loss / 20 };
}

test("a band's target is the average of it and its neighbours", () => {
    assert.equal(peopleAt(people, 1000).averageLoss, 60);
    assert.equal(peopleAt(people, 2100).averageLoss, -45); // 2200 does not exist: 2000 and 2100 only
});

test("below 600 the 600-1000 trend continues downwards", () => {
    assert.equal(Math.round(peopleAt(people, 400).averageLoss), 120);
    assert.equal(Math.round(peopleAt(people, 400).giveaways), 6);
});

test("plays like: the rating whose people lose as much per move", () => {
    assert.equal(playsLike(people, 60), 1000);
    assert.equal(playsLike(people, 55), 1050);
    assert.equal(playsLike(people, 500), null);
});
