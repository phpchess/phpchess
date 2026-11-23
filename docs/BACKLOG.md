# phpChess 6 — Backlog

Work that is agreed but not yet placed in a chunk. Each item gets a chunk (and
feature ids in [FEATURES.md](FEATURES.md)) when it is planned in
[BUILD_PLAN.md](BUILD_PLAN.md).

| # | Item | Added | Notes |
|---|---|---|---|
| B1 | **A plain-language action for every failure, at every stage.** The design so far covers the best case. For every check that can fail and every error an admin can meet (installer checks, database, protection self-test, migrations, jobs, mail, upgrades, the unsafe-state banner), write down what the admin can do, and show that action in the message itself. | 2026-11-22 | Start with a review of the C1-1 installer and checks; then make it part of every chunk's definition of done. |
| B2 | **Colour scheme review** of the default look. | 2026-11-22 | Covers the installer, error and start pages from C1-1 (`public/assets/css/base.css`) and the default skin in C1-2. |
| B3 | **Skin editor in the admin area and seasonal effects** (A24, A25): logo selection, colour schemes, fonts, default board and pieces, layout choices, fun effects by season (by date or by hand). | 2026-11-23 | Moved out of C1-2. The skin format from C1-2 (`skin.json` + generated `theme.css`) is designed so this editor only edits `skin.json`. |
| B4 | **Theme ideas from other systems**: look at the themes of commonly used content management systems (WordPress and similar) and student portals for ideas on layouts, colour schemes and what admins expect to change. | 2026-11-23 | Input for B2 and B3. |
| B5 | **Common-password check** (ASVS 2.1.7): refuse new passwords that are on a local list of the most common or breached passwords. The list ships with phpChess (no outside requests, A16). | 2026-11-23 | Found in the Block 1 security review (`docs/proofs/security-review-block1.md`). |
