<?php

/**
 * English texts (UI-06, A26). The complete language and the fallback for every other one.
 *
 * Keys name where a text is used: "page.part". Parts in braces, such as
 * {name}, are filled in by the code. Wording from phpChess 4.4
 * (lang/reference/en-4.4.php) is reused where it fits.
 * A new language is a copy of this file with the texts translated, saved as
 * lang/<code>.php (for example lang/de.php), plus its name under "language.".
 */

declare(strict_types=1);

return [
    // --- Languages ---
    'language.en' => 'English',

    // --- Layout ---
    'layout.skip_to_content' => 'Skip to the content',
    'layout.menu' => 'Main menu',
    'layout.login' => 'Log in',
    'layout.logout' => 'Log out',
    'layout.admin' => 'Admin',
    'layout.footer' => 'Powered by phpChess',

    // --- Start page ---
    'home.title' => 'Welcome',
    'home.heading' => 'Chess, at your own pace',
    'home.welcome_visitor' => 'Play correspondence and timed games with your club, your class or your friends.',
    'home.welcome_member' => 'Welcome back, {name}. Games arrive with the next update of this site.',
    'home.version' => 'phpChess {version}',

    // --- Shared account texts ---
    'account.username' => 'User name',
    'account.email' => 'Email',
    'account.password' => 'Password',
    'account.password_again' => 'Retype password',
    'account.username_rules' => 'The user name needs 3 to 40 letters, digits, ".", "-" or "_".',
    'account.email_invalid' => 'Please enter a valid email address.',
    'account.password_too_short' => 'The password needs at least {length} characters.',
    'account.password_is_username' => 'The password may not be the same as the user name.',
    'account.passwords_differ' => 'The two passwords are not the same.',
    'account.too_many_tries' => 'Too many tries. Please wait an hour and try again.',
    'account.not_found' => 'This account does not exist.',

    // --- Registration (ID-01) ---
    'register.title' => 'Register',
    'register.button' => 'Create my account',
    'register.have_account' => 'Already have an account? Log in.',
    'register.username_taken' => 'Sorry, the user name that you selected is already taken, please select another name.',
    'register.email_taken' => 'An account with this email address already exists. Use "Forgot your password?" if it is yours.',
    'register.email_failed' => 'Your account was created, but the email with the confirmation link could not be sent. Log in to send it again, or ask the admin.',
    'register.done_active' => 'Your account is ready. Please log in.',
    'register.done_check_email' => 'Almost done: we sent you an email. Open the link in it to confirm your address.',
    'register.done_wait_for_approval' => 'Thank you. An admin will check your registration; you can log in once it is approved.',

    // --- Email verification (ID-02) ---
    'verify.title' => 'Confirm your email address',
    'verify.explain' => 'Press the button to confirm that this email address is yours.',
    'verify.button' => 'Confirm my email address',
    'verify.link_invalid' => 'This confirmation link is not valid any more. Log in to get a new one.',
    'verify.done_active' => 'Your email address is confirmed. Please log in.',
    'verify.done_wait_for_approval' => 'Your email address is confirmed. An admin will now check your registration.',
    'verify.send_again' => 'Send the confirmation link again',
    'verify.sent_again' => 'If the account is waiting for confirmation, a new link is on its way.',

    // --- Login (ID-04, ID-05) ---
    'login.title' => 'Log in',
    'login.name_or_email' => 'User name or email',
    'login.remember_me' => 'Keep me logged in on this device',
    'login.button' => 'Log in',
    'login.forgot_password' => 'Forgot your password?',
    'login.no_account' => 'No account yet? Register.',
    'login.wrong_name_or_password' => 'The user name or the password is wrong. Please try again.',
    'login.too_many_tries' => 'Too many tries. Please wait {minutes} minutes and try again.',
    'login.account_blocked' => 'This account is disabled. Please contact the admin.',
    'login.account_pending' => 'This account is not active yet.',
    'login.email_not_verified' => 'Please confirm your email address first: open the link in the email we sent you.',
    'login.waiting_for_approval' => 'An admin has not approved your registration yet.',
    'logout.done' => 'You are logged out.',

    // --- Password reset (ID-06) ---
    'forgot.title' => 'Forgot your password?',
    'forgot.explain' => 'Enter the email address of your account. We will send you a link to set a new password.',
    'forgot.button' => 'Send the link',
    'forgot.sent' => 'If an account has this email address, an email with a link is on its way. The link works for one hour.',
    'reset.title' => 'Set a new password',
    'reset.button' => 'Save the new password',
    'reset.link_invalid' => 'This link is not valid any more. Please ask for a new one.',
    'reset.done' => 'Your new password is saved. Please log in.',

    // --- Preferences and password (ID-13) ---
    'preferences.title' => 'My preferences',
    'preferences.language' => 'Language',
    'preferences.colour_scheme' => 'Light or dark',
    'preferences.colour_scheme.auto' => 'Like my device',
    'preferences.colour_scheme.light' => 'Light',
    'preferences.colour_scheme.dark' => 'Dark',
    'preferences.board' => 'Board colours',
    'preferences.board.green' => 'Green',
    'preferences.board.brown' => 'Brown',
    'preferences.board.blue' => 'Blue',
    'preferences.board.grey' => 'Grey',
    'preferences.pieces' => 'Pieces',
    'preferences.pieces.standard' => 'Standard',
    'preferences.email_notices' => 'Send me email notices',
    'preferences.button' => 'Save',
    'preferences.saved' => 'Your preferences are saved.',
    'password.title' => 'Change Password',
    'password.old' => 'Old Password',
    'password.new' => 'New Password',
    'password.button' => 'Change',
    'password.old_wrong' => 'Your old password is wrong. Please try again.',
    'password.changed' => 'Your password is changed. Other devices must log in again.',

    // --- Account states and roles (ID-07, ID-08) ---
    'state.pending' => 'Waiting',
    'state.active' => 'Active',
    'state.disabled' => 'Disabled',
    'state.archived' => 'Archived',
    'role.admin' => 'Admin',
    'role.teacher' => 'Teacher',
    'role.student' => 'Student',
    'role.player' => 'Player',

    // --- Emails ---
    'email.verify.subject' => 'Confirm your email address for {site}',
    'email.verify.text' => "Hello {name},\n\nplease confirm your email address for {site} by opening this link:\n\n{link}\n\nThe link works for two days. If you did not register, you can ignore this email.",
    'email.reset.subject' => 'A new password for {site}',
    'email.reset.text' => "Hello {name},\n\nsomeone (hopefully you) asked for a new password for {site}. To set one, open this link:\n\n{link}\n\nThe link works for one hour. If you did not ask for it, you can ignore this email; your password stays the same.",

    // --- Admin: shared ---
    'admin.save' => 'Save',
    'admin.saved' => 'Saved.',
    'admin.banner.title' => 'Things to fix',

    // --- Admin: start page and confirmation (ID-08, INS-06) ---
    'admin.dashboard.title' => 'Admin',
    'admin.dashboard.pending' => '{count} registration(s) waiting for approval',
    'admin.dashboard.nothing_waiting' => 'No registrations are waiting.',
    'admin.confirm.title' => 'Confirm your password',
    'admin.confirm.explain' => 'For admin work, please enter your password again. You will not be asked again for 30 minutes while you work.',
    'admin.confirm.button' => 'Confirm',
    'admin.confirm.wrong' => 'The password is wrong.',
    'admin.setup_token.title' => 'Installer link for updates',
    'admin.setup_token.explain' => 'To install an update: upload the new release files, then open the installer with a one-time link. The link works once, for one hour.',
    'admin.setup_token.button' => 'Create an installer link',
    'admin.setup_token.shown_once' => 'Copy this link now; it is shown only once. Upload the new release files first, then open it.',

    // --- Admin: users (ADM-01) ---
    'admin.users.title' => 'Users',
    'admin.users.search' => 'Name or email',
    'admin.users.state' => 'State',
    'admin.users.all_states' => 'All',
    'admin.users.filter' => 'Show',
    'admin.users.none' => 'No accounts found.',
    'admin.users.approve' => 'Approve',
    'admin.users.set_state' => 'Set state',
    'admin.users.role' => 'Role',
    'admin.users.set_role' => 'Set role',
    'admin.users.new_name' => 'New user name',
    'admin.users.rename' => 'Rename',
    'admin.users.saved' => 'The account is changed.',
    'admin.users.not_found' => 'This account or value does not exist.',
    'admin.users.keep_one_admin' => 'Not possible: you can\'t block or demote yourself, and the last active admin must stay.',

    // --- Admin: settings (ADM-02, ID-03) ---
    'admin.settings.title' => 'Settings',
    'admin.settings.site_name' => 'Site name',
    'admin.settings.language' => 'Language for visitors',
    'admin.settings.skin' => 'Skin',
    'admin.settings.approval_required' => 'New registrations need my approval',
    'admin.settings.name_rules' => 'The site name needs 1 to 60 characters.',

    // --- Admin: mail (ADM-02) ---
    'admin.mail.title' => 'Mail',
    'admin.mail.explain' => 'phpChess sends confirmation links and password resets by email. Without a mail server, emails are written to the file storage/logs/mail.log.',
    'admin.mail.transport' => 'How to send',
    'admin.mail.transport_log' => 'Write emails to a log file (no mail server)',
    'admin.mail.transport_smtp' => 'Send through an SMTP mail server',
    'admin.mail.from_address' => 'Sender address',
    'admin.mail.smtp' => 'SMTP server (from your hosting control panel)',
    'admin.mail.smtp_host' => 'Server',
    'admin.mail.smtp_port' => 'Port',
    'admin.mail.smtp_encryption' => 'Encryption',
    'admin.mail.encryption.tls' => 'TLS (usually port 587)',
    'admin.mail.encryption.ssl' => 'SSL (usually port 465)',
    'admin.mail.encryption.none' => 'None',
    'admin.mail.smtp_username' => 'User name',
    'admin.mail.smtp_password' => 'Password',
    'admin.mail.password_kept' => 'A password is saved. Leave the field empty to keep it.',
    'admin.mail.saved_try_test' => 'Saved. Now send a test email to check that it works.',
    'admin.mail.test_button' => 'Send a test email to me',
    'admin.mail.test_subject' => 'phpChess test email',
    'admin.mail.test_text' => "This is a test email from phpChess. If you can read it, mail works.",
    'admin.mail.test_sent' => 'The test email was sent. Check your inbox (and the spam folder).',
    'admin.mail.test_logged' => 'The test email was written to storage/logs/mail.log.',
    'admin.mail.test_failed' => 'The test email could not be sent.',

    // --- Admin: feature switches (SCH-01) ---
    'admin.switches.title' => 'Feature switches',
    'admin.switches.explain' => 'A switched-off feature disappears completely: its pages and its API answer "not found".',
    'switch.public_registration.label' => 'Public registration',
    'switch.public_registration.explain' => 'Visitors can create their own accounts. Off: only admins (and teachers) create accounts.',
    'switch.email_requirement.label' => 'Email requirement',
    'switch.email_requirement.explain' => 'New accounts must confirm their email address, and phpChess sends email notices. Off: no email is needed (for schools).',
    'switch.player_list_visibility.label' => 'Player list visibility',
    'switch.player_list_visibility.explain' => 'Members can see the list of all players and the ratings.',
    'switch.school_mode.label' => 'School mode',
    'switch.school_mode.explain' => 'Classes, teacher accounts and class limits.',
    'switch.game_chat.label' => 'Game chat',
    'switch.game_chat.explain' => 'Players can chat inside a game.',
    'switch.direct_messages.label' => 'Direct messages',
    'switch.direct_messages.explain' => 'Members can send each other private messages.',
    'switch.public_profiles.label' => 'Public profiles',
    'switch.public_profiles.explain' => 'Profiles can be seen by visitors who are not logged in.',
    'switch.file_uploads.label' => 'File uploads',
    'switch.file_uploads.explain' => 'Members can upload pictures (avatars).',
    'switch.push_notifications.label' => 'Push notifications',
    'switch.push_notifications.explain' => 'Notices on the phone through the browser makers\' push services. This sends data to outside servers.',
    'switch.external_content.label' => 'External content',
    'switch.external_content.explain' => 'Lichess content and login. This loads content from lichess.org.',

    // --- Admin: audit log (ADM-03) ---
    'admin.audit.title' => 'Audit log',
    'admin.audit.none' => 'No admin actions yet.',
];
