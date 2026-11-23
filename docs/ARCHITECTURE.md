# phpChess 6 — Solution architecture

How phpChess 6 is put together, and why. Features are listed in [FEATURES.md](FEATURES.md), decisions in [DECISIONS.md](DECISIONS.md), and the order of building in [BUILD_PLAN.md](BUILD_PLAN.md). The findings on phpChess 4.4 that shaped this design are in `phpchess44/PHPCHESS_ANALYSIS.md`, outside this repository.

## 1. Shape in one picture

```
 Browser (HTML pages + JavaScript, all served by the install)
   ├─ pages: plain PHP templates rendered by the server
   ├─ board + move feedback: cm-chessboard, chess.js
   ├─ live state: short-poll  GET /api/v1/games/{id}/state?since=<ply>
   └─ computer opponent: Web Worker → engine (WASM, UCI) + selection layer
            │ HTTPS, cookie session + CSRF (web) or Bearer token (apps)
 ───────────┼──────────────────────────────── shared host: PHP + MariaDB/MySQL
 public/index.php  (front controller: the only PHP file the web server runs)
   → Kernel: config, error handling, security headers, session
   → Router → Controller (HTML page or API v1 endpoint)
       → Domain services   (rules, games, ratings, tournaments, identity, school …)
           → Repositories (PDO, prepared statements, transactions, row locks)
               → database (prefixed InnoDB tables)
   → Job runner: cron or pseudo-cron → timeouts, vote resolution, rounds, mail queue
```

**Mobile first (A18)**: the browser is usually a phone running the installed app (PWA). A service worker caches the app shell and your own games for offline viewing, and receives Web Push.

**The reasoning behind it:**
- Shared hosting gives us PHP and a database, nothing that runs permanently (A1). So all server work happens inside a request or a job-runner tick.
- Real-time needs and heavy computation move to the browser: polling, and the engine running in the player's own device.

## 2. Code layout

```
phpchess6/
  public/          the only web-served folder: index.php, cron.php (command line only), assets/ (css, js, img, vendor/ libraries)
  src/             PHP code, namespace PhpChess\ (PSR-4)
    Kernel/        Kernel, Config, ErrorHandler, SecurityHeaders, Session, Csrf, Router, Routes (the list of every address)
    Http/          Request, Response, ApiError, Template, e(); controllers (Web\, Api\V1\)
    Domain/        one folder per module (see §3): services, entities, rules (SystemNSAFESTATECHECK SO FAR)
    Infra/         Database, Repository base, repositories per module (System\, Identity\, ...), Migration\, Jobs\; later mailer, clock, file storage
    Install/       InstallerController, DatabaseFinder, PreflightCheck, ProtectionSelfTest (all deleted after install, A23)
  templates/       plain PHP templates (core); templates/local/ = the server admin's overrides
  migrations/      numbered schema migrations
  lang/            language files per locale
  skins/           skins: skin.json, theme.css, images (no PHP)
  config/          config.php written by the installer (never committed)
  storage/         uploads, logs, cache (not web-served)
  tests/           PHPUnit tests, mirrors src/
  tools/           development tools - never released: composer.phar, the CI checks (check-headers, check-sql K5,
                   check-external-urls K9), build-release.php (K10) and install-test.php
  docs/            these documents
  .htaccess        root: routes everything to public/ (A17)
```

Every folder except `public/` carries its own deny `.htaccess`. That protection doesn't depend on the root rule or on how the host maps the domain (A17).

## 3. Modules

Each module is a folder under `src/Domain/`. It owns its tables and is used by others only through its service class, never through its tables.

