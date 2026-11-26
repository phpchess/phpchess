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
| 3 Live and computer | C3-1 Timed play | clock, lobby, polling transport, limits, measurements | done (2026-11-24, 5+3 games played on the local install) |
| | C3-2 Computer opponent | engine in the browser, selection layer, characters, calibration | done (2026-11-25, tuned to real players, A55–A58); reviewed and play-tested 2026-11-25 |
| | F1 Fixes and the strength test | B7, B15, B17, B20, B21, B23, B30 (A59–A65) | done for review (2026-11-25): all checks pass |
| | F2 UI overhaul | B28, B29, new Play page (A66–A73, A82–A86) | done (2026-11-26), reviewed by the user |
| 4 Tournaments | C4-1 Tournament core | model, Round Robin, Swiss, knockout, League with drag-and-drop, tie-breaks (A97–A110) | built (2026-11-26); tests, checks and review pending |
| | C4-2 Special formats | Team vs Team, Arena, voting, class tournaments | planned |
| 5 Lichess | C5-1 Lichess | puzzles, study games, Lichess TV, content scanner (A74–A88; login dropped, A77) | done (2026-11-26, reviewed) |

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
- **Changed during the chunk (2026-11-25)**: the players are tuned against real players of each rating (Lichess profile, A55) instead of an Elo range; results in `docs/proofs/c3-2-human-profile.md`. The "no random blunders" proof is dropped (user): human-like giveaways are now intended (A56), and A57–A58 replace random blunders with noise, blind spots and threat blindness. Check run of the final adjustment: backlog B32.

## F1 Fixes and the strength test
- **Scope**: backlog B7, B15, B17 (plus no chat in games against the computer), B20, B21, B23, B30; decisions A59–A65.
  - **B15 strength test** (A59, A60): 10 adaptive Lichess puzzles (CC0), pop-up on first login, offered next to the rating until the strength is known.
  - **B17**: review of C2-2; chat closed on finished and computer games, no colour reset after posting (A61).
  - **B7** admin login without "register"; **B20** service worker cache name from the release (A62).
  - **B21** site-wide poll with challenge pop-up (A63); **B23** no flicker in live games (A64); **B30** computer move delay (A65).
- **Tests**: puzzle selection and band result; chat refused on finished/computer games; cache name changes with the version; site-wide poll answer; a poll with news but no move leaves the board alone; delay settings.
- **Done**: as every chunk (tests, PHPStan max, composer audit, header check), each run with the user's go.
- **Status (2026-11-25)**: built, committed locally. C2-2 review: docs/proofs/f1-c2-2-review.md (teacher takeover fixed; B34, B35 to the backlog). Push for direct challenges already comes from the notices (C2-2). A new pace still reloads the game page once (clocks change). Checks 2026-11-25: PHPUnit 295 tests OK, Node 48 OK, PHPStan max OK, composer audit OK, header/SQL/escaping/URL checks OK; load test: /news 2.5 ms median (docs/proofs/c3-1-load-test.md). Open: the user's review on the local site.

## F2 UI overhaul
- **Scope**: backlog B28, B29 and a new Play page; decisions A66–A73, A82, A83.
  - **Look** (A66, A72): one design system, button-centred, full width on every device, portrait phone first; bottom tab bar on phones (A68).
  - **Icons** (A67): inline SVG from a PHP helper, coloured by the theme.
  - **Play page** (A69, A70): "New game" pop-up (opponent, time, strength range, colour, all buttons); boxes for open challenges, received challenges, running games, admin messages, statistics later; live refresh through the site-wide poll.
  - **Admin** (A71): full width, grouped side menu, sticky table headers, switch buttons.
  - **Login** (A73): empty slot for the optional Lichess button of C5-1.
