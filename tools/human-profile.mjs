/**
 * How people and our computer players make mistakes, per rating (development only).
 *
 * Feature AI-05, decisions A55 and A56. GarboChess at depth 5 is the one judge for both:
 * for every move it measures how much worse the move is than the best move.
 * Per rating band it reports:
 *   - average loss per move (centipawns, each loss capped at 1000);
 *   - mistakes: share of moves losing 100 centipawns (1 pawn) or more;
 *   - blunders: share of moves losing 300 or more;
 *   - giveaways: share of moves that hand material over for nothing (A48).
 * The strength table (tools/calibration/strength-table.mjs) is then tuned so
 * each computer player's numbers match the people of its rating.
 *
 * Two sources:
 *   people   Lichess games with known ratings (CC0): by default
 *            ChessTutor/ExternalData/lichess/lichess_2022-12_scored_1000_per_band.pgn,
 *            60 games per 100-point band (the White player's band); every
 *            move is counted in its own player's band.
 *   ours     the computer players' tuning games (storage/calibration/tuning-games.jsonl,
 *            written by "play"); before those exist, their calibration games
 *            (results.jsonl). Only the computer player's own moves count, in
 *            its listed band.
 * The first 8 half-moves (the opening book) are left out for both.
 *
 * Uses every processor core but two, so the user starts it (A51).
 *
 * Usage:
 *   node tools/human-profile.mjs --estimate          time 2 games, estimate the full run, write nothing
 *   node tools/human-profile.mjs people              measure the people  -> storage/calibration/profile-people.json
 *   node tools/human-profile.mjs play                the tuning games: neighbours in rating paired,
 *                                                    20 games per pair   -> storage/calibration/tuning-games.jsonl
 *   node tools/human-profile.mjs ours                measure our players -> storage/calibration/profile-ours.json
 *   node tools/human-profile.mjs report              compare both        -> docs/proofs/c3-2-human-profile.md
 * A tuning run (A56, A57) is: node tools/build-player-settings.mjs, then play,
 * then ours; ours also prints each strength row's loss next to the people's.
 * play --fresh starts a new run: the earlier games are kept as tuning-games-<time>.jsonl;
 * without it, play continues a stopped run.
 * Options: --games=60 (per band; "play" and "ours" use a third: 20 per player),
 *          --threads=8, --pgn=<file>
 */

import { Worker, isMainThread, parentPort, workerData } from "node:worker_threads";
import { availableParallelism } from "node:os";
import { appendFileSync, existsSync, readFileSync, renameSync, writeFileSync, mkdirSync } from "node:fs";
import { fileURLToPath } from "node:url";
import { dirname, join } from "node:path";
import { loadEngine } from "./engine-in-node.mjs";
import { STRENGTH_ROWS, strengthRowFor } from "./calibration/strength-table.mjs";
import { peopleAt, playsLike } from "./calibration/human-targets.mjs";

const projectFolder = join(dirname(fileURLToPath(import.meta.url)), "..");
const resultsFolder = join(projectFolder, "storage/calibration");
const START = "rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1";
const tuningFile = join(resultsFolder, "tuning-games.jsonl");

// --- The judge's rules ---

const JUDGE_DEPTH = 5;
const BOOK_PLIES = 8;
const LOSS_CAP = 1000;
const MISTAKE_LOSS = 100;
const BLUNDER_LOSS = 300;

// =====================================================================
// Worker thread: judges the moves of the games it is given
// =====================================================================