| Module | Responsibility | Owns (tables, prefix omitted) |
|---|---|---|
| Identity | accounts, states, roles, login, tokens, preferences | `users`, `user_profiles`, `user_preferences`, `user_tokens`, `login_attempts` |
| School | classes, teacher actions, class limits | `groups`, `group_members` |
| Rules | chess rules: legal moves, check, mate, draws, FEN, SAN/UCI | none (pure code) |
| Games | one game model for every time control, one-tap start, challenges and invites, lobby, the move function, the game-end function, clocks, offers, chat, presence | `games`, `game_moves`, `game_offers`, `game_chat`, `challenges` |
| Ratings | Elo per speed, history | `ratings`, `rating_history` |
| Tournaments | formats, pairings, rounds, standings, votes | `tournaments`, `tournament_*` |
| Community | profiles, buddies, messages, filter, content | `friendships`, `messages`, `word_filter`, `news`, `pages`, `server_messages`, `tips`, `media` |
| Notifications | presence-aware delivery: live, in-app, push, email; quiet hours | `notifications`, `email_outbox`, `push_subscriptions` |
| System | settings, feature switches, jobs, audit, migrations | `settings`, `jobs`, `audit_log`, `schema_migrations`, `rate_limits` |
| Lichess | OAuth link, embeds, API calls | `user_external_accounts` |

**Who calls whom:**
- **Games** calls Rules and, when a game ends, Ratings and Notifications.
- **Tournaments** creates games through Games and reads their results from Games. It never writes a result itself.
- **School** limits who may challenge whom, through a check that Games asks.

## 4. Contracts: rules every part keeps

Each contract has an id. A chunk names the contracts it touches, and a test or automated check backs each one.

| Id | Contract | Checked by |
|---|---|---|
| K1 | **The server decides chess.** Only the Rules module decides legality and game state. Browser checks are for feedback only. | tests: rules suite (perft counts, mate/stalemate/draw positions) |
| K2 | **One move function** `Games::move(game, ply, move)`. It runs in one transaction, with the game row locked (`SELECT … FOR UPDATE`). The expected ply makes a repeated request harmless. | tests: double submit, wrong turn, wrong ply, concurrent moves |
| K3 | **One game-end function** `Games::end(game, result, reason)`. It checks the game is still running inside the lock, writes result and reason, then calls Ratings, Notifications and Tournaments. Nothing else writes a result. | static check: no other code writes `games.result`; tests: each ending in the analysis §12.1 |
| K4 | **A rating is applied once per game**: `rating_history` is unique per (game, user), and the game's rated flag is respected on every path. | tests: end twice, unrated game |
| K5 | **All SQL goes through repositories, as prepared statements.** No SQL text is built from input; identifiers come from code only. | static check: no `query(`/`exec(` with variables outside `Infra/` |
| K6 | **All output is escaped**: every value printed in a template goes through `e()`; JSON goes through `json_encode`. | CI check: unescaped `<?=`/`echo` of a variable fails |
| K7 | **Changes are POST**, with a CSRF token (web) or a Bearer token (API). GET never changes state. | router refuses state-changing routes on GET; tests |
| K8 | **Feature switches are enforced on the server**: a switched-off feature answers 404 to page and API alike; hiding it in the UI is only extra. | tests per switch |
| K9 | **No outside requests**: CSP with `default-src 'self'` and `connect-src 'self'`. Every library and font is local (A16). Two exceptions, each behind its own switch: the Lichess module ("external content") and Web Push ("push notifications"), which the server sends through the browser makers' push services (A18). | header test; CI check for external URLs in assets |
| K10 | **Development tools never ship**: the release is built with `--no-dev`, and CI fails if a dev package is in the zip. | release CI |
| K11 | **Migrations touch only their own prefixed tables**, never drop what they didn't create, and record each step. | migration tests on a database that also holds other tables |
| K12 | **Time is the server's**: UTC, milliseconds; clocks are updated inside K2's transaction; lag compensation is capped. | clock tests |
| K13 | **Every redirect or refusal ends the request**: the response object is returned, never followed by more code. | framework design; tests for denied access |
| K14 | **Access is checked in the controller before the service runs** (role, ownership, class limits); services check again whatever protects data (e.g. "is this your game"). | tests: every endpoint as an outsider |
| K15 | **Code is readable by a person (A21)**: every file follows §11. Clarity is not traded for speed or brevity; a measured performance problem is solved by a clear design, not a clever line. | review checklist for every chunk; PHPStan max level; CI checks that every PHP file has a header comment |

## 5. Request flow
1. The web server sends every request to `public/index.php`: through the root `.htaccess` when the domain points at the install folder, or directly when it points at `public/`. On nginx, the installer's snippet does the same.
2. **The Kernel**:
   - loads the config, and sends a request to the installer when the config is missing;
   - sets the error handling: errors are logged, never shown;
   - sends the security headers (CSP, frame-ancestors, nosniff, Referrer-Policy, HSTS when HTTPS);
   - starts the session (HttpOnly, Secure, SameSite=Lax).
