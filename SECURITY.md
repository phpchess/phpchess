# Security - phpChess 4.x

**phpChess 4.x is unsupported. No security fixes will be made.** Do not run it on a server reachable from the internet.

Known issues, found in a code review of the 4.x code base (the review was done on 4.4; 4.3 shares the same code and design):
- **SQL injection throughout**: request values are concatenated into SQL, with no prepared statements.
- **Admin area not protected**: a failed login check sends a redirect but the page keeps running.
- **Re-runnable installer**: the installer can be run again after installation.
- **Weak passwords**: passwords are hashed with salted md5, and an auto-login cookie holds the password.
- **Sessions**: the session check can be bypassed with a crafted session id.
- **Uploads**: unrestricted file upload in the admin area, which allows code execution.
- **Missing protections**: no CSRF protection, and widespread cross-site scripting.

phpChess 6 is a rebuild designed to avoid these issues; see the main branch. Please report issues for phpChess 6 through GitHub's private vulnerability reporting on this repository once 6.0 is released.
