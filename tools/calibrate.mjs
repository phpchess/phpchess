/**
 * Calibration of the computer players (development only).
 *
 * Feature AI-05, decisions A51 and A53. Two parts:
 *   1. the ladder: the reference players (tools/calibration/anchors.mjs) play
 *      each other, 200 games per pairing;
 *   2. the computer players (src/Domain/Computer/computer-players.json) each
 *      play 60 games, 30 against each of the two anchors nearest their listed Elo.
 * Then it fits Elo ratings with a 95 % range (tools/calibration/elo-fit.mjs)
 * and writes docs/proofs/c3-2-calibration.md. Uses every processor core but
 * two, so the user starts it, overnight (A14, A51).
 *
 * Results are saved game by game in storage/calibration/results.jsonl, so a
 * stopped run simply continues where it was when started again.
 *
 * Usage:
 *   node tools/calibrate.mjs --estimate          time a few games and estimate the full run
 *   node tools/calibrate.mjs                     the full run
 *   node tools/calibrate.mjs --games=20          fewer ladder games per pairing
 *   node tools/calibrate.mjs --player-games=10   fewer games per computer player
 *   node tools/calibrate.mjs --threads=8         use 8 threads
 *   node tools/calibrate.mjs --report            only write the report from saved results
 */

import { Worker } from "node:worker_threads";
import { availableParallelism } from "node:os";
import { appendFileSync, existsSync, mkdirSync, readFileSync, writeFileSync } from "node:fs";
import { fileURLToPath } from "node:url";
import { dirname, join } from "node:path";
import { ANCHORS, FIXED_ANCHOR, anchorPairings } from "./calibration/anchors.mjs";
import { fitRatings } from "./calibration/elo-fit.mjs";
import { PLAYERS_FILE } from "./build-player-settings.mjs";

const projectFolder = join(dirname(fileURLToPath(import.meta.url)), "..");
const resultsFolder = join(projectFolder, "storage/calibration");
const resultsFile = join(resultsFolder, "results.jsonl");
const reportFile = join(projectFolder, "docs/proofs/c3-2-calibration.md");

// --- Command-line options ---

function option(name, fallback) {
    const found = process.argv.find((argument) => argument.startsWith("--" + name + "="));
    return found ? Number(found.split("=")[1]) : fallback;
}
const estimateOnly = process.argv.includes("--estimate");
const reportOnly = process.argv.includes("--report");
const gamesPerPairing = estimateOnly ? 2 : option("games", 200);
const gamesPerPlayer = estimateOnly ? 4 : option("player-games", 60);
const threadCount = option("threads", Math.max(1, availableParallelism() - 2));

// --- The sides ---

const anchorsByName = Object.fromEntries(ANCHORS.map((anchor) => [anchor.name, anchor]));

/** Every computer player as a calibration side: "#12 Henrik Solberg" with its engine block. */
function computerPlayerSides() {
    const players = JSON.parse(readFileSync(PLAYERS_FILE, "utf8"));
    return players.map((player) => ({
        name: "#" + player.id + " " + player.name,
        listedElo: player.elo,
        engineBlock: player.engine,
    }));
}

// --- The games to play ---

/** count games between two sides, colours alternating. */
function gamesBetween(first, second, count) {
    const jobs = [];
    for (let number = 0; number < count; number++) {
        const firstIsWhite = number % 2 === 0;
        jobs.push({
            white: firstIsWhite ? first : second,
            black: firstIsWhite ? second : first,
            gameId: first.name + "|" + second.name + "|" + number,
        });
    }
    return jobs;
}

/** Part 1: the ladder games. */
function ladderJobs() {
    return anchorPairings().flatMap(([first, second]) => gamesBetween(anchorsByName[first], anchorsByName[second], gamesPerPairing));
}

/**
 * Part 2: each computer player against the two anchors nearest its listed Elo.
 * anchorElo gives an anchor's Elo: measured if the ladder has been played, else its guess.
 */
function playerJobs(anchorElo) {
    const jobs = [];
    for (const side of computerPlayerSides()) {
        const distance = (anchor) => Math.abs(anchorElo(anchor) - side.listedElo);
        const nearest = [...ANCHORS].sort((first, second) => distance(first) - distance(second)).slice(0, 2);
        for (const anchor of nearest) {
            jobs.push(...gamesBetween(side, anchor, gamesPerPlayer / 2));
        }
    }
    return jobs;
}

/** The results saved so far, one per line. */
function savedResults() {
    if (!existsSync(resultsFile)) {
        return [];
    }
    return readFileSync(resultsFile, "utf8").split("\n").filter((line) => line.trim() !== "").map((line) => JSON.parse(line));
}

// --- Ratings ---

function scoreForWhite(result) {
    return result === "1-0" ? 1 : result === "0-1" ? 0 : 0.5;
}

function toFitGame(saved) {
    return { white: saved.white, black: saved.black, score: scoreForWhite(saved.result) };
}

/** The anchors' Elo: measured from the saved ladder games if any, else the guess. */
function anchorEloFrom(results) {
    const ladderGames = results.filter((saved) => anchorsByName[saved.white] && anchorsByName[saved.black]);
    const ratings = ladderGames.length > 0 ? fitRatings(ladderGames.map(toFitGame), FIXED_ANCHOR) : {};
    return (anchor) => (ratings[anchor.name] ? ratings[anchor.name].elo : anchor.guessElo);
}