3. **The router** matches method and path. API routes live under `/api/v1/`. State-changing routes accept POST only and check CSRF or the Bearer token (K7).
4. **The controller** checks access (K14), calls one service, and returns a response: a template, JSON or a redirect (K13).
5. **On the way out**: one log line per request with time taken, and the pseudo-cron tick if one is due (§8).

## 6. Data
- InnoDB, `utf8mb4_unicode_ci`, a configurable table prefix (default `phpc_`), integer keys, foreign keys, explicit column lists, no zero dates.
- The table set is listed in the analysis §11.3, plus the school tables (`groups`, `group_members`). It becomes migrations in C1-1 (core) and in later chunks (their own tables).
- **Migrations (K11)**: files `migrations/NNNN_name.php`, each returning steps of one SQL statement with table names written `{name}`; the runner adds the prefix, refuses bare table names, `DROP DATABASE` and dropping tables no migration created, and records every step in `schema_migrations`. C1-1 ships `0001` (System: settings, jobs, audit_log, rate_limits) and `0002` (a first `users` table, so the installer can create the admin; C1-2 extends it).
- **Game state**:
  - `games` holds the current FEN, ply and clocks;
  - `game_moves` holds each move as UCI and SAN, the FEN after it, and the clock after it;
  - the current position is read from `games`, not replayed, and the move list is the record behind it.
- **Strict SQL mode** is assumed. The app sets its own session `sql_mode` to strict and never relies on a loose server.
- **Settings**: a typed key/value table, cached per request. Feature switches are settings.

## 7. Browser side
- **Rendering**: server-rendered HTML pages (plain PHP templates), enhanced by JavaScript modules. It is not a single-page app. Pages work with JavaScript for reading, and the board needs it.
- **Libraries** (A16): permissive licences, versions pinned, kept as plain files in `public/assets/vendor/<name>/` with their licence and a `SOURCE.md` (origin, version, checksum, network check). The list and their status are in [LIBRARIES.md](LIBRARIES.md).
- **Live state**: one polling client (API-03) for every live view. Its interval adapts: about 1 s while it's the opponent's move in a timed game, slower otherwise, and paused in a hidden tab.
- **Computer opponent**:
  - **Where it runs**: a Web Worker hosts the engine; the main page never blocks.
  - **How it chooses**: the selection layer asks for several candidate moves at a fixed node or depth budget (AI-04), then picks by the character's rules.
  - **What it stores**: a game against the computer is stored through the normal API, marked as a computer game and unrated.
