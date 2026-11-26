# phpChess 6 — working notes for Claude

A self-hosted chess server (PHP 8.3+, MariaDB/MySQL, plain shared hosting). Complete rebuild of phpChess 4.x; 4.x is frozen on the `4.x` branch.

## Where things live (read only what the task needs)
- `docs/BUILD_PLAN.md`: the chunks, their scope (feature ids), contracts, tests and status. **Start here.**
- `docs/FEATURES.md`: every feature with id, chunk, switch and status.
- `docs/ARCHITECTURE.md`: layout, modules, contracts K1–K15, code conventions (§11).
- `docs/DECISIONS.md`: the user's decisions A1–A21. New decisions are added here.
- `docs/LIBRARIES.md`: every third-party library, licence, check and status.
- `docs/BACKLOG.md`: agreed work not yet placed in a chunk (B1, B2, ...).
- Local setup (XAMPP, databases): `C:\Users\chriz\CBPROJS\htdocs\phpchess44\LOCAL_SETUP.md` (outside the repo).

## Rules that override everything else
- **Readable code (A21, K15)**: a person, including a PHP student, must be able to read and change any file without tools. Never trade readability for speed or brevity. Header comment on every file, clear names, labelled segments.
- **Mobile first (A18)**, improve-don't-replicate (A19), local libraries only and no outside requests (A16).
- **Chunks are large and stop for review**: finish a chunk, flip its status in BUILD_PLAN.md, FEATURES.md and the README progress table, then stop and ask before the next one.
- **Definition of done**: tests, PHPStan max, `composer audit`, `php tools/check-headers.php` all pass (same as CI).
- Never push, post or change GitHub settings without asking. Never run `git config`.

## Working style
- Token-friendly: short replies, results and decisions only. Side discussions get recorded in DECISIONS.md and kept short.
- Number questions Q1, Q2, ... when asking more than one.

## Tools
- PHP: `C:\Users\chriz\CBPROJS\htdocs\xampp\php\php.exe`; Composer: `tools/composer.phar`.
- Three copies only (2026-11-25): this folder is development only and is **not served** (no install here; the installer would delete src/Install). Test deploy: http://127.0.0.1/pc6sub/phpchess/ (folder htdocs/pc6sub/phpchess, outside git), updated from the release zip keeping its config/config.php, storage/ and closed installer. Git: GitHub. Databases (A22): `phpchess` (deploy) and `phpchess_support` (tests); user `pc60`, credentials in `xampp\passwords_pc60.txt`. Tests read the database from `PHPCHESS_TEST_DB_*` environment variables (`tests/Support/TestDatabase.php`).
- Checks: `php tools/check-headers.php`, `php tools/check-sql.php` (K5), `php tools/check-escaping.php` (K6), `php tools/check-external-urls.php` (K9); release zip: `php tools/build-release.php --composer="<php> tools/composer.phar"`.

## Status
C0-1 to C3-2, F1 and F2 done (F2 on 2026-11-26, reviewed); C5-1 done (2026-11-26, reviewed); pending: C2-2's real-phone Web Push test (A18). C4-1 built 2026-11-26 (A97–A110, migration 12), all checks pass, waiting for review. Next: **C4-2 Special formats**. Load test: `php tools/load-test.php` (docs/proofs/c3-1-load-test.md). Local test install: `htdocs/pc6sub/phpchess` (database `phpchess`, prefix `phpc_`).