// --- Running the games on several threads ---

function runJobs(jobs, onResult) {
    return new Promise((resolve) => {
        if (jobs.length === 0) {
            resolve();
            return;
        }
        let next = 0;
        let finished = 0;
        const startWorker = () => {
            const worker = new Worker(new URL("./calibration/game-worker.mjs", import.meta.url));
            worker.on("message", (result) => {
                onResult(result);
                finished++;
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
        };
        for (let count = 0; count < Math.min(threadCount, jobs.length); count++) {
            startWorker();
        }
    });
}

// --- The report ---

function writeReport(results) {
    const ratings = fitRatings(results.map(toFitGame), FIXED_ANCHOR);
    const totalHours = results.reduce((sum, saved) => sum + saved.milliseconds, 0) / 3600000;

    const lines = [
        "# C3-2 calibration result",
        "",
        "Written by `node tools/calibrate.mjs` (AI-05, decisions A51 and A53). Do not edit by hand.",
        "",
        "- **Anchor**: `" + FIXED_ANCHOR.name + "` is fixed at Elo " + FIXED_ANCHOR.elo + " (random legal moves).",
        "- **Games**: " + results.length + ", about " + totalHours.toFixed(1) + " hours of engine time.",
        "- **Range**: 95 %, from each player's own games; ratings far from the anchor are estimates (no human anchors, A51).",
        "",
        "## The ladder",
        "",
        "| Anchor | Elo | 95 % range | Games |",
        "|---|---|---|---|",
    ];
    for (const anchor of ANCHORS) {
        const rating = ratings[anchor.name];
        if (rating) {
            lines.push("| " + anchor.name + " | " + rating.elo + " | " + rating.low + " to " + rating.high + " | " + rating.games + " |");
        }
    }

    lines.push(
        "",
        "## The computer players",
        "",
        "Listed = the Elo in computer-players.json (never changed, A53); measured = this run.",
        "",
        "| Player | Listed | Measured | 95 % range | Games | Listed inside range |",
        "|---|---|---|---|---|---|",
    );
    for (const side of computerPlayerSides()) {
        const rating = ratings[side.name];
        if (rating) {
            const inside = side.listedElo >= rating.low && side.listedElo <= rating.high ? "yes" : "no";
            lines.push("| " + side.name + " | " + side.listedElo + " | " + rating.elo + " | " + rating.low + " to " + rating.high + " | " + rating.games + " | " + inside + " |");
        }
    }

    // How the games ended, as a sanity check (many length limits = players too passive).
    const reasons = {};
    for (const saved of results) {
        reasons[saved.reason] = (reasons[saved.reason] || 0) + 1;
    }
    lines.push("", "Game endings: " + Object.entries(reasons).map(([reason, count]) => reason + " " + count).join(", ") + ".", "");

    writeFileSync(reportFile, lines.join("\n"));
    console.log(lines.join("\n"));
}

// --- Main ---

if (reportOnly) {
    writeReport(savedResults());
} else {
    mkdirSync(resultsFolder, { recursive: true });
    const startedAt = Date.now();
    const timeByPairing = {};
    let count = 0;

    const onResult = (result) => {
        count++;
        const pairing = result.gameId.split("|").slice(0, 2).join(" v ");
        timeByPairing[pairing] = (timeByPairing[pairing] || 0) + result.milliseconds;
        if (!estimateOnly) {
            appendFileSync(resultsFile, JSON.stringify(result) + "\n");
        }
        if (count % 50 === 0 || estimateOnly) {
            console.log(count + " " + result.gameId + " " + result.result + " (" + result.reason + ", " + result.moves.length + " plies, " + result.milliseconds + " ms)");
        }
    };

    /** The jobs not saved yet (an estimate plays them all and saves nothing). */
    const notDone = (jobs) => {
        if (estimateOnly) {
            return jobs;
        }
        const done = new Set(savedResults().map((saved) => saved.gameId));
        return jobs.filter((job) => !done.has(job.gameId));
    };

    // Part 1, then part 2, which uses the ladder measured in part 1.
    const ladder = notDone(ladderJobs());
    console.log("Part 1, the ladder: " + ladder.length + " games on " + threadCount + " threads.");
    await runJobs(ladder, onResult);
    const players = notDone(playerJobs(anchorEloFrom(savedResults())));
    console.log("Part 2, the computer players: " + players.length + " games.");
    await runJobs(players, onResult);

    if (estimateOnly) {
        // Engine time for the full run: each pairing's average game time x its full number of games, shared over the threads.
        let fullMilliseconds = 0;
        for (const [pairing, milliseconds] of Object.entries(timeByPairing)) {
            const isPlayerPairing = pairing.startsWith("#");
            const gamesPlayed = isPlayerPairing ? gamesPerPlayer / 2 : gamesPerPairing;
            const fullGames = isPlayerPairing ? option("player-games", 60) / 2 : option("games", 200);
            fullMilliseconds += (milliseconds / gamesPlayed) * fullGames;
        }
        console.log("Estimated full run: " + (fullMilliseconds / threadCount / 3600000).toFixed(1) + " hours on " + threadCount + " threads.");
    } else {
        console.log("Finished in " + ((Date.now() - startedAt) / 60000).toFixed(1) + " minutes.");
        writeReport(savedResults());
    }
}
