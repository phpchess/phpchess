/**
 * Calibration of the computer players (development only, runs overnight).
 *
 * Feature AI-05, decision A51. Plays the ladder of reference players
 * (tools/calibration/anchors.mjs) against each other, fits Elo ratings with a
 * 95 % range (tools/calibration/elo-fit.mjs), and writes the result to
 * docs/proofs/c3-2-calibration.md. Uses every processor core but two.
 *
 * Results are saved game by game in storage/calibration/results.jsonl, so a
 * stopped run simply continues where it was when started again.
 *
 * Usage:
 *   node tools/calibrate.mjs --estimate        time a few games and estimate the full run
 *   node tools/calibrate.mjs                   the full run (200 games per pairing)
 *   node tools/calibrate.mjs --games=20        a shorter run
 *   node tools/calibrate.mjs --threads=8       use 8 threads
 *   node tools/calibrate.mjs --report          only write the report from saved results
 */

import { Worker } from "node:worker_threads";
import { availableParallelism } from "node:os";
import { appendFileSync, existsSync, mkdirSync, readFileSync, writeFileSync } from "node:fs";
import { fileURLToPath } from "node:url";
import { dirname, join } from "node:path";
import { ANCHORS, FIXED_ANCHOR, anchorPairings } from "./calibration/anchors.mjs";
import { fitRatings } from "./calibration/elo-fit.mjs";

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
const threadCount = option("threads", Math.max(1, availableParallelism() - 2));

// --- The games to play ---

const playersByName = Object.fromEntries(ANCHORS.map((anchor) => [anchor.name, anchor]));

/** Every game of the run: each pairing, colours alternating. */
function allJobs() {
    const jobs = [];
    for (const [first, second] of anchorPairings()) {
        for (let number = 0; number < gamesPerPairing; number++) {
            const firstIsWhite = number % 2 === 0;
            jobs.push({
                white: playersByName[firstIsWhite ? first : second],
                black: playersByName[firstIsWhite ? second : first],
                gameId: first + "|" + second + "|" + number,
            });
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

// --- Running the games on several threads ---

function runJobs(jobs, onResult) {
    return new Promise((resolve) => {
        if (jobs.length === 0) {
            resolve();
            return;
        }
        let next = 0;
        let finished = 0;
        const workers = [];
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
            workers.push(worker);
        };
        for (let count = 0; count < Math.min(threadCount, jobs.length); count++) {
            startWorker();
        }
    });
}

// --- The report ---

function scoreForWhite(result) {
    return result === "1-0" ? 1 : result === "0-1" ? 0 : 0.5;
}

function writeReport(results) {
    const games = results.map((saved) => ({ white: saved.white, black: saved.black, score: scoreForWhite(saved.result) }));
    const ratings = fitRatings(games, FIXED_ANCHOR);
    const totalHours = results.reduce((sum, saved) => sum + saved.milliseconds, 0) / 3600000;

    const lines = [
        "# C3-2 calibration result",
        "",
        "Written by `node tools/calibrate.mjs` (AI-05, decision A51). Do not edit by hand.",
        "",
        "- **Anchor**: `" + FIXED_ANCHOR.name + "` is fixed at Elo " + FIXED_ANCHOR.elo + " (random legal moves).",
        "- **Games**: " + results.length + ", about " + totalHours.toFixed(1) + " hours of engine time.",
        "- **Range**: 95 %, from each player's own games; ratings far from the anchor are estimates (no human anchors, A51).",
        "",
        "| Player | Elo | 95 % range | Games |",
        "|---|---|---|---|",
    ];
    const ordered = Object.entries(ratings).sort((first, second) => first[1].elo - second[1].elo);
    for (const [name, rating] of ordered) {
        lines.push("| " + name + " | " + rating.elo + " | " + rating.low + " to " + rating.high + " | " + rating.games + " |");
    }

    // How the games ended, as a sanity check (too many length limits = players too passive).
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
    const done = new Set(savedResults().map((saved) => saved.gameId));
    const jobs = estimateOnly ? allJobs() : allJobs().filter((job) => !done.has(job.gameId));
    console.log(jobs.length + " games to play on " + threadCount + " threads.");

    const startedAt = Date.now();
    const timeByPairing = {};
    let count = 0;
    await runJobs(jobs, (result) => {
        count++;
        const pairing = result.gameId.split("|").slice(0, 2).join(" v ");
        timeByPairing[pairing] = (timeByPairing[pairing] || 0) + result.milliseconds;
        if (!estimateOnly) {
            appendFileSync(resultsFile, JSON.stringify(result) + "\n");
        }
        if (count % 50 === 0 || estimateOnly) {
            console.log(count + "/" + jobs.length + " " + result.gameId + " " + result.result + " (" + result.reason + ", " + result.moves.length + " plies, " + result.milliseconds + " ms)");
        }
    });

    if (estimateOnly) {
        // Engine time for the full run: each pairing's average game time x 200 games, shared over the threads.
        let fullMilliseconds = 0;
        for (const [pairing, milliseconds] of Object.entries(timeByPairing)) {
            const perGame = milliseconds / gamesPerPairing;
            fullMilliseconds += perGame * option("games", 200);
            console.log(pairing + ": " + (perGame / 1000).toFixed(1) + " s per game");
        }
        console.log("Estimated full run: " + (fullMilliseconds / threadCount / 3600000).toFixed(1) + " hours on " + threadCount + " threads.");
    } else {
        console.log("Finished in " + ((Date.now() - startedAt) / 60000).toFixed(1) + " minutes.");
        writeReport(savedResults());
    }
}
