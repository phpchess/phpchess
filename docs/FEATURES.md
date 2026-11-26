# phpChess 6 — Functionality list

Every feature phpChess 6 has, will have, or has dropped on purpose. Each one has an id, which the build plan's chunks ([BUILD_PLAN.md](BUILD_PLAN.md)) and the tests refer to.

**Columns:**
- **Origin**:
  - *4.4*: kept from phpChess 4.4, rebuilt;
  - *redesign*: 4.4 had it, but it is designed anew;
  - *new*: not in 4.4.
- **Chunk**: the chunk in [BUILD_PLAN.md](BUILD_PLAN.md) that builds it.
- **Switch**: the install-level setting that can turn the feature off (school mode, A13). The server enforces every switch, and the UI hides the feature when it is off.
- **Status**: planned · building · built. Flip it in the same session the chunk ships.

Decisions A1–A21 are in [DECISIONS.md](DECISIONS.md).

## Principles every feature follows
1. **Readable, documented code (A21).** Every file can be read, searched and understood by a person, including a PHP student, without tools or AI. Readability is never traded for efficiency, performance or brevity. This governs how every feature below is built; see [ARCHITECTURE.md §11](ARCHITECTURE.md#11-code-conventions-readable-by-a-person-a21).
2. **Mobile first (A18).** Every page is designed for a phone held in one hand first, then grown to tablet and desktop. Touch is the main input: drag-and-drop and tap-tap moves, large targets, no hover-only actions. Installed from the browser to the home screen, phpChess behaves like an app and receives notifications there (Web Push), on iPhone (iOS 16.4 or later, after "Add to Home Screen") and on Android. No native app is planned; one would be built only if Web Push proves not good enough in C2-2.
3. **Improve, don't replicate (A19).** A 4.4 feature is the starting point, not the specification. Each one is rebuilt the way a player today expects it: starting a game is one tap, there is no wall between correspondence and live play, and the site knows who is online and acts on it.
4. **Modern capabilities throughout (A20).** Wherever the browser offers a better way, it is used: drag-and-drop for moves, lists, divisions and uploads; live updates instead of reloads; share links and QR codes for invitations; offline viewing of your own games; dark mode. Each still follows A16: local libraries only, nothing that reaches outside the install, except push, which runs only while the "push notifications" switch is on.

## INS — Install and operation
| Id | Feature | Origin | Chunk | Switch | Status |
|---|---|---|---|---|---|
| INS-01 | Release zip with `vendor/` and all JavaScript included; no Composer, npm or shell on the host (A17) | new | C1-1 | — | done (2026-11-22) |
| INS-02 | Installer: copy the files, open the address, follow the steps; works in the web root, in a subfolder, or with the domain pointed at `public/` | redesign | C1-1 | — | done (2026-11-22) |
| INS-03 | Pre-flight checks: PHP and extensions, database version and `sql_mode`, writable data folder, `.htaccess` honoured, HTTPS | redesign | C1-1 | — | done (2026-11-22) |
| INS-04 | Self-test that non-public folders are unreachable; stops with a plain-language fix, and shows an nginx snippet for a VPS | new | C1-1 | — | done (2026-11-22) |
| INS-05 | Migrations: numbered, resumable, table prefix, never drop what they didn't create; also used for upgrades to later 6.x versions | redesign | C1-1 | — | done (2026-11-22) |
| INS-06 | Installer locks itself after installing, for good; updates run from the admin banner ("Run updates now", A54) | redesign | C1-1 | — | done (2026-11-22; changed 2026-11-24, A54) |
| INS-07 | Unsafe-state banner on every admin page: install folder reachable, config writable, errors displayed, no HTTPS, mail not set up, jobs not running for more than 24 h, migrations pending | redesign | C1-1 | — | done (2026-11-22) |
| INS-08 | Job runner: real cron when present, otherwise pseudo-cron triggered by page requests, guarded by a database lock | redesign | C1-1 | — | done (2026-11-22) |
| INS-09 | Docker image (PHP, web server, MariaDB) for school servers | new | backlog (B16) | — | moved to backlog (A40) |
| INS-10 | Fully offline operation: every asset served locally, no outside requests (A16) | new | C1-1 | — | done (2026-11-22) |

## ID — Identity and accounts
| Id | Feature | Origin | Chunk | Switch | Status |
|---|---|---|---|---|---|
| ID-01 | Registration with a password the user chooses | redesign | C1-2 | public registration | done |
| ID-02 | Email verification | new | C1-2 | email requirement | done |
| ID-03 | Registration needs admin approval (setting) | 4.4 | C1-2 | — | done |
| ID-04 | Login with throttling per account and IP; session id regenerated | redesign | C1-2 | — | done |
| ID-05 | Remember-me by a hashed token that rotates | redesign | C1-2 | — | done |
| ID-06 | Password reset by a single-use, expiring token | redesign | C1-2 | — | done |
| ID-07 | Account states: pending, active, disabled, archived (one field, not separate tables) | redesign | C1-2 | — | done |
| ID-08 | Roles: admin, teacher, student, player; admin actions need re-authentication | new | C1-2 | — | done |
| ID-09 | Rename keeps the account id | redesign | C1-2 | — | done |
| ID-10 | API access tokens per device, revocable in settings | redesign | C2-2 | — | done (C2-2, 2026-11-23) |
| ID-11 | The user's own data export and account deletion | new | C2-2 | — | done (C2-2, 2026-11-23) |
| ID-12 | Presence (online / offline), kept separate from account state | 4.4 | C2-1 | player list visibility | built |
| ID-13 | User preferences: board, pieces, language, notifications | 4.4 | C1-2 | — | done (notification choices come with the notifications) |

## SCH — School mode (A13)
| Id | Feature | Origin | Chunk | Switch | Status |
|---|---|---|---|---|---|
| SCH-01 | Feature switches page: every switch named in this list | new | C1-2 | — | done |
| SCH-02 | Classes (groups) with their members | new | C2-2 | school mode | done (C2-2, 2026-11-23) |
| SCH-03 | Teachers create student accounts in bulk, no email needed | new | C2-2 | school mode | done (C2-2, 2026-11-23) |
| SCH-04 | One-time login cards with QR codes (A41) and password resets by the teacher | new | C2-2 | school mode | done (C2-2, 2026-11-23) |
| SCH-05 | Students can be limited to playing within their class | new | C2-2 | school mode | done (C2-2, 2026-11-23) |
| SCH-06 | Teachers see their students' games | new | C2-2 | school mode | done (C2-2, 2026-11-23) |
| SCH-07 | Retention by performance: daily speed check, archive file kept by the admin, read back in (A42, A43) | new | C2-2 | — | done (C2-2, 2026-11-23) |

## G — Play
| Id | Feature | Origin | Chunk | Switch | Status |
|---|---|---|---|---|---|
| G-01 | One server rules engine: legality, check, mate, stalemate, threefold repetition, 50-move rule, insufficient material | redesign | C2-1 | — | built |
| G-02 | One move function: turn, legality, clock, game end, all in one locked transaction; repeated requests are harmless | redesign | C2-1 | — | built |
| G-03 | One game-end function: result plus reason, written once; every way a game ends goes through it | redesign | C2-1 | — | built |
| G-04 | Board sized for a phone: drag-and-drop or tap-tap moves, instant feedback, keyboard play on desktop; the server checks every move | redesign | C2-1 | — | built |
| G-05 | Open and direct challenges: colour, time control, rated or not, expiry; an open challenge can be accepted only once | redesign | C2-1 | — | built |
| G-06 | Game from a custom start position (FEN) | 4.4 | C2-1 | — | built |
| G-07 | **One game model**: any time control, from 5+3 up to days per move, in the same kind of game; no separate correspondence and live modes (A19) | redesign | C2-1 | — | built |
| G-08 | Vacation in correspondence | new | C2-1 | — | built |
| G-09 | Draw offer and acceptance, resignation, abort before the first moves | 4.4 | C2-1 | — | built |
| G-10 | **Presence-aware delivery**: while both players are on the game page, moves appear live; if the opponent leaves, they get an in-app or push notice; if they are offline for a set time, a move email. Never duplicated. The player sets their preferences (N-01–N-04) | redesign | C2-1 | — | built |
| G-11 | Game archive, game viewer with replay, PGN export | 4.4 | C2-1 | — | built |
| G-12 | Game chat: off, fixed phrases only, or free | 4.4 | C2-2 | game chat | done (C2-2, 2026-11-23) |
| G-13 | Watching other players' games | 4.4 | C2-1 | player list visibility | built |
| G-14 | Clock with base plus increment for the faster controls in G-07; shortest 5+3, admin can raise it (A12) | redesign | C3-1 | — | built |
| G-15 | Server-authoritative clock with lag compensation | new | C3-1 | — | built |
| G-16 | Lobby: post a game offer, accept one; abort when no first move is made; disconnect handling | new | C3-1 | — | built |
| G-17 | Premoves in timed games | new | C3-1 | — | built |
| G-18 | Admin limit for the number of live timed games | new | C3-1 | — | built |
| G-19 | Conditional (pre-entered) moves in correspondence | new | later | — | planned |
| G-20 | **One-tap start**: a "Play" button with remembered defaults (time control, rated or not); quick pairing with whoever is waiting, or a challenge to a friend | new | C2-1 | — | built |
| G-21 | Invite by share link or QR code (Web Share on phones), e.g. a teacher pairing a class | new | C2-1 | — | built |
| G-22 | Rematch with one tap | new | C2-1 | — | built |
| G-23 | A game can switch pace when both players agree (e.g. play live while both are online, then continue as correspondence) | new | C3-1 | — | built |

## N — Notifications
| Id | Feature | Origin | Chunk | Switch | Status |
|---|---|---|---|---|---|
| N-01 | In-app notices: your move, challenges, messages, tournament rounds; a badge count | redesign | C2-1 | — | built |
| N-02 | Move and challenge emails when offline, queued and throttled, with a one-click link to the game | 4.4 | C2-1 | email requirement | built |
| N-03 | Web Push to the installed app (iPhone and Android) and to desktop browsers; opt-in after a tap, revocable | new | C2-2 | push notifications | built (C2-2, 2026-11-23); real-phone test pending (A18) |
| N-04 | Quiet hours and per-kind choice (push, email, none) | new | C2-2 | — | done (C2-2, 2026-11-23) |

## R — Ratings
| Id | Feature | Origin | Chunk | Switch | Status |
|---|---|---|---|---|---|
| R-01 | Elo per speed (correspondence, rapid, blitz), applied once per game | redesign | C2-1 | — | built |
| R-02 | Provisional ratings for new players | new | C2-1 | — | built |
| R-03 | Rating history and chart | new | C2-1 | — | built |
| R-04 | Rating list, top players, player statistics | 4.4 | C2-1 | player list visibility | built |
| R-05 | "Rated" is a property of the game, respected by every way it ends | redesign | C2-1 | — | built |

## AI — Computer opponent (A14)
| Id | Feature | Origin | Chunk | Switch | Status |
|---|---|---|---|---|---|
| AI-01 | Engine in the player's browser (Web Worker), swappable; GarboChess, with a how-to for other engines (A47) | new | C3-2 | — | done (2026-11-25) |
| AI-02 | Selection layer: chooses among the engine's candidate moves by score closeness | new | C3-2 | — | done (2026-11-25) |
| AI-03 | Opponent characters with their own habits and strengths | new | C3-2 | — | done (2026-11-25) |
| AI-04 | Fixed search budget in nodes or depth, the same on every device | new | C3-2 | — | done (2026-11-25) |
| AI-05 | Calibration: own ladder, ~600 Elo and below, result published with its confidence range | new | C3-2 | — | done (2026-11-25) |
| AI-06 | Games against the computer stored, unrated | new | C3-2 | — | done (2026-11-25) |
| AI-07 | Admin page: active engine, name, version, licence, source link | new | C3-2 | — | done (2026-11-25) |
| AI-08 | Computer move delay chosen by the member: none, short, normal, long (B30, A65) | new | F1 | — | done (2026-11-25), review pending |
| ID-14 | Strength test: 10 adaptive Lichess puzzles (CC0), pop-up at first login, link next to the rating (B15, A59, A60) | new | F1 | — | done (2026-11-25), review pending |
| UI-10 | Site-wide news poll: timed-challenge pop-up with Accept/Decline, message badge (B21, A63) | new | F1 | — | done (2026-11-25), review pending |
| UI-11 | Design system: button-centred, full width, bottom tab bar on phones, side bar on wider screens, no text below 11 px (B29, A66, A68, A72, A74) | new | F2 | — | built (2026-11-26), review pending |
| UI-12 | Icons drawn by code (inline SVG in the text colour), restyled with the look (A67) | new | F2 | — | built (2026-11-26), review pending |
| G-24 | Play page: tiles for Computer, Live, Correspondence; new game pop-up of choice buttons; live boxes for games and challenges; admin news (A69, A70, A75) | new | F2 | — | built (2026-11-26), review pending |
| ADM-08 | Admin pages full width with a grouped side menu, sticky table headers, switches (B28, A71) | new | F2 | — | built (2026-11-26), review pending |

## T — Tournaments
| Id | Feature | Origin | Chunk | Switch | Status |
|---|---|---|---|---|---|
| T-01 | One tournament model: formats, registration, organisers, schedule, status | redesign | C4-1 | — | planned |
| T-02 | Round Robin with Berger tables and proper byes | redesign | C4-1 | — | planned |
| T-03 | Swiss (Dutch system) | new | C4-1 | — | planned |
| T-04 | Knockout | new | C4-1 | — | planned |
| T-05 | League: divisions, promotion and relegation; drag-and-drop to place and order players (A15) | redesign | C4-1 | — | planned |
| T-06 | Tie-breaks: Buchholz, Sonneborn-Berger, direct encounter | new | C4-1 | — | planned |
| T-07 | Standings, crosstables, PGN export of a tournament | 4.4 | C4-1 | — | planned |
| T-08 | Rounds scheduled by the job runner; forfeits only by a configured, visible rule | redesign | C4-1 | — | planned |
| T-09 | Team vs Team (A10) | 4.4 | C4-2 | — | planned |
| T-10 | Arena (timed play) | new | C4-2 | — | planned |
| T-11 | Voting tournaments (A7): one player against a group voting each move; the server resolves the vote on time, with a stated tie rule | redesign | C4-2 | — | planned |
| T-12 | Tournament chat and spectators | 4.4 | C4-2 | game chat | planned |
| T-13 | Teacher-run tournaments for a class | new | C4-2 | school mode | planned |

## C — Community and content
| Id | Feature | Origin | Chunk | Switch | Status |
|---|---|---|---|---|---|
| C-01 | Profiles | 4.4 | C2-2 | public profiles | done (C2-2, 2026-11-23) |
| C-02 | Avatars: bundled sets and uploads (validated, re-encoded) | 4.4 | C2-2 | file uploads | done (C2-2, 2026-11-23) |
| C-03 | Buddy list | 4.4 | C2-2 | — | done (C2-2, 2026-11-23) |
| C-04 | Direct messages | 4.4 | C2-2 | direct messages | done (C2-2, 2026-11-23) |
| C-05 | Word filter on chat, messages and names | 4.4 | C2-2 | — | done (C2-2, 2026-11-23) |
| C-06 | News on the front page | 4.4 | C2-2 | — | done (C2-2, 2026-11-23) |
| C-07 | Server messages to all users | 4.4 | C2-2 | — | done (C2-2, 2026-11-23) |
| C-08 | Tip of the day | 4.4 | C2-2 | — | done (C2-2, 2026-11-23) |
| C-09 | FAQ and static pages | 4.4 | C2-2 | — | done (C2-2, 2026-11-23) |
| C-10 | Who's online | 4.4 | C2-2 | player list visibility | done (C2-2, 2026-11-23) |

## UI — Look and use
| Id | Feature | Origin | Chunk | Switch | Status |
|---|---|---|---|---|---|
| UI-01 | Skins: CSS variables and assets, no code (A5) | redesign | C1-2 | — | done |
| UI-02 | Appearance page: create and edit skins in the admin area: logo, colour schemes, fonts, default board and pieces, layout choices, seasonal fun effects; no code changes (A24) | new | backlog (B3) | — | planned |
| UI-03 | Mobile-first layout: phone first, then tablet and desktop; touch targets, one-hand use; dark mode (A18) | new | C1-2 | — | done |
| UI-04 | Accessibility: contrast, keyboard, screen-reader labels | new | C1-2 | — | done |
| UI-05 | Installable as an app (PWA): home-screen icon, full-screen, start page, app badge | new | C2-2 | — | done (C2-2, 2026-11-23) |
| UI-08 | Offline viewing of your own games and the last state of running games; moves queue until the connection returns | new | C2-2 | — | done (C2-2, 2026-11-23) |
| UI-09 | Drag-and-drop wherever things are arranged: board, League divisions, admin lists and ordering, avatar upload | new | C1-2 (base), later chunks per use | — | base done (SortableJS, `sortable-list.js`); each use in its own chunk |
| UI-06 | Language files; English first, the 4.4 strings reused as a base | 4.4 | C1-2 | — | done |
| UI-07 | Server admin template override (`templates/local/`) | new | C1-2 | — | done |

## API — Client access
| Id | Feature | Origin | Chunk | Switch | Status |
|---|---|---|---|---|---|
| API-01 | JSON API v1, used by the web client itself; reads by GET, all changes by POST | redesign | C1-1 (skeleton), C2-2 | — | done (C1-1 skeleton; C2-2 complete, 2026-11-23) |
| API-02 | Polling for game state with `since=<ply>`; ETag/304 on lists | redesign | C2-1 | — | built |
| API-03 | Short-poll transport, adaptive interval, behind an interface | new | C3-1 | — | built |
| API-04 | Rate limits and one error format for every endpoint | new | C1-1 | — | done (2026-11-22) |

## ADM — Administration
| Id | Feature | Origin | Chunk | Switch | Status |
|---|---|---|---|---|---|
| ADM-01 | Users: list, approve, disable, archive, roles | 4.4 | C1-2 | — | done |
| ADM-02 | Settings, mail (SMTP, or a mail log file for development and offline installs), feature switches | 4.4 | C1-2 | — | done |
| ADM-03 | Audit log of admin actions | new | C1-2 | — | done |
| ADM-04 | Games: list, adjudicate through the game-end function (G-03), admin FEN game setup | 4.4 | C2-1 | — | built |
| ADM-05 | Content: news, server messages, tips, FAQ, pages | 4.4 | C2-2 | — | done (C2-2, 2026-11-23) |
| ADM-06 | Tournaments: create, edit, run, correct results through the game-end function | 4.4 | C4-1 | — | planned |
| ADM-07 | Newsletter to all users (admin only, queued, throttled) | 4.4 | C2-2 | — | done (C2-2, 2026-11-23) |

## L — Lichess (A9)
| Id | Feature | Origin | Chunk | Switch | Status |
|---|---|---|---|---|---|
| L-01 | Embedded Lichess learning content and puzzles | new | C5-1 | external content | planned |
| L-02 | Login with Lichess / account link (OAuth with PKCE) | new | C5-1 | external content | dropped (A77) |
| L-03 | Game import and study links through the Lichess API | new | C5-1 | external content | planned |

## Dropped by decision
| 4.4 feature | Decision |
|---|---|
| Mobile site, XML API `mobile.php`, J2ME, widgets, Java applet | A2; replaced by the responsive site, the PWA and API v1 |
| PayPal, credits, redemption codes | A2 |
| Clubs | A8 |
| Activities (paid lessons) | A9; replaced by Lichess (L) |
| League A/B as built in 4.4 | A15; replaced by T-05 |
| Bullet, 3+0, 3+2, WebSocket transport | A12 |
| Separate correspondence and real-time game types | A19; one game model (G-07) |
| Native mobile apps (iOS, Android) | A18; the PWA with Web Push instead, unless C2-2 shows it isn't enough |
| Upgrade from 4.x, data migration | A3 |
| Captcha with audio | replaced by throttling and registration approval |
| PHP skins, TinyMCE 3, old jQuery, Prototype, the old PGN viewer | A5, A16 |
| "King captured" ending, dead game-state code | not a chess rule; G-01 covers the real endings |
