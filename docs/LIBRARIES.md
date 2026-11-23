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

## Server (Composer, runtime)
| Library | Purpose | Feature | Version | Licence | Own dependencies | Known vulnerabilities | Status |
|---|---|---|---|---|---|---|---|
| PHPMailer | Sending mail over SMTP | N-02, ID-02, ID-06 | v7.2.0 | LGPL-2.1-only | none (PHP extensions only) | none (composer audit when added) | approved (decision 2), added in C1-2 |
| minishlink/web-push | Sending Web Push notifications | N-03 | v11.0.0 | MIT | 9 packages, including a JWT library and PSR HTTP interfaces | to check when added | approved reluctantly (decision 3), added in C2-2 |

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
4. **GarboChess is the first engine**, Stockfish.js the fallback (engine proof).
