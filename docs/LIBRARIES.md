# phpChess 6 — Third-party libraries

Every library phpChess 6 uses or plans to use, checked against A16 (client side) and the no-new-dependency rule. A library enters the code only once its row says **approved**. Re-check every row when its version changes.

**Last checked: 2026-11-22.**
- **Licence and version** come from the package registries (npm, Packagist).
- **Vulnerabilities** were checked against the OSV database (osv.dev) for the exact version.
- **"No network calls"** still has to be confirmed by reading the code when each library is first used. The registry check can't prove it.

## Browser (A16: permissive licence, local files only, no outside requests, offline)
| Library | Purpose | Feature | Version | Licence | Own dependencies | Known vulnerabilities | No network calls | Status |
|---|---|---|---|---|---|---|---|---|
| cm-chessboard | The board: drag-and-drop and tap moves, SVG pieces | G-04 | 8.15.8 | MIT | none | none | **yes**: the only request is the piece sprite, loaded from our own `assets/` path | **approved** (A32); vendored in `public/assets/vendor/cm-chessboard/` (C2-1), SHA-256 in its `SOURCE.md` |
| chess.js | Move rules in the browser, for instant feedback only (the server decides, K1) | G-04 | 1.4.0 | BSD-2-Clause | none | none | **yes**: no network calls in `dist/esm/chess.js` | **approved** (A32); vendored in `public/assets/vendor/chess.js/` (C2-1), SHA-256 in its `SOURCE.md` |
| SortableJS | Drag-and-drop for lists: League divisions, admin ordering | UI-09, T-05 | 1.15.7 | MIT | none | none | **yes**: no network calls in `Sortable.js` | **approved**; vendored in `public/assets/vendor/sortablejs/` (C1-2), SHA-256 in its `SOURCE.md` |
| qrcode-generator | QR codes for game invitations | G-21 | 2.0.4 | MIT | none | none | to confirm | candidate |
| stockfish (Stockfish.js) | Fallback engine, only if GarboChess falls short | AI-01 | 19.0.1 | GPL-3.0 (the engine exception in A16) | none | none | to confirm if used | fallback |
| GarboChess-JS | Computer opponent engine (first choice, AI-01), scored per move by our wrapper | AI-01 | file of commit 4ff0f9e (2012), SHA-256 recorded in its `SOURCE.md` | BSD-3-Clause | none | none known (dormant since 2012; we read and own the copy) | **yes**: no XMLHttpRequest, fetch, importScripts or WebSocket in the file | **approved for the proof**; vendored in `public/assets/vendor/garbochess/`; see [proofs/engine-proof.md](proofs/engine-proof.md) |
| Lichess chess-openings (data) | Opening book and opening names: `public/assets/engine/book/a.tsv`–`e.tsv` (ECO, name, moves) | AI-02 | files as supplied by the user, 2026-11-24 | CC0-1.0 | none | none (data, no code) | **yes**: plain text | **approved** (A52) |
| Opening traps sample (data) | `traps-sample.tsv`: popular losing opening moves from Lichess games with engine refutation, for low-level and teacher players | AI-03 | supplied by the user, 2026-11-24 (8 rows) | CC0-1.0 (Lichess database) | none | none (data) | **yes**: plain text | **approved** (A52) |
| Lichess puzzle database (data) | Strength test (B15): 68 puzzles, 4 per rating band 600–2200, in `src/Domain/Strength/strength-puzzles.csv`, picked by `tools/pick-puzzles.php` from https://database.lichess.org/ (`lichess_db_puzzle.csv.zst`, downloaded 2026-11-25; full file kept outside the repo) | B15 | 2026-11-25 download | CC0-1.0 | none | none (data) | **yes**: plain text | **approved** (A59) |
| Lichess puzzle database (data), puzzle set | Puzzle trainer (L-01): 3,334 puzzles, 521 KB, in `src/Domain/Puzzles/puzzle-set.csv`, picked by `tools/build-puzzle-set.php` from the same download (popularity ≥80, ≥1,000 plays, 25 motifs × 10 rating bands) | L-01 | 2026-11-25 download | CC0-1.0 | none | none (data) | **yes**: plain text | **approved** (A76, A80) |

## Server (Composer, runtime)
| Library | Purpose | Feature | Version | Licence | Own dependencies | Known vulnerabilities | Status |
|---|---|---|---|---|---|---|---|
| PHPMailer | Sending mail over SMTP | N-02, ID-02, ID-06 | v7.2.0 | LGPL-2.1-only | none (PHP extensions only) | none (composer audit when added) | approved (decision 2), added in C1-2 |
| minishlink/web-push | Sending Web Push notifications | N-03 | v11.0.0 | MIT | 14 packages, below | none (composer audit, 2026-11-23) | **approved** reluctantly (decision 3), added in C2-2; only called when the switch "push notifications" is on (K9) |
| nyholm/psr7 | The HTTP request and response objects web-push needs (PSR-7, PSR-17) | N-03 | 1.8.2 | MIT | the PSR interfaces | none (composer audit) | **approved** with web-push (C2-2); our own small curl client (`src/Infra/Notify/CurlHttpClient.php`) sends them, so no Guzzle |
| web-push's own dependencies | brick/math 1.0.0, php-http/discovery 1.20.0, php-http/httplug 2.4.1, php-http/promise 1.3.1, psr/clock 1.0.0, psr/http-client 1.0.3, psr/http-factory 1.1.0, psr/http-message 2.0, psr/log 3.0.2, spomky-labs/base64url 2.0.4, spomky-labs/pki-framework 1.6.3, symfony/deprecation-contracts 3.8.1, symfony/polyfill-php83 1.43.0, web-token/jwt-library 4.2.8 | N-03 | as listed | all MIT | — | none (composer audit, 2026-11-23); none of them opens a connection of its own (checked: no curl or socket calls) | recorded (decision 3) |

## Development only (never in a release, K10)
| Tool | Purpose | Version | Licence | Status |
|---|---|---|---|---|
| Composer | Package manager | 2.11.2 | MIT | approved, in use |
| PHPUnit | Tests | 12.5 | BSD-3-Clause | approved, in use |
| PHPStan | Static analysis | 2.x | MIT | approved, in use |

## Decisions (user, 2026-11-22)
1. **No JavaScript build step**: plain JavaScript modules loaded by the browser; libraries kept as plain files in `public/assets/vendor/<name>/`, each with its licence and a `SOURCE.md` (origin, version, checksum, changes, network check). No Node or npm needed. Vulnerabilities are checked against OSV by version.
2. **PHPMailer allowed** (LGPL-2.1), as a server-side exception.
3. **web-push allowed, reluctantly**: every one of its dependencies is recorded here when C2-2 adds it.
4. **GarboChess is the engine** (A47); Stockfish only through the how-to, not shipped.
