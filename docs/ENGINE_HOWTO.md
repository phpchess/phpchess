# Using another chess engine

phpChess ships with **GarboChess** (BSD-3-Clause), a small JavaScript engine that runs in the player's browser (feature AI-01, decision A47). This page explains how the computer opponent is built and what you would change to plug in a different engine. You do not need any of this to run phpChess.

## How the computer opponent works

1. The game page (`public/assets/js/engine/computer-opponent.js`) sees that it is the computer's turn.
2. It starts a **Web Worker**, `public/assets/js/engine/computer-player.worker.js`, which runs in the player's browser, so the server does no chess thinking.
3. The worker asks the engine to **score every legal move** (`scoreAllMoves` in `garbochess-engine.js`).
4. The **move chooser** (`move-chooser.js`) picks a human-like move from those scores: blurred judgement (noise), blind spots, threat blindness and giveaways, per player (decisions A56–A58).
5. The move goes to the server like any other move (`POST /games/{id}/computer-move`), and the server checks it (contract K1).

The random choices are seeded from the game id and move number, so the same game gives the same move on every device. Never use `Math.random()` or a time limit; use a fixed depth.

## What another engine must provide

Only `garbochess-engine.js` talks to GarboChess. A replacement file must offer the same functions to the worker:

| Function | What it returns |
|---|---|
| `loadPosition(fen)` | sets up the position |
| `scoreAllMoves(fen, depth, withoutReplies)` | every legal move, best first, as `{ move: "e2e4", centipawns, mateIn, isCapture, givesCheck, isGiveaway }` from the side to move; with `withoutReplies` true, scored without looking at the opponent's reply |
| `listLegalMoves(fen)` | every legal move in the same format (scores 0) |
| `depthForPosition(fen, depthSettings)` | the search depth for this position |
| `decideMove(fen, player, gameId, ply, movesSoFar)` | the full decision; copy it from `garbochess-engine.js`, it only calls the functions above, the book and the chooser |

`isGiveaway` means: the move hands over at least 1.5 pawns at once, and a check at least 3 moves deep confirms there is no gain for it (A48). `move-chooser.js` and `opening-book.js` work with any engine and stay as they are.

Then change the `importScripts(...)` list at the top of `computer-player.worker.js` to load your engine and your new file instead of GarboChess and `garbochess-engine.js`.

## Stockfish and licences

**Stockfish** is much stronger and also runs in browsers (WebAssembly builds), but it is licensed under **GPL-3.0**. phpChess keeps every third-party library under a licence compatible with its own and lists it in `docs/LIBRARIES.md` (decision A16). If you add Stockfish to your own install, you take on the GPL-3.0 terms for what you distribute, and you must keep the files local (no outside requests, A16). phpChess itself does not ship or support Stockfish (A47).

Also note: a stronger engine does not make better opponents for beginners. The players' strength comes from the move chooser and the strength table, which are tuned to real players of each rating (A55–A58).

## After changing the engine

The players' settings were tuned with GarboChess. With another engine, run the tuning again (development machine, Node.js):

1. `node tools/build-player-settings.mjs`
2. `node tools/human-profile.mjs play --fresh`, then `node tools/human-profile.mjs ours`: compare each rating's loss per move with the people's, and adjust `tools/calibration/strength-table.mjs`.
3. Run the tests: `node --test "tests/js/*.test.mjs"`.
