/**
 * Writes every computer player's engine settings (development only).
 *
 * Feature AI-03, decisions A50 and A53. Reads
 * src/Domain/Computer/computer-players.json, works out each player's engine
 * settings from tools/calibration/strength-table.mjs, and writes them back
 * into the same file as an "engine" block. Names, bios and listed Elo are
 * kept exactly as they are.
 *
 * Each player gets:
 *   engine.normal         the settings for its listed rating;
 *   engine.low / high     Uneven players only: settings 250 Elo below / above,
 *                         used in some games (chosen per game, the same on
 *                         every device; see settingsForGame in move-chooser.js);
 *   engine.slowOnPhones   true from 1400 up (A53).
 * The Wood Pusher (personality "Random") gets { normal: { randomMover: true } }.
 *
 * Run again after changing the strength table:
 *   node tools/build-player-settings.mjs
 */

import { readFileSync, writeFileSync } from "node:fs";
import { fileURLToPath } from "node:url";
import { dirname, join } from "node:path";
import { SLOW_ON_PHONES_FROM_ELO, UNEVEN_SWING_ELO, engineSettingsFor } from "./calibration/strength-table.mjs";

const projectFolder = join(dirname(fileURLToPath(import.meta.url)), "..");
export const PLAYERS_FILE = join(projectFolder, "src/Domain/Computer/computer-players.json");

/** The engine block for one player. */
export function engineBlockFor(player) {
    if (player.personality === "Random") {
        return { normal: { randomMover: true }, slowOnPhones: false };
    }
    const block = { normal: engineSettingsFor(player.elo, player.personality) };
    if (player.personality === "Uneven") {
        block.low = engineSettingsFor(player.elo - UNEVEN_SWING_ELO, player.personality);
        block.high = engineSettingsFor(player.elo + UNEVEN_SWING_ELO, player.personality);
    }
    block.slowOnPhones = player.elo >= SLOW_ON_PHONES_FROM_ELO;
    return block;
}

// --- Main (only when run directly, not when imported by the calibration) ---

if (process.argv[1] && fileURLToPath(import.meta.url) === process.argv[1]) {
    const players = JSON.parse(readFileSync(PLAYERS_FILE, "utf8"));
    for (const player of players) {
        player.engine = engineBlockFor(player);
    }
    writeFileSync(PLAYERS_FILE, JSON.stringify(players, null, 2) + "\n");
    console.log("Engine settings written for " + players.length + " players.");
}
