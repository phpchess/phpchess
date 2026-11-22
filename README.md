# phpChess 6

A self-hosted chess server for clubs, schools and communities: correspondence chess and timed games down to 5+3, tournaments, ratings, and a computer opponent that plays at beginner level. It is built to install on ordinary shared hosting, using PHP and MySQL/MariaDB only.

> **Status: in development. Not usable yet.** There is no installer and no release yet. Follow the build plan in [docs/BUILD_PLAN.md](docs/BUILD_PLAN.md).

phpChess 6 is a complete rebuild. **phpChess 4.x is deprecated, unsupported, and has known critical security issues.** It is kept for the record on the [`4.x` branch](../../tree/4.x) and the `v4.3-final` tag. Do not install it on a public server. There is no upgrade path from 4.x.

## Goals
- **Easy to install**: copy the files, open the site's address and follow the steps. No command line is needed on the host.
- **Mobile first**: install it from the browser to your phone's home screen, with notifications for your moves.
- **Built for schools**: classes, teacher-created student accounts, and features that can be switched off (for example direct messages). It works fully offline on a school network.
- **Secure by design**: see the rules every part keeps in [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md).

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
