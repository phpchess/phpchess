# phpChess 6 — Build plan

The plan is six blocks, split into eleven large chunks.
- **A chunk** is one distinct building block: it ends in software that works and is tested, and it stops for review before the next one starts.
- **What each chunk names**: its scope as feature ids from [FEATURES.md](FEATURES.md), the contracts it touches from [ARCHITECTURE.md](ARCHITECTURE.md#4-contracts-rules-every-part-keeps), and the tests that prove it.
- **Order**: a chunk starts only after the one before it is done. The one exception is C5-1, which can run alongside Block 4.

**Definition of done, for every chunk:**
- its scope is built, and its tests and the automated checks pass;
- PHPStan reports no errors at max level;
- every new or changed file meets the code conventions (ARCHITECTURE.md §11, contract K15): header comment, clear names and segments, method descriptions, plain code;
- `composer audit` and the JavaScript audit are clean;
- the feature status is flipped in FEATURES.md, in the same session;
- ARCHITECTURE.md is updated wherever the chunk changed the shape;
- the review is done and the user has approved it.

| Block | Chunk | Builds | Status |
|---|---|---|---|
| 0 Ground | C0-1 Definition | this plan, the functionality list, the architecture | done (2026-11-22) |
| | C0-2 Proofs | the library check, the engine proof, the development environment finished | done (2026-11-22) |
| 1 Foundation | C1-1 Application core and install | kernel, router, database, migrations, installer, release zip, job runner | done (2026-11-22) |
| | C1-2 Identity, admin and look | accounts, roles, admin, feature switches, skins, languages (Appearance editor: backlog B3) | done |
| 2 Product | C2-1 Correspondence chess | rules, the move and game-end functions, challenges, board, ratings, notifications | done (2026-11-23) |
| | C2-2 Community, school and API | messages, profiles, content, school mode, API v1, PWA and Web Push, archive (Docker moved to backlog, A40) | done (2026-11-23); Web Push phone test (A18) pending |
| 3 Live and computer | C3-1 Timed play | clock, lobby, polling transport, limits, measurements | planned |
| | C3-2 Computer opponent | engine in the browser, selection layer, characters, calibration | planned |
| 4 Tournaments | C4-1 Tournament core | model, Round Robin, Swiss, knockout, League with drag-and-drop, tie-breaks | planned |
| | C4-2 Special formats | Team vs Team, Arena, voting, class tournaments | planned |
| 5 Lichess | C5-1 Lichess | embeds, login with Lichess, API | planned |

Release 6.0 is C0-1 to C4-2, plus a check on a real shared host before release.

---

## C0-1 Definition
- **Scope**: FEATURES.md, ARCHITECTURE.md, BUILD_PLAN.md and DECISIONS.md in `docs/`.
- **Contracts**: defines K1–K15.
- **Done**: the user has reviewed the four documents, and every 4.4 concept in the analysis §11.5 is either a feature id or a recorded drop.

## C0-2 Proofs
- **Scope**:
  - **Library check (A16)**, giving a library table with licence, version, audit result and "no network calls" checked. It covers cm-chessboard, chess.js, the drag-and-drop library, Stockfish.js and PHPMailer.
  - **Engine proof**: Stockfish.js (single thread) in a Web Worker returns several candidate moves at a fixed node budget, on a desktop and on a slow phone, with timings written down.
  - **Development environment finished**:
    - MariaDB strict mode and the local database (LOCAL_SETUP.md §4; named `phpchess` and `phpchess_support` since A22);
    - development mail goes to a log file, not to a mail catcher (decided 2026-11-22; built with the mailer in C1-2);
    - JavaScript without a build step (decided 2026-11-22, LIBRARIES.md);
    - a CI workflow on GitHub running the tests, PHPStan and the audits.
- **Contracts**: K9 and K10, prepared.
- **Done**: the library table is complete, with no blockers; the engine proof is measured; CI is green on the current skeleton.

## C1-1 Application core and install
- **Scope**: INS-01 to INS-08, INS-10, API-01 (skeleton), API-04.
  - **Application core**: the kernel, router, request and response objects, the security headers, the session and CSRF.
  - **Database**: the repository base class on PDO, the migration runner and the core migrations (System module).
  - **Install**: the installer with pre-flight checks and the protection self-test, and the root and per-folder `.htaccess` files.
  - **Background work**: the job runner and the rate limiter.
  - **Packaging**: the release zip build in CI, with an install test into a web root and into a subfolder.
- **Contracts**: K5, K7, K9, K10, K11, K13.
- **Tests**:
  - routing and method refusal;
  - CSRF;
  - migrations run, interrupted and resumed, on a database holding other tables;
  - the installer, start to finish, with a refusal when protection fails;
  - the job runner lock;
  - the CI checks for K5, K9 and K10.
- **Done**: a release zip installs into a subfolder of the local XAMPP by following the installer alone, with no command line.
- **Result (2026-11-22)**: done. The release zip installed into `htdocs/pc6sub/phpchess` in a normal browser, with the database found by the installer (A23); the installer then removed itself. The installer also creates the database when allowed and deletes its own files (A23). A first `users` table (migration 0002) lets it create the admin; C1-2 extends it. The CI install test (`release.yml`) runs on the first push.

## C1-2 Identity, admin and look
- **Scope**: ID-01 to ID-09, ID-13, SCH-01, UI-01, UI-03, UI-04, UI-06, UI-07, UI-09, ADM-01 to ADM-03. UI-02 (the skin editor) moved to the backlog (A25, B3); the skin format is built ready for it.
  - **Accounts**: registration, approval, verification, login, remember-me, reset, account states and roles.
  - **Admin**: re-authentication, the user administration, settings and mail, the feature switches page and the audit log.
  - **Look**: mobile-first layout (A18), the drag-and-drop base used by later chunks, the default skin (in the editor-ready format of A25), the responsive layout with dark mode, accessibility, the language files, and the reuse of the 4.4 English strings.
- **Contracts**: K6, K8, K13, K14.
- **Tests**:
  - each account flow, including the abuse cases: wrong token, expired token, reuse, throttling;
  - each role against each admin page;
  - each feature switch, refused when off;
  - the escaping check.
- **Done**: an OWASP ASVS level 2 review of Block 1 and a ZAP scan, with no open Critical or High findings.
- **Result (2026-11-23)**: done. 112 tests, all checks green. ZAP 2.17.0 found no Critical, High or Medium issues; the two Low ones are fixed. ASVS level 2 review in [proofs/security-review-block1.md](proofs/security-review-block1.md); one open point moved to backlog B5 (common-password list). SortableJS vendored for the drag-and-drop base.

## C2-1 Correspondence chess
- **Scope**: G-01 to G-11, G-13, G-20 to G-22, N-01, N-02, ID-12, R-01 to R-05, API-02, ADM-04.
  - **Rules and game logic**: the Rules module, the move function and the game-end function.
  - **Play**: one game model for every pace (A19), one-tap start, invites by link or QR code, rematch, challenges (open and direct), custom start positions, days-per-move clocks, vacation, offers, resignation and abort.
  - **Presence and delivery**: live moves while both players are there, in-app notices otherwise, a move email when offline.
  - **On the page**: the board, move notifications, the archive, the viewer, PGN export and watching games.
  - **Ratings**: Elo per speed with history, and the rating list.
  - **Admin**: adjudication and FEN game setup.
- **Contracts**: K1, K2, K3, K4, K12, K14.
- **Tests**:
  - the rules suite: perft counts and the positions for each ending;
  - every way a game ends in the analysis §12.1 goes through `end()` exactly once;
  - double submit and concurrent moves;
  - an open challenge can't be accepted twice;
  - a rating is never applied twice;
  - the outsider tests: S24 (resigning someone else's game) is impossible.
- **Done**: two users play a full correspondence game to each kind of ending, on the local install; ratings and history are correct.

## C2-2 Community, school and API
- **Scope**: C-01 to C-10, G-12, N-03, N-04, SCH-02 to SCH-07, ID-10, ID-11, API-01 (complete), UI-05, UI-08, ADM-05, ADM-07. (INS-09 Docker left the chunk: A40, backlog B16.)
  - **Community**: profiles, avatars and uploads, buddies, messages, the word filter, content and who's online.
  - **School mode**: classes, bulk student accounts, login cards (A41), class limits, teacher views. Retention by performance, not age: a daily speed check and an archive file the admin keeps (A42, A43).
  - **Start page**: the dashboard; notices leave the player's view (A44).
  - **Users' own data**: export and deletion.
  - **Clients**: API v1 with tokens; the PWA with offline viewing; Web Push to iPhone, Android and desktop, with quiet hours.
  - **Decision point (A18)**: Web Push tested on a real iPhone and an Android phone. If it isn't good enough, a native app becomes its own chunk.
- **Contracts**: K6, K8, K9, K14.
- **Tests**:
  - every school switch;
  - a student can't reach anyone outside the class when limited;
  - upload validation;
  - the API token life cycle;
  - an export contains all of the user's data, and deletion removes it;
  - with the push switch off, the server makes no outgoing connection.
- **Done**: a school scenario (a teacher, a class of 25 students, messaging off) runs end to end; the security review is repeated.
- **Status (2026-11-23)**: built and tested (tests/Community, tests/School, tests/Clients, tests/Identity/OwnDataTest, tests/Infra/ArchiveTest; the scenario is SchoolTest::testASchoolScenarioWithAClassOf25AndMessagingOff). Open: the Web Push test on a real iPhone and Android phone (A18).

## C3-1 Timed play
- **Scope**: G-14 to G-18, G-23, API-03.
  - **Clock**: base plus increment, flag fall, lag compensation.
  - **Changing pace**: a game switches between live and correspondence when both players agree.
  - **Lobby**: game offers, abort when no first move is made, disconnects, premoves.
  - **Transport**: the short-poll client.
  - **Limits**: the minimum time control and the maximum number of live games.
  - **Measurements**: move delivery time, the cost per poll, a load test, and the shared-host estimate.
- **Contracts**: K2, K3, K12.
- **Tests**:
  - clock arithmetic and lag compensation;
  - a flag decided inside the move lock;
  - the load test results written down.
- **Done**: 5+3 games on the local install, with no time lost to polling; measurements and the estimate are recorded.

## C3-2 Computer opponent
- **Scope**: AI-01 to AI-07.
  - **Engine**: the engine Worker and its interface.
  - **Choosing moves**: the selection layer and the characters.
  - **Games**: computer games are stored, unrated.
  - **Admin**: the engine page.
  - **Calibration**: the ladder, with its anchors and number of games fixed before it runs.
  - **Cleanup**: remove the temporary engine proof page `public/proof/` (from C0-2).
- **Contracts**: K1 (the computer's moves go through the server like any other), K9.
- **Tests**:
  - the selection layer with fixed engine outputs;
  - the same move choice on every device at the same budget;
  - the calibration run.
- **Done**: the calibration result lands in the target band (about 600 and below), its confidence range is published, and no random blunders are seen in the sample games.

## C4-1 Tournament core
- **Scope**: T-01 to T-08, ADM-06.
  - **Model**: one tournament model with organisers.
  - **Formats**: Round Robin, Swiss, knockout, and League with divisions, promotion and relegation, plus a drag-and-drop interface.
  - **Results**: tie-breaks, standings, crosstables and PGN export.
  - **Rounds**: scheduled by the job runner, with forfeit rules.
- **Contracts**: K3 (tournament results come from games), K4, K14.
- **Tests**:
  - pairing tables for each format, including odd player counts;
  - Swiss checked against published examples;
  - tie-break values;
  - a round closing with unfinished games under each forfeit rule;
  - drag-and-drop changes saved and checked on the server.
- **Done**: one tournament of each format runs to the end on the local install with real games.

## C4-2 Special formats
- **Scope**: T-09 to T-13.
  - **Team vs Team**.
  - **Arena**, for timed play.
  - **Voting tournaments**: the server resolves the vote, with a stated tie rule; organisers, spectators and chat.
  - **Class tournaments** run by teachers.
- **Contracts**: K2 (a vote result is played through the move function), K3, K8.
- **Tests**:
  - vote resolution on time with nobody online;
  - ties;
  - team scoring;
  - Arena pairing and scoring.
- **Done**: one tournament of each format runs on the local install, followed by the release check on a real shared host.

## C5-1 Lichess
- **Scope**: L-01 to L-03.
- **Contracts**: K9, which is opened only by the "external content" switch; K8.
- **Tests**:
  - with the switch off, there are no outside requests at all;
  - the OAuth flow, including state and PKCE checks.
- **Done**: works with the switch on, is silent with it off, and the OAuth flow passes security review.