if (!isMainThread) {
    const engine = loadEngine();

    /** "Nf3" -> the engine's move in the current position, or null. */
    const findSanMove = (san) => {
        const plain = (text) => text.replace(/[+#!?]/g, "");
        const legal = engine.GenerateValidMoves();
        return legal.find((move) => plain(engine.GetMoveSAN(move, legal)) === plain(san)) ?? null;
    };

    /**
     * Judges one game. game: { sans | ucis, bandOfPly: [band per half-move, or null to skip] },
     * for our games also rowOfPly (the strength row, or null) and choices (why
     * each move was chosen, from the tuning games).
     * Returns one record per judged move: { band, row, choice, loss, giveaway }.
     */
    const judgeGame = (game) => {
        const records = [];
        let fen = START;
        const moveCount = game.sans ? game.sans.length : game.ucis.length;
        for (let ply = 0; ply < moveCount; ply++) {
            engine.loadPosition(fen);
            const move = game.sans
                ? findSanMove(game.sans[ply])
                : engine.GenerateValidMoves().find((candidate) => engine.FormatMove(candidate) === game.ucis[ply]) ?? null;
            if (move === null) {
                break; // a move we can not read: stop this game, keep what was judged
            }
            const playedText = engine.FormatMove(move);
            const band = game.bandOfPly[ply];

            if (ply >= BOOK_PLIES && band !== null) {
                const scores = engine.scoreAllMoves(fen, JUDGE_DEPTH);
                const best = Math.min(LOSS_CAP, Math.max(-LOSS_CAP, engine.comparableScore(scores[0])));
                const played = scores.find((scored) => scored.move === playedText);
                if (played) {
                    const playedScore = Math.min(LOSS_CAP, Math.max(-LOSS_CAP, engine.comparableScore(played)));
                    records.push({
                        band: band,
                        row: game.rowOfPly ? game.rowOfPly[ply] : null,
                        choice: game.choices ? game.choices[ply] : null,
                        loss: Math.max(0, best - playedScore),
                        giveaway: played.isGiveaway,
                    });
                }
            }

            engine.loadPosition(fen);
            engine.MakeMove(move);
            fen = engine.GetFen() + " 0 1";
        }
        return records;
    };

    parentPort.on("message", (game) => {
        parentPort.postMessage(judgeGame(game));
    });
}

// =====================================================================
// Main thread
// =====================================================================

/** --name=value from the command line, or the fallback. */
function option(name, fallback) {
    const found = process.argv.find((argument) => argument.startsWith("--" + name + "="));
    return found ? found.slice(name.length + 3) : fallback;
}

/** The 100-point band of a rating: 1234 -> 1200. */
function bandOf(rating) {
    return Math.floor(rating / 100) * 100;
}

// --- Reading the people's games (PGN) ---

/** Splits a PGN file into games: { whiteElo, blackElo, sans }. */
function readPgnGames(file) {
    const text = readFileSync(file, "utf8");
    const games = [];
    for (const chunk of text.split(/\n(?=\[Event )/)) {
        const whiteElo = Number((chunk.match(/\[WhiteElo "(\d+)"\]/) || [])[1]);
        const blackElo = Number((chunk.match(/\[BlackElo "(\d+)"\]/) || [])[1]);
        const moveText = chunk.split(/\n\n/).slice(1).join(" ");
        const sans = moveText
            .replace(/\{[^}]*\}/g, " ")           // comments: evaluations and clocks
            .replace(/\d+\.(\.\.)?/g, " ")        // move numbers "12." and "12..."
            .split(/\s+/)
            .filter((word) => word !== "" && !/^(1-0|0-1|1\/2-1\/2|\*)$/.test(word));
        if (whiteElo > 0 && blackElo > 0 && sans.length > 0) {
            games.push({ whiteElo: whiteElo, blackElo: blackElo, sans: sans });
        }
    }
    return games;
}

/** gamesPerBand people's games per White band; each move counts in its own player's band. */
function peopleJobs(gamesPerBand) {
    const pgn = option("pgn", "C:/Users/chriz/CBPROJS/ChessTutor/ExternalData/lichess/lichess_2022-12_scored_1000_per_band.pgn");
    const taken = {};
    const jobs = [];
    for (const game of readPgnGames(pgn)) {
        const band = bandOf(game.whiteElo);
        taken[band] = (taken[band] || 0) + 1;
        if (taken[band] > gamesPerBand) {
            continue;
        }
        const bandOfPly = game.sans.map((san, ply) => bandOf(ply % 2 === 0 ? game.whiteElo : game.blackElo));
        jobs.push({ sans: game.sans, bandOfPly: bandOfPly });
    }
    return jobs;
}

// --- Reading our players' games ---

/** The computer players, from src/Domain/Computer/computer-players.json. */
function readPlayers() {
    return JSON.parse(readFileSync(join(projectFolder, "src/Domain/Computer/computer-players.json"), "utf8"));
}

/** A player's name in the game files: "#12 Name". */
function gameName(player) {
    return "#" + player.id + " " + player.name;
}

/**
 * gamesPerPlayer games per computer player; only a player's own moves count,
 * in its listed band. From the tuning games when they exist, otherwise from
 * the calibration games. In a tuning game both players are measured (each
 * game counts for both); in a calibration game only the computer player (its
 * opponent there is an anchor).
 * Uneven players and the Wood Pusher get no row: an Uneven player's games
 * swing above and below its row (A53), so they stay out of the row check
 * (they are still in the report).
 */
function ourJobs(gamesPerPlayer) {
    const players = readPlayers();
    const listedElo = Object.fromEntries(players.map((player) => [gameName(player), player.elo]));
    const rowOf = Object.fromEntries(players.map((player) => [
        gameName(player),
        player.personality === "Uneven" || player.id === 0 ? null : strengthRowFor(player.elo).elo,
    ]));
    const tuning = existsSync(tuningFile);
    const resultsFile = tuning ? tuningFile : join(resultsFolder, "results.jsonl");
    const lines = existsSync(resultsFile) ? readFileSync(resultsFile, "utf8").split("\n").filter((line) => line.trim() !== "") : [];
    const taken = {};
    const jobs = [];
    for (const saved of lines.map((line) => JSON.parse(line))) {
        // Which sides of this game are measured: [name, parity], parity 0 = White.
        const sides = [[saved.white, 0], [saved.black, 1]].filter(([name]) => name in listedElo);
        const measured = sides.filter(([name]) => {
            taken[name] = (taken[name] || 0) + 1;
            return taken[name] <= gamesPerPlayer;
        });
        if (measured.length === 0) {
            continue;
        }
        const sideOfPly = (ply) => measured.find(([, parity]) => ply % 2 === parity);
        const bandOfPly = saved.moves.map((uci, ply) => (sideOfPly(ply) ? bandOf(listedElo[sideOfPly(ply)[0]]) : null));
        const rowOfPly = saved.moves.map((uci, ply) => (sideOfPly(ply) ? rowOf[sideOfPly(ply)[0]] : null));
        jobs.push({ ucis: saved.moves, bandOfPly: bandOfPly, rowOfPly: rowOfPly, choices: saved.choices || null });
    }
    return jobs;
}

// --- The tuning games (A56) ---

/**
 * The tuning games: the computer players (not the Wood Pusher) sorted by
 * rating and paired with their neighbour (1st with 2nd, 3rd with 4th, ...).
 * Each pair plays gamesPerPlayer games, colours alternating; both players are
 * measured in every game, so 60 players need only 30 x 20 = 600 games.
 * Game id "tune|<first player>|<second player>|<number>".
 */
function tuningJobs(gamesPerPlayer) {
    const players = readPlayers().filter((player) => player.id !== 0);
    players.sort((first, second) => first.elo - second.elo || first.id - second.id);
    const jobs = [];
    for (let index = 0; index + 1 < players.length; index += 2) {
        const first = { name: gameName(players[index]), engineBlock: players[index].engine };
        const second = { name: gameName(players[index + 1]), engineBlock: players[index + 1].engine };
        for (let number = 1; number <= gamesPerPlayer; number++) {
            const firstIsWhite = number % 2 === 1;
            jobs.push({
                white: firstIsWhite ? first : second,
                black: firstIsWhite ? second : first,
                gameId: "tune|" + first.name + "|" + second.name + "|" + number,
            });
        }
    }
    return jobs;
}

/**
 * Plays the tuning games on several threads (tools/calibration/game-worker.mjs)
 * and saves each one as a line of tuning-games.jsonl as soon as it ends, so a
 * stopped run continues where it stopped.
 */
function playTuningGames(gamesPerPlayer, threadCount) {
    const savedLines = existsSync(tuningFile) ? readFileSync(tuningFile, "utf8").split("\n").filter((line) => line.trim() !== "") : [];
    const done = new Set(savedLines.map((line) => JSON.parse(line).gameId));
    const jobs = tuningJobs(gamesPerPlayer).filter((job) => !done.has(job.gameId));
    console.log(jobs.length + " tuning games to play (" + done.size + " already saved).");
    return new Promise((resolve) => {
        if (jobs.length === 0) {
            resolve();
            return;
        }
        let next = 0;
        let finished = 0;
        for (let count = 0; count < Math.min(threadCount, jobs.length); count++) {
            const worker = new Worker(join(projectFolder, "tools/calibration/game-worker.mjs"));
            worker.on("message", (result) => {
                appendFileSync(tuningFile, JSON.stringify(result) + "\n");
                finished++;
                if (finished % 50 === 0) {
                    console.log(finished + " / " + jobs.length + " tuning games played");
                }
                if (next < jobs.length) {
                    worker.postMessage(jobs[next++]);
                } else {
                    worker.terminate();
                }
                if (finished === jobs.length) {
                    resolve();
                }
            });
            worker.postMessage(jobs[next++]);
        }
    });
}

// --- Running jobs on several threads ---

function judgeAll(jobs, threadCount) {
    return new Promise((resolve) => {
        const records = [];
        if (jobs.length === 0) {
            resolve(records);
            return;
        }
        let next = 0;
        let finished = 0;
        for (let count = 0; count < Math.min(threadCount, jobs.length); count++) {
            const worker = new Worker(fileURLToPath(import.meta.url));
            worker.on("message", (gameRecords) => {
                records.push(...gameRecords);
                finished++;
                if (finished % 50 === 0) {
                    console.log(finished + " / " + jobs.length + " games judged");
                }
                if (next < jobs.length) {
                    worker.postMessage(jobs[next++]);
                } else {
                    worker.terminate();
                }
                if (finished === jobs.length) {
                    resolve(records);
                }
            });
            worker.postMessage(jobs[next++]);
        }
    });
}

// --- Summing up per band ---

/**
 * { group: { moves, averageLoss, mistakes, blunders, giveaways } }, shares in
 * per cent. groupOf picks the group of a record (by default its band);
 * records whose group is null are left out.
 */
function profileOf(records, groupOf = (record) => record.band) {
    const bands = {};
    for (const record of records) {
        const group = groupOf(record);
        if (group === null || group === undefined) {
            continue;
        }
        const sum = bands[group] ||= { moves: 0, loss: 0, mistakes: 0, blunders: 0, giveaways: 0 };
        sum.moves++;
        sum.loss += record.loss;
        sum.mistakes += record.loss >= MISTAKE_LOSS ? 1 : 0;
        sum.blunders += record.loss >= BLUNDER_LOSS ? 1 : 0;
        sum.giveaways += record.giveaway ? 1 : 0;
    }
    const profile = {};
    for (const [band, sum] of Object.entries(bands)) {
        profile[band] = {
            moves: sum.moves,
            averageLoss: Math.round(sum.loss / sum.moves),
            mistakes: Number((100 * sum.mistakes / sum.moves).toFixed(1)),
            blunders: Number((100 * sum.blunders / sum.moves).toFixed(1)),
            giveaways: Number((100 * sum.giveaways / sum.moves).toFixed(1)),
        };
    }
    return profile;
}

// --- Each strength row against the people (A57) ---

/**
 * Prints, per strength row, our players' average loss per move next to the
 * people's of that rating, and how often the blind spots and giveaways came
 * up (from the move-chooser's reasons). Shows which rows need more or less
 * noise or blind spots.
 */
function printRowCheck(records) {
    const people = JSON.parse(readFileSync(join(resultsFolder, "profile-people.json"), "utf8"));
    const measuredByRow = profileOf(records, (record) => record.row);
    const lines = ["Row  | our loss | people's loss | blind spots | threats unseen | giveaways (ours / people)"];
    for (const row of STRENGTH_ROWS) {
        const measured = measuredByRow[row.elo];
        if (!measured) {
            continue;
        }
        const target = peopleAt(people, row.elo);
        const rowRecords = records.filter((record) => record.row === row.elo);
        const blindSpots = rowRecords.filter((record) => record.choice !== null && record.choice.includes("blind spot")).length;
        const threatsUnseen = rowRecords.filter((record) => record.choice !== null && record.choice.startsWith("threats unseen")).length;
        lines.push(row.elo + " | " + measured.averageLoss + " | " + Math.round(target.averageLoss)
            + " | " + (100 * blindSpots / rowRecords.length).toFixed(1) + " %"
            + " | " + (100 * threatsUnseen / rowRecords.length).toFixed(1) + " %"
            + " | " + measured.giveaways + " % / " + target.giveaways.toFixed(1) + " %");
    }
    console.log(lines.join("\n"));
}

// --- Upsets in the tuning games (for information only) ---

// A pair is an upset when the lower-rated player scores at least this share.
const UPSET_SHARE = 0.7;

/**
 * Report lines listing the tuning pairs in which the lower-rated player scored
 * clearly better (at least 70 %). Information only: nothing is re-paired and
 * no listed rating changes (A53).
 */
function upsetLines() {
    if (!existsSync(tuningFile)) {
        return [];
    }
    const listedElo = Object.fromEntries(readPlayers().map((player) => [gameName(player), player.elo]));
    const pairs = {};
    for (const line of readFileSync(tuningFile, "utf8").split("\n").filter((text) => text.trim() !== "")) {
        const game = JSON.parse(line);
        const [, first, second] = game.gameId.split("|");
        const pair = pairs[first + "|" + second] ||= { first: first, second: second, games: 0, firstPoints: 0 };
        const whitePoints = game.result === "1-0" ? 1 : game.result === "0-1" ? 0 : 0.5;
        pair.games++;
        pair.firstPoints += game.white === first ? whitePoints : 1 - whitePoints;
    }
    const lines = ["## Upsets in the tuning games (information only)", ""];
    for (const pair of Object.values(pairs)) {
        const firstIsLower = listedElo[pair.first] < listedElo[pair.second];
        const lowerPoints = firstIsLower ? pair.firstPoints : pair.games - pair.firstPoints;
        if (listedElo[pair.first] !== listedElo[pair.second] && lowerPoints >= UPSET_SHARE * pair.games) {
            const lower = firstIsLower ? pair.first : pair.second;
            const higher = firstIsLower ? pair.second : pair.first;
            lines.push("- " + lower + " (" + listedElo[lower] + ") scored " + lowerPoints + " / " + pair.games
                + " against " + higher + " (" + listedElo[higher] + ").");
        }
    }
    if (lines.length === 2) {
        lines.push("None.");
    }
    return lines;
}

// --- The report ---

function writeReport() {
    const read = (name) => {
        const file = join(resultsFolder, name);
        return existsSync(file) ? JSON.parse(readFileSync(file, "utf8")) : {};
    };
    const people = read("profile-people.json");
    const ours = read("profile-ours.json");
    const bands = [...new Set([...Object.keys(people), ...Object.keys(ours)])].map(Number).sort((first, second) => first - second);
    const cell = (profile) => (profile ? profile.averageLoss + " · " + profile.mistakes + " % · " + profile.blunders + " % · " + profile.giveaways + " %" : "–");

    const lines = [
        "# C3-2 human profile: people and our computer players",
        "",
        "Written by `node tools/human-profile.mjs report` (AI-05, decision A55). Do not edit by hand.",
        "",
        "- **Judge**: GarboChess depth " + JUDGE_DEPTH + " for both; the first " + BOOK_PLIES + " half-moves are left out.",
        "- **People**: Lichess blitz games, December 2022 (CC0).",
        "- **Each cell**: average loss per move (centipawns) · mistakes (≥ 1 pawn) · blunders (≥ 3 pawns) · giveaways.",
        "",
        "- **Plays like**: the rating of the people who lose as much per move as our players of that band",
        "  (from the smoothed people's curve; \"–\" when it is outside 600–2100).",
        "",
        "| Band | People | Our players | Our players play like |",
        "|---|---|---|---|",
    ];
    for (const band of bands) {
        const likeRating = ours[band] && Object.keys(people).length > 0 ? playsLike(people, ours[band].averageLoss) : null;
        lines.push("| " + band + " | " + cell(people[band]) + " | " + cell(ours[band]) + " | " + (likeRating === null ? "–" : likeRating) + " |");
    }
    lines.push("", ...upsetLines(), "");
    writeFileSync(join(projectFolder, "docs/proofs/c3-2-human-profile.md"), lines.join("\n"));
    console.log(lines.join("\n"));
}

// --- Main ---

if (isMainThread) {
    const threadCount = Number(option("threads", String(Math.max(1, availableParallelism() - 2))));
    const gamesEach = Number(option("games", "60"));
    const what = process.argv.slice(2).find((argument) => !argument.startsWith("--")) || "";
    mkdirSync(resultsFolder, { recursive: true });

    if (process.argv.includes("--estimate")) {
        // Time 2 games on one thread, then scale to the full run on all threads.
        const sample = peopleJobs(gamesEach).slice(0, 2);
        const startedAt = Date.now();
        const records = await judgeAll(sample, 1);
        const secondsPerMove = (Date.now() - startedAt) / 1000 / Math.max(1, records.length);
        const peopleMoves = peopleJobs(gamesEach).reduce((sum, job) => sum + job.bandOfPly.filter((band, ply) => ply >= BOOK_PLIES && band !== null).length, 0);
        const ourMoves = ourJobs(gamesEach / 3).reduce((sum, job) => sum + job.bandOfPly.filter((band, ply) => ply >= BOOK_PLIES && band !== null).length, 0);
        const minutes = (moves) => (moves * secondsPerMove / threadCount / 60).toFixed(0);
        console.log("Judging takes about " + (secondsPerMove * 1000).toFixed(0) + " ms per move on one thread.");
        console.log("people: " + peopleMoves + " moves, about " + minutes(peopleMoves) + " minutes on " + threadCount + " threads.");
        console.log("ours (so far): " + ourMoves + " moves, about " + minutes(ourMoves) + " minutes on " + threadCount + " threads.");
    } else if (what === "people") {
        const records = await judgeAll(peopleJobs(gamesEach), threadCount);
        writeFileSync(join(resultsFolder, "profile-people.json"), JSON.stringify(profileOf(records), null, 2) + "\n");
        writeReport();
    } else if (what === "play") {
        if (process.argv.includes("--fresh") && existsSync(tuningFile)) {
            renameSync(tuningFile, tuningFile.replace(".jsonl", "-" + Date.now() + ".jsonl"));
        }
        await playTuningGames(gamesEach / 3, threadCount);
    } else if (what === "ours") {
        const records = await judgeAll(ourJobs(gamesEach / 3), threadCount);
        writeFileSync(join(resultsFolder, "profile-ours.json"), JSON.stringify(profileOf(records), null, 2) + "\n");
        writeReport();
        printRowCheck(records);
    } else if (what === "report") {
        writeReport();
    } else {
        console.log("Usage: node tools/human-profile.mjs --estimate | people | play | ours | report   (see the top of this file)");
    }
}
