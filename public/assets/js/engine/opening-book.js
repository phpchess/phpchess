/**
 * Opening book: plays known opening moves at the start of a game.
 *
 * Feature AI-02 (FEATURES.md), decisions A49 and A52 (DECISIONS.md).
 *
 * The book is public/assets/engine/book/book.json, built by
 * tools/build-book.mjs from the Lichess opening list (CC0). It maps the moves
 * played so far to the moves the book knows next:
 *   { "lines": { "": [["e2e4", 1200], ["d2d4", 980], ...],
 *                "e2e4": [["c7c5", 300], ...], ... },
 *     "names": { "e2e4 c7c5": "Sicilian Defense", ... } }
 * The number after each move is how many named opening lines continue that way.
 *
 * Each player has its own book settings:
 *   maxPly    follows the book for at most this many half-moves (0 = no book);
 *   variety   0 = always the most common continuation, 1 = any book move with
 *             equal chance; values between mix the two.
 *
 * Loaded with importScripts() in the engine worker and by the Node tests, so it
 * uses plain global functions. The random number comes from move-chooser.js,
 * so the book move is the same on every device too.
 */

"use strict";

// The book, once loaded (null until then, or when there is no book).
let openingBook = null;

/** Sets the book (the parsed contents of book.json). */
function setOpeningBook(book) {
    openingBook = book;
}

/**
 * Chooses a book move after the given moves ("e2e4", "e7e5", ...), or returns
 * null when the book has nothing or the player has left the book.
 */
function chooseBookMove(movesSoFar, bookSettings, random) {
    if (openingBook === null || !bookSettings || movesSoFar.length >= (bookSettings.maxPly || 0)) {
        return null;
    }
    const continuations = openingBook.lines[movesSoFar.join(" ")];
    if (!continuations || continuations.length === 0) {
        return null;
    }

    // Each move's chance: a mix of "how common it is" and "all equal".
    const variety = Math.min(Math.max(bookSettings.variety || 0, 0), 1);
    const totalCount = continuations.reduce((sum, entry) => sum + entry[1], 0);
    const weights = continuations.map((entry) => (1 - variety) * (entry[1] / totalCount) + variety / continuations.length);

    // With no variety at all, the most common move is always played.
    if (variety === 0) {
        return mostCommon(continuations);
    }
    let ticket = random();
    for (let index = 0; index < continuations.length; index++) {
        ticket -= weights[index];
        if (ticket < 0) {
            return continuations[index][0];
        }
    }
    return continuations[continuations.length - 1][0];
}

/** The book move with the highest count (the first one listed on a tie). */
function mostCommon(continuations) {
    let best = continuations[0];
    for (const entry of continuations) {
        if (entry[1] > best[1]) {
            best = entry;
        }
    }
    return best[0];
}

/** The name of the opening reached by these moves, or the nearest earlier one. */
function openingName(movesSoFar) {
    if (openingBook === null) {
        return null;
    }
    for (let length = movesSoFar.length; length > 0; length--) {
        const name = openingBook.names[movesSoFar.slice(0, length).join(" ")];
        if (name) {
            return name;
        }
    }
    return null;
}