- **Presence and delivery (G-10)**:
  - a player counts as *on the game* while that game's page is polling, and as *online* while any page is;
  - when a move is made, Notifications picks one channel: live (the opponent is on the game), push or in-app (online elsewhere, or the app is closed but push is on), or email (offline longer than the player's set delay);
  - every notice is recorded, so the same move is never sent twice.
- **Mobile first (A18)**: layouts start at phone width; touch is the main input (drag-and-drop and tap-tap moves); the service worker gives offline viewing (UI-08) and Web Push (N-03).
- **Skins made in the admin area (A24)**: an admin creates and edits skins in the browser, with no files or code: logo selection, colour schemes, default board and pieces, layout choices, and fun seasonal effects (for example winter or holiday themes, switched on by date or by hand). A skin is stored as data (skin.json, theme.css as CSS variables, images) under `skins/`, never as PHP (A5, UI-01, UI-02). Ready-made skins can be copied and adjusted.
- **No build step**: the JavaScript in the repository is exactly what the browser runs — plain modules, readable, unminified (A21). No Node or npm is needed on the development machine or the host.

## 8. Background work
- **The job runner** (INS-08): one entry point that runs due jobs, each with a database lock so two runners never overlap. The jobs:
  - timeouts;
  - vote resolution;
  - round scheduling;
  - mail queue;
  - cleanup and retention.
- **With cron**: a real cron calls it every minute.
- **Without cron**: a request finishing after its response checks whether a tick is due, at most once a minute, and runs one short batch.
- **Push and email sending** run from the job runner, so a move request never waits for an outside server.
- **Timeouts never wait for the job runner**: any request that reads a game checks its clock and ends it on time through K3.

## 9. Security model (summary)
The contracts K2–K14 are the security model. The rest:
- **Passwords**: `password_hash` (Argon2id, bcrypt fallback).
- **Tokens**: random, 256-bit, stored hashed, typed and expiring.
- **Rate limits**: in the database, per account and IP, for login, registration, reset and the API.
- **Uploads**: type checked by content, re-encoded, renamed, stored in `storage/` and served through a controller.
- **Admin**: re-authentication for admin actions, an audit log, and the unsafe-state banner (INS-07).
- **Review**: OWASP ASVS level 2 is the target; the review and a ZAP scan close Block 1, and are repeated at each later block.

## 10. Packaging and install (A17)
- **The release zip**: a GitHub release built by CI. It contains:
  - `vendor/` from `--no-dev`;
  - `public/` with its JavaScript and vendored libraries, as in the repository;
  - the root and per-folder `.htaccess` files;
  - no `tests/` or `tools/`.
- **The installer** runs at the site's address when no config exists:
  1. pre-flight;
  2. protection self-test;
  3. database details;
  4. admin account;
  5. migrations;
  6. lock.

  Step 3 asks only for the database login and finds or creates the database itself (`DatabaseFinder`, A23). Step 6 deletes the installer's own files (`src/Install`, `templates/install`); `/install` then no longer exists.
  The step is worked out from the facts on every visit, so a reload carries on. Only the browser that saved the database details can finish (its session secret is hashed into the config). When `config/` is not writable, the page shows the config file to upload by FTP.
- **CI install test**: CI (`.github/workflows/release.yml`) installs every release zip with Apache both into a web root and into a subfolder, and drives all six installer steps over HTTP (`tools/install-test.php`), self-test included.
- **Docker image** for school servers (INS-09): the same zip, with PHP, the web server and MariaDB.

## 11. Code conventions: readable by a person (A21)

The main value of phpChess is code that a person can read, audit and change without tools or AI, including a PHP student studying it on their own. These rules apply to every file, and a chunk is reviewed against them before it is done.

**Finding things**
- One class per file. The file name is the class name, and the folder is its module (§2, §3), so the URL, the controller, the service and the template for a feature can be found by name.
- Names say what something is or does, in full words: `GameService::endGame()`, `$remainingMilliseconds`, `ChallengeRepository`. No abbreviations beyond the common ones (`id`, `url`, `fen`, `pgn`, `uci`, `san`).
- Each feature id from FEATURES.md appears in the header of the files that build it, so a search for `G-05` finds the challenge code.

**Reading a file from the top**
- Every file starts with a header comment: what it is, its responsibility, and how it fits the rest (which module, who calls it, which contracts it keeps).
- Files are divided into clear, labelled segments, for example `// --- Checking the move ---`, in the order the code runs.
- Every public method has a short description: what it does, its inputs and output, and what it refuses. A block whose purpose isn't obvious from the code gets a comment above it saying why it is there.
- Comments describe the code as it is now. Its history lives in Git, and decisions live in DECISIONS.md.

**Keeping it plain**
- Straightforward code over clever code: early returns, small methods with one job, explicit loops where a chain of calls would be harder to follow. No magic: no hidden behaviour through reflection, dynamic method names or code generation.
- Types everywhere (`declare(strict_types=1)`, typed properties, parameters and returns), so PHPStan at max level and a reader can both trust what a value is.
- No abstraction before it is needed twice. A pattern is introduced only when it makes the code easier to follow, and it is explained where it is defined.
- Libraries are used where they are clearer than our own code, and named in the file that uses them.

**Templates and JavaScript**
- Templates are plain PHP with only display logic, and every printed value goes through `e()` (K6). Each template starts with a comment listing the variables it expects.
- JavaScript follows the same rules: one module per file, a header comment, named functions, no minified code of our own; what is in the repository is what runs.

**Performance**
- Performance budgets come from measurements, not assumptions. When a measured problem needs fixing, the fix keeps the code readable: a better query, an index or a cache with a clear name, never a hard-to-read shortcut.