- **Tests**: icon helper output and escaping; new-game choices map to the right challenge or computer game; poll answer carries the Play page boxes; every page renders with the new layout; no external URLs (K9).
- **Done**: as every chunk (tests, PHPStan max, composer audit, header check), each run with the user's go.
- **Status (2026-11-26)**: built, committed locally. Checks 2026-11-26: PHPUnit 305 OK (with -d extension=gd -d file_uploads=1, about 4 min), Node OK, PHPStan max OK, composer audit OK, header/SQL/escaping/URL checks OK. Icons: templates/partials/icon.php (an icon partial instead of a helper class, so the escaping check K6 needs no change). Play page: tiles open the new game pop-up (POST /play/start); boxes swap in from GET /play/boxes when /play/news reports a new boxes_version (the Play page keeps its own poll every 3 s). Text size and pop-up (A82), strength choices (A83). The rarer challenges (one player, invite link, start position) moved to GET /play/challenge. Tests: tests/Play/NewPlayPageTest.php, tests/Look/DesignSystemTest.php. Open: checks with the user's go; a look on phone, tablet and laptop on the local site.

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
- **Result (2026-11-26)**: built; nothing run yet (tests, PHPStan, audit, checks wait for the user's go).
  - Migration 12; module `src/Domain/Tournament` (Tournaments, TournamentRounds, TournamentDesk, LeagueDivisions, DutchPairing, BergerTables, KnockoutBracket, TieBreaks, TournamentResults); job `TournamentRoundJob`.
  - Pages: `/tournaments` (list, page with five views, PGN), `/tournaments/{id}/manage` (the organiser's desk), `/tournaments/{id}/league` (editor), `/admin/tournaments`.
  - Games::end() tells TournamentResults (K3); Games::correctResult() for corrections; Games::startForTournament() for guests (A109).
  - Tests: `tests/Tournament/` (pairing tables, Dutch, tie-breaks, whole tournaments on the database).
  - Open: the Swiss check against JaVaFo/bbpPairings TRF files needs those files downloaded (A99, asked the user).

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
  - the OAuth flow, including state and PKCE checks (dropped with L-02, A77);
  - the content scanner keeps poisoned PGN out (A81).
- **Done**: works with the switch on, is silent with it off, and the scanner passes security review.
- **Result (2026-11-26)**: built, checked and reviewed (closed by the user 2026-11-26). Login with Lichess was dropped (A77), no iframes (A75).
  - **Learn** in the main menu (`/learn`): puzzles, study themes, my own Lichess games, Lichess TV.
  - **Puzzles** (L-01): 3,334 bundled Lichess puzzles (CC0, `src/Domain/Puzzles/puzzle-set.csv`, built by `tools/build-puzzle-set.php`), 16 motifs × 5 levels, on our own board; no switch, works offline.
  - **Study games** (L-03): admins make themes and fill them from PGN uploads (offline) or Lichess (one game, or a player's newest up to 50); players import one game at a time into a private list (A88). Replay on our own board.
  - **Lichess TV** (L-01): our server asks lichess.org at most every 10 s per channel; the browser asks only our server (CSP stays `'self'`).
  - **Content scanner** (A81): `ContentScanner` + strict `PgnReader`; rejected games go to the audit log.
  - **Safety of the door**: `LichessClient` refuses unless the switch is on; https://lichess.org only, no redirects, one request at a time, 60 s rest after 429, 1 MB reply cap. The switch can only be turned on after "Test connection" passed (A80); it needs curl (ServerSupport).
  - Migration 10 (`study_themes`, `study_games`).
  - Tests: `tests/Lichess/` (ContentScanner, LichessClient with a fake transport, PuzzleSet, Learn pages).
  - **Review round (2026-11-26, A89–A92)**: word filter modes and a starter list (B36), PGN paste in and copy out, players' limits as admin settings (B37), plain-text comments kept (B38), the viewer reports a move it can't follow (B39), own games removed after 30 days unless kept (A92).
  - **Review round 2 (2026-11-26, A93–A96)**: the installer checks lichess.org and says so; Internet connection through a proxy with an encrypted login (SecretBox, config/secret.key); themes picked from Lichess sections and topics plus Openings and Famous games (migration 11); one Lichess page for admins (switch and content kinds) and players (live games, broadcasts, daily puzzle); study import. TV channel keys fixed after checking the live API.
  - **Replay overhaul (2026-11-26, B44, B45, B47, B48)**: one shared replay (`partials/replay.php`, `assets/js/learn/replay.js`) for study, own and broadcast games and Lichess TV: move table, seven controls (|<< << < = > >> >>|, < and > play backwards and forward), last move marked; TV gets the whole game and plays new moves spread over the 10 s until the next update. Also: the content scanner drops a long lichess.org Site address instead of the game.
  - **Not verified**: real requests to lichess.org (TV, game import, player import) were never sent; the first real check is the admin's "Test connection" on the test site.
