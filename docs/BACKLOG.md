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
| B5 | **Done 2026-11-23.** **Common-password check** (ASVS 2.1.7): refuse new passwords that are on a local list of the most common or breached passwords. The list ships with phpChess (no outside requests, A16). | 2026-11-23 | Found in the Block 1 security review (`docs/proofs/security-review-block1.md`). |
| B6 | **Conditional moves** in correspondence (A36). | 2026-11-23 | |
| B7 | **Admin login page**: remove "register" from it, keep "forgot password". | 2026-11-23 | |
| B8 | **OpenID Connect login** (to consider). | 2026-11-23 | Must fit A16 (no outside requests except the chosen provider). |
| B9 | **Sign in with Apple** (to consider). | 2026-11-23 | Needs an Apple developer account. |
| B10 | **Manual approval of new users**, and an optional passcode (e.g. 4 digits) on the registration page. | 2026-11-23 | Admin options. |
| B11 | **Home-hosted server** on a separate Raspberry Pi (Linux) from a home IP (to consider). | 2026-11-23 | Security and dynamic-DNS questions first. |
| B12 | **Two-factor login (2FA)**: an authenticator app code (TOTP) with recovery codes; admins could be required to use it. | 2026-11-23 | Must use a local library or our own readable code (A16, A21). |
| B13 | **Passkeys as the main login** (settled 2026-11-23): passkeys (WebAuthn) with "remember this device"; passwords stay as a fallback. **Young pupils (6–10)** usually share school tablets, where a passkey belongs to the device and not the child, so a teacher prints **QR login cards** per class (scan to log in, the teacher can revoke a card), optionally with a picture PIN. | 2026-11-23 | No outside service needed (A16). Ties in with school mode (C2-2). |
| B14 | **Demo mode** (admin switch): 4 standard demo users anyone can log in with. Blocked for them: password and account changes, rating changes, tournament play, avatar or any upload, messaging and anything else risky. The admin area is shown to them as screenshots only, with no admin rights. | 2026-11-23 | The server must stay safe: every block is enforced on the server, not just hidden in the page. |
| B15 | **Self-rating puzzle test** (A30): an optional short test of 5–10 basic puzzles at registration that suggests the strength band. C2-1 has the drop-down only. | 2026-11-23 | |
| B16 | **Docker image** (INS-09: PHP, web server, MariaDB) for school servers with root access. Taken out of C2-2 (A40): plain shared hosting has no root access, so it has no use there now. | 2026-11-23 | Only if a school with its own server asks. |
| B17 | **Thorough review of C2-2, soon** (user, 2026-11-23). Found already: chat lines can be posted on finished games, and posting one shows the game with the active board colours again. | 2026-11-23 | Before C3-1 starts. |
| B18 | **Class announcements by teachers**: a teacher writes a short message to a class; it shows on those students' start page, with an optional push. Today server messages, news and newsletters are admin-only, and students without email get no newsletter. | 2026-11-23 | School mode (SCH-02). |
| B19 | **Simuls**: one host plays many timed or correspondence games at once. Polling copes; the per-player timed-game limit (A46) must exempt the host. | 2026-11-23 | |
