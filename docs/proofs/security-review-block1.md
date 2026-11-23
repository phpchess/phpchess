# Security review, Block 1 (C1-1 and C1-2)

Date: 2026-11-23. Target: OWASP ASVS 4.0 level 2 (ARCHITECTURE.md §9, A27).

## ZAP scan

- Tool: OWASP ZAP 2.17.0, quick scan (spider plus active scan, no login), on a full copy of the site with the installer removed, served by PHP's built-in server.
- Result: **no Critical, High or Medium findings.**

| Risk | Finding | What was done |
|---|---|---|
| Low | `X-Powered-By` header shows the PHP version | Removed in `Response::send()` |
| Low | Static files (CSS) without `X-Content-Type-Options` | `public/.htaccess` sets `nosniff` for every file (Apache with mod_headers); the pages already sent it |
| Info | Session cookie seen in responses | Expected: `HttpOnly`, `SameSite=Lax`, `Secure` on HTTPS |

The scan was not logged in, so admin pages were only checked for "login required". Their protection is covered by the tests below.

## Found while preparing the scan

- Foreign key names in migration 0003 had no table prefix. MySQL wants them unique per database, so a second install in the same database failed. Now they are written in braces and get the prefix.

## ASVS level 2, by chapter

| Chapter | How phpChess 6 meets it | Proof |
|---|---|---|
| V2 Authentication | Passwords: at least 10 characters, stored with `password_hash`. Login throttled: 5 per name and 20 per IP per 15 minutes. Same message for a wrong name or a wrong password. | `AccountFlowTest` |
| V2.5 Recovery | Reset links: 256-bit random, stored as SHA-256 hashes, single use, expire after 1 hour; the answer never says whether an email exists. A reset logs out every device. | `AccountFlowTest` |
| V3 Sessions | New session id at login and logout; remember-me token rotates on every use, stored as a hash; "log out everywhere". Cookies are `HttpOnly`, `SameSite=Lax`, `Secure` on HTTPS. Admin actions need a password re-check within 30 minutes. | `AccountFlowTest`, `AccessTest` |
| V4 Access control | Every route declares its access level (K14); the Kernel checks it before the handler. Switched-off features answer 404 (K8). Admins can't block or demote themselves; the last admin stays. | `AccessTest` (every route), `AdminActionsTest` |
| V5 Input and output | Every SQL statement is prepared (K5, `check-sql`); every printed value is escaped (K6, `check-escaping`); form values are checked against fixed lists (roles, states, skins). | CI checks |
| V7 Errors and logging | Errors show a plain page, details only in the log; admin actions go to the audit log with who and when. | `AdminActionsTest` |
| V8 Data protection | Responses with personal data are `no-store`; no personal data in URLs. | headers seen in the scan |
| V9 Communication | The admin banner warns while the site isn't on HTTPS. | `AdminActionsTest` |
| V13 / V14 Configuration | CSRF token on every POST (K7); CSP self-only, `frame-ancestors 'none'`, `nosniff`, `Referrer-Policy` (K9); no outside requests; libraries checked (`composer audit`, LIBRARIES.md); the installer deletes itself and the banner warns if its files remain. | `KernelTest`, CI |

## Open points (not blockers)

- ASVS 2.1.7: new passwords are not yet checked against a list of common or breached passwords. Needs a local list (no outside requests, A16); backlog B5.
- Logged-in ZAP scan of the admin area: at the end of Block 2, with ZAP's authentication set up.
- Hosts without mod_headers don't get `nosniff` on static files; there's nothing more a shared-hosting site can do here.
