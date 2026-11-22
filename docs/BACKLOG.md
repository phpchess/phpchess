# phpChess 6 — Backlog

Work that is agreed but not yet placed in a chunk. Each item gets a chunk (and
feature ids in [FEATURES.md](FEATURES.md)) when it is planned in
[BUILD_PLAN.md](BUILD_PLAN.md).

| # | Item | Added | Notes |
|---|---|---|---|
| B1 | **A plain-language action for every failure, at every stage.** The design so far covers the best case. For every check that can fail and every error an admin can meet (installer checks, database, protection self-test, migrations, jobs, mail, upgrades, the unsafe-state banner), write down what the admin can do, and show that action in the message itself. | 2026-11-22 | Start with a review of the C1-1 installer and checks; then make it part of every chunk's definition of done. |
| B2 | **Colour scheme review** of the default look. | 2026-11-22 | Covers the installer, error and start pages from C1-1 (`public/assets/css/base.css`) and the default skin in C1-2. |
