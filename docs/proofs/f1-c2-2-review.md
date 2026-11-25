# F1: review of C2-2 (B17, 2026-11-25)

Reviewed: game chat, messages (web and API), profiles, buddies, avatars,
school classes and login cards, API v1 members, own-data export and deletion.

## Fixed in F1

| # | Finding | Fix | Test |
|---|---------|-----|------|
| 1 | Chat lines could be posted on finished games; the post reloaded the page in play mode (active board colours). | `GameChat::say()` refuses when the game is not running; the form shows only while it runs (A61). | `CommunityTest::testAFinishedGamesChatCanBeReadButNotAddedTo` |
| 2 | Games against the computer had a chat. | No chat block and 404 on a post (A61). | `CommunityTest::testAGameAgainstTheComputerHasNoChat` |
| 3 | **A teacher could add another teacher's student to their own class by name and then set that student's password** (account takeover). | `Classes::addExistingMember()`: a teacher adds only a student in no class or in one of their own classes. | `SchoolTest::testATeacherCannotPullAnotherTeachersStudentIntoTheirClass` |
| 4 | A buddy could be added for a disabled or computer account. | `addBuddy` checks the account is active. | (small, no own test) |

## To the backlog

- B34: uploaded avatars (`/avatars/{id}`) are served to any logged-in member by id, also for students in a limited class. Check the class rules there too.
- B35: a deleted teacher's classes stay with no teacher; only the admin can manage them. Offer the admin to hand them to another teacher.

Found fine: messages respect the class limit and the switch (web and API), the API routes are switch-gated, export and deletion cover every personal table, login cards are one-time 64-hex tokens.
