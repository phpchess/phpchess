# phpChess 6

A self-hosted chess server for clubs, schools and communities: correspondence chess and timed games down to 5+3, tournaments, ratings, and a computer opponent that plays at beginner level. It is built to install on ordinary shared hosting, using PHP and MySQL/MariaDB only.

> **Status: in development. Not usable yet.** There is no installer and no release yet. Follow the build plan in [docs/BUILD_PLAN.md](docs/BUILD_PLAN.md).

phpChess 6 is a complete rebuild. **phpChess 4.x is deprecated, unsupported, and has known critical security issues.** It is kept for the record on the [`4.x` branch](../../tree/4.x) and the `v4.3-final` tag. Do not install it on a public server. There is no upgrade path from 4.x.

## Goals
- **Readable code**: every file is written so that a person can read it, find where to change something and understand it, without tools or AI. PHP students can study it on their own. Readability is never traded for speed or brevity; it is what makes the code easy to audit, secure and transparent. See the [code conventions](docs/ARCHITECTURE.md#11-code-conventions-readable-by-a-person-a21).
- **Easy to install**: copy the files, open the site's address and follow the steps. No command line is needed on the host.
- **Mobile first**: install it from the browser to your phone's home screen, with notifications for your moves.
- **Built for schools**: classes, teacher-created student accounts, and features that can be switched off (for example direct messages). It works fully offline on a school network.
- **Secure by design**: see the rules every part keeps in [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md).

## Development progress

phpChess 6 is built in large chunks, each a working, tested whole. The full list of features, with ids, is in [docs/FEATURES.md](docs/FEATURES.md); each chunk below names the features it builds in [docs/BUILD_PLAN.md](docs/BUILD_PLAN.md).

| Chunk | What it delivers | Status |
|---|---|---|
| C0-1 Definition | Feature list, architecture, build plan, decisions | ✅ Done |
| C0-2 Proofs | Library check, engine-in-the-browser test, development environment, automated checks (CI) | ✅ Done |
| C1-1 Application core and install | Request handling, database and migrations, the installer, the release zip, background jobs | ✅ Done |
| C1-2 Identity, admin and look | Accounts and roles, admin area, feature switches, mobile-first layout, skins, languages | ✅ Done |
| C2-1 Chess play | Rules, one-tap start, invites, one game model for every pace, live moves and notices, ratings | ⏳ Next |
| C2-2 Community, school and app | Messages, profiles, school mode (classes, teacher accounts), installable app with push notifications | Planned |
| C3-1 Timed play | Clocks with increment, lobby, changing pace mid-game | Planned |
| C3-2 Computer opponent | Engine in the browser, beginner-level characters (~600 Elo), calibration | Planned |
| C4-1 Tournaments | Round Robin, Swiss, knockout, League with drag-and-drop | Planned |
| C4-2 Special tournaments | Team vs Team, Arena, voting tournaments, class tournaments | Planned |
| C5-1 Lichess | Lichess learning content and login (optional, off for offline schools) | Planned |

**Built so far**
- Project set-up: Composer (development tools only, no runtime packages), PHPUnit tests and PHPStan analysis at its strictest level, both passing.
- The four planning documents in `docs/`, plus the [library table](docs/LIBRARIES.md).
- Automated checks on every push: known vulnerabilities, a header comment in every PHP file, tests, and static analysis.
- The computer opponent's engine proven in the browser: GarboChess scores every candidate move, finds mates and hanging pieces, and is fast enough on an iPhone ([engine proof](docs/proofs/engine-proof.md)).
- The 4.x code frozen on the `4.x` branch with a security notice.
- **Install without skills**: copy the release zip's files to any PHP + MySQL host and open the address. The installer checks the server, tests that private folders are protected, finds or creates the database from the hosting login, creates the admin account and the tables, then removes itself.
- The application core: request handling with security headers and CSRF protection, the database layer and resumable migrations, background jobs (cron or page-triggered), rate limits, and the first API endpoint (`/api/v1/status`).

## Documentation
- [Features](docs/FEATURES.md): what phpChess 6 does, and what was dropped on purpose.
- [Architecture](docs/ARCHITECTURE.md): how it is built, and why.
- [Build plan](docs/BUILD_PLAN.md): the chunks, in order, with their status.
- [Decisions](docs/DECISIONS.md): the decisions behind it.

## Requirements (planned)
- PHP 8.3 or newer.
- MariaDB 10.11 or newer, or MySQL 8.0 or newer.
- Apache or LiteSpeed (most shared hosts), or nginx with the provided configuration.

## Licence
GNU Affero General Public License v3.0 or later. If you run a modified version for others, you must offer them its source code.
