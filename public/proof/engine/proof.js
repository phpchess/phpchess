/**
 * Engine proof page script (chunk C0-2, temporary; removed in C3-2).
 *
 * Sends each test position to the candidate-move scorer worker at depths 1-6,
 * waits for the answer, and adds a row per run: time, number of moves, the best
 * and worst moves, and whether the known answer was found. The summary line
 * says how many checks passed and the slowest depth-4 and depth-6 times.
 */

"use strict";

// --- Test positions with known answers ---

// "expectBest" is the move that must come out on top; null means no single
// right answer, and the row only measures time.
const TEST_POSITIONS = [
    { name: "Start position", fen: "rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1", expectBest: null },
    { name: "Italian Game, move 4", fen: "r1bqk1nr/pppp1ppp/2n5/2b1p3/2B1P3/5N2/PPPP1PPP/RNBQK2R w KQkq - 4 4", expectBest: null },
    { name: "Black queen hangs on h4", fen: "rnb1kbnr/pppp1ppp/8/4p3/4P2q/5N2/PPPP1PPP/RNBQKB1R w KQkq - 2 3", expectBest: "f3h4" },
    { name: "Back-rank mate in one", fen: "6k1/5ppp/8/8/8/8/8/R5K1 w - - 0 1", expectBest: "a1a8" },
    { name: "Queen's Gambit middlegame", fen: "r1bq1rk1/pp2bppp/2n1pn2/3p4/2PP4/2N1PN2/PP3PPP/R1BQKB1R w KQ - 0 8", expectBest: null },
];

const DEPTHS = [1, 2, 3, 4, 5, 6];

// --- Talking to the worker ---

const worker = new Worker("../../assets/js/engine/garbochess-scorer.worker.js");

/** Sends one request and resolves with the worker's answer. */
function scorePosition(fen, depth) {
    return new Promise((resolve, reject) => {
        worker.onmessage = (event) => {
            if (event.data.type === "scores") {
                resolve(event.data);
            } else {
                reject(new Error(event.data.message));
            }
        };
        worker.postMessage({ type: "score", fen: fen, depth: depth });
    });
}

// --- Showing the results ---

/** Formats one scored move as "e2e4 (+0.31)" or "a1a8 (mate in 1)". */
function describeMove(scoredMove) {
    if (scoredMove.mateIn !== null) {
        return scoredMove.move + " (mate in " + scoredMove.mateIn + ")";
    }
    const pawns = (scoredMove.centipawns / 100).toFixed(2);
    return scoredMove.move + " (" + (scoredMove.centipawns >= 0 ? "+" : "") + pawns + ")";
}

/** Adds one result row to the table. */
function addRow(position, answer, verdict) {
    const row = document.createElement("tr");
    const cells = [
        position.name,
        String(answer.depth),
        String(answer.milliseconds),
        String(answer.moves.length),
        answer.moves.slice(0, 3).map(describeMove).join(", "),
        describeMove(answer.moves[answer.moves.length - 1]),
        verdict.text,
    ];
    cells.forEach((text, index) => {
        const cell = document.createElement("td");
        cell.textContent = text;
        if (index === cells.length - 1 && verdict.className) {
            cell.className = verdict.className;
        }
        row.appendChild(cell);
    });
    document.getElementById("results").appendChild(row);
}

/** Compares the best move with the expected one, if the position has one. */
function judge(position, answer) {
    if (position.expectBest === null) {
        return { text: "time only", className: "", passed: null };
    }
    const passed = answer.moves[0].move === position.expectBest;
    return { text: (passed ? "pass: " : "FAIL: expected ") + position.expectBest, className: passed ? "pass" : "fail", passed: passed };
}

// --- Running the proof ---

async function runProof() {
    document.getElementById("results").textContent = "";
    const summary = document.getElementById("summary");
    let passed = 0;
    let checked = 0;
    let slowestDepthFour = 0;
    let slowestDepthSix = 0;

    for (const position of TEST_POSITIONS) {
        for (const depth of DEPTHS) {
            summary.textContent = "Running: " + position.name + ", depth " + depth + " ...";
            const answer = await scorePosition(position.fen, depth);
            const verdict = judge(position, answer);
            addRow(position, answer, verdict);

            if (verdict.passed !== null) {
                checked++;
                if (verdict.passed) passed++;
            }
            if (depth === 6) {
                slowestDepthSix = Math.max(slowestDepthSix, answer.milliseconds);
            }
            if (depth === 4) {
                slowestDepthFour = Math.max(slowestDepthFour, answer.milliseconds);
            }
        }
    }

    summary.textContent = "Done. Known answers found: " + passed + " of " + checked
        + ". Slowest depth-4 position: " + slowestDepthFour + " ms; depth 6: " + slowestDepthSix + " ms. "
        + "Browser: " + navigator.userAgent;
}

document.getElementById("run").addEventListener("click", () => {
    runProof().catch((problem) => {
        document.getElementById("summary").textContent = "Error: " + problem.message;
    });
});
