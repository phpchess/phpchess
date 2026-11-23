<?php

declare(strict_types=1);

namespace PhpChess\Kernel;

/**
 * Who may open a route (contract K14, ID-08).
 *
 * Every route in Routes.php names one of these. The Kernel checks it before
 * the controller runs, so a controller never starts work for someone who may
 * not see the page:
 *
 * - EVERYONE: visitors and members alike (start page, login, registration);
 * - MEMBERS:  logged-in accounts only; visitors are sent to the login page;
 * - ADMINS:   accounts with the admin role, who also confirmed their password
 *             in the last 30 minutes (re-authentication, ID-08);
 * - ADMINS_BEFORE_CONFIRMATION: the admin role is enough; only for the page
 *             where the password is confirmed.
 * - TEACHERS: accounts with the teacher or admin role (school pages, SCH-02);
 *             no password confirmation, a teacher works there all lesson.
 *
 * Services still check whatever protects data ("is this your game"), K14.
 */
final class Access
{
    public const string EVERYONE = 'everyone';
    public const string MEMBERS = 'members';
    public const string ADMINS = 'admins';
    public const string ADMINS_BEFORE_CONFIRMATION = 'admins_before_confirmation';
    public const string TEACHERS = 'teachers';
}
