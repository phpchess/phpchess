<?php

declare(strict_types=1);

namespace PhpChess\Http\Web\School;

use InvalidArgumentException;
use PhpChess\Domain\ActionRefused;
use PhpChess\Domain\Play\Game;
use PhpChess\Http\QrCode;
use PhpChess\Http\Request;
use PhpChess\Http\Response;
use PhpChess\Http\Web\MemberPages;
use PhpChess\Kernel\Services;

/**
 * School mode for teachers (SCH-02 to SCH-06), behind the switch "school mode" (K8).
 *
 * Addresses (Routes.php), teachers and admins:
 *   GET  /school                                my classes (admins: all), and a form for a new one
 *   POST /school/classes                        create a class
 *   GET  /school/classes/{id}                   members, their games, and the class settings
 *   POST /school/classes/{id}/students          create student accounts from a list of names
 *   POST /school/classes/{id}/members           add an existing account
 *   POST /school/classes/{id}/members/{user}/remove
 *   POST /school/classes/{id}/limit             "limit to the class" on or off (SCH-05)
 *   POST /school/classes/{id}/cards             print login cards (all students, or one)
 *   POST /school/classes/{id}/members/{user}/revoke-card
 *   POST /school/classes/{id}/members/{user}/password
 *   GET  /school/students/{user}/games          a student's games (SCH-06)
 * And for everyone, so a student can scan a card (a short address, to fit a QR code):
 *   GET  /c/{token}, POST /c/{token}
 */
final class SchoolController
{
    public function __construct(
        private readonly Services $services,
        private readonly MemberPages $pages,
    ) {
    }

    public function classes(Request $request): Response
    {
        $teacher = $this->pages->user();

        return $this->pages->render($request, 'school/classes', 'school.title', [
            'classes' => $this->services->school()->listClasses($teacher->isAdmin() ? null : $teacher->id),
        ]);
    }

    public function createClass(Request $request): Response
    {
        try {
            $classId = $this->services->classes()->createClass($this->pages->user(), $request->formValue('name'), $this->services->now);
        } catch (ActionRefused $refusal) {
            return $this->pages->refused($request, '/school', $refusal);
        }

        return $this->pages->done($request, '/school/classes/' . $classId, 'school.class_created');
    }

    /**
     * @param array<string, string> $parts
     */
    public function showClass(Request $request, array $parts): Response
    {
        try {
            $class = $this->services->classes()->classFor($this->pages->user(), MemberPages::idFrom($parts));
        } catch (ActionRefused) {
            return $this->pages->notFound($request);
        }
        $members = $this->services->school()->members($class['id']);
        $games = $this->services->play()->gameRepository();
        $memberRows = [];
        foreach ($members as $member) {
            $memberRows[] = $member + [
                'running' => count($games->listForPlayer($member['id'], Game::STATE_RUNNING)),
                'finished' => count($games->listForPlayer($member['id'], Game::STATE_FINISHED)),
            ];
        }

        return $this->services->pages()->render($request, 'school/class', $class['name'], [
            'class' => $class,
            'members' => $memberRows,
        ]);
    }

    /**
     * @param array<string, string> $parts
     */
    public function addStudents(Request $request, array $parts): Response
    {
        $classId = MemberPages::idFrom($parts);
        try {
            $created = $this->services->classes()->addStudents($this->pages->user(), $classId, $request->formValue('names'));
        } catch (ActionRefused $refusal) {
            return $this->pages->refused($request, '/school/classes/' . $classId, $refusal);
        }

        return $this->pages->done($request, '/school/classes/' . $classId, 'school.students_created', ['count' => count($created)]);
    }

    /**
     * @param array<string, string> $parts
     */
    public function addMember(Request $request, array $parts): Response
    {
        $classId = MemberPages::idFrom($parts);
        try {
            $this->services->classes()->addExistingMember($this->pages->user(), $classId, $request->formValue('username'));
        } catch (ActionRefused $refusal) {
            return $this->pages->refused($request, '/school/classes/' . $classId, $refusal);
        }

        return $this->pages->done($request, '/school/classes/' . $classId, 'school.member_added');
    }

    /**
     * @param array<string, string> $parts
     */
    public function removeMember(Request $request, array $parts): Response
    {
        $classId = MemberPages::idFrom($parts);
        try {
            $this->services->classes()->removeMember($this->pages->user(), $classId, MemberPages::idFrom($parts, 'user'));
        } catch (ActionRefused $refusal) {
            return $this->pages->refused($request, '/school', $refusal);
        }

        return $this->pages->done($request, '/school/classes/' . $classId, 'school.member_removed');
    }

    /**
     * @param array<string, string> $parts
     */
    public function setLimit(Request $request, array $parts): Response
    {
        $classId = MemberPages::idFrom($parts);
        try {
            $this->services->classes()->setLimitToClass($this->pages->user(), $classId, $request->formValue('limit') === 'yes');
        } catch (ActionRefused $refusal) {
            return $this->pages->refused($request, '/school', $refusal);
        }

        return $this->pages->done($request, '/school/classes/' . $classId, 'school.saved');
    }

    /**
     * Makes new login cards and shows them ready to print. The cards exist
     * only on this page (only their hashes are stored), like any one-time link.
     *
     * @param array<string, string> $parts
     */
    public function printCards(Request $request, array $parts): Response
    {
        $teacher = $this->pages->user();
        try {
            $class = $this->services->classes()->classFor($teacher, MemberPages::idFrom($parts));
        } catch (ActionRefused) {
            return $this->pages->notFound($request);
        }
        $members = $this->services->school()->members($class['id']);
        $onlyUserId = (int) $request->formValue('user', '0');
        $userIds = [];
        foreach ($members as $member) {
            if ($member['role'] === 'student' && ($onlyUserId === 0 || $member['id'] === $onlyUserId)) {
                $userIds[] = $member['id'];
            }
        }
        $tokens = $this->services->classes()->newLoginCards($teacher, $class['id'], $userIds);

        $cards = [];
        foreach ($members as $member) {
            if (isset($tokens[$member['id']])) {
                // The short address /c/... keeps the link small enough for a QR code (106 characters at most).
                $link = $request->baseUrl() . '/c/' . $tokens[$member['id']];
                try {
                    $qrSvgHtml = QrCode::svg($link, 4);
                } catch (InvalidArgumentException) {
                    $qrSvgHtml = '';
                }
                $cards[] = ['username' => $member['username'], 'link' => $link, 'qrSvgHtml' => $qrSvgHtml];
            }
        }

        return $this->services->pages()->render($request, 'school/cards', $class['name'], [
            'class' => $class,
            'cards' => $cards,
        ]);
    }

    /**
     * @param array<string, string> $parts
     */
    public function revokeCard(Request $request, array $parts): Response
    {
        $classId = MemberPages::idFrom($parts);
        try {
            $this->services->classes()->revokeLoginCard($this->pages->user(), $classId, MemberPages::idFrom($parts, 'user'));
        } catch (ActionRefused $refusal) {
            return $this->pages->refused($request, '/school', $refusal);
        }

        return $this->pages->done($request, '/school/classes/' . $classId, 'school.card_revoked');
    }

    /**
     * @param array<string, string> $parts
     */
    public function setPassword(Request $request, array $parts): Response
    {
        $classId = MemberPages::idFrom($parts);
        try {
            $this->services->classes()->setPassword($this->pages->user(), $classId, MemberPages::idFrom($parts, 'user'), $request->formValue('password'));
        } catch (ActionRefused $refusal) {
            return $this->pages->refused($request, '/school/classes/' . $classId, $refusal);
        }

        return $this->pages->done($request, '/school/classes/' . $classId, 'school.password_set');
    }

    /**
     * A student's games, for their teacher (SCH-06).
     *
     * @param array<string, string> $parts
     */
    public function studentGames(Request $request, array $parts): Response
    {
        $teacher = $this->pages->user();
        $student = $this->services->users()->findById(MemberPages::idFrom($parts, 'user'));
        if ($student === null || !$this->services->play()->classRules()->mayLookAfter($teacher, $student->id)) {
            return $this->pages->notFound($request);
        }
        $repository = $this->services->play()->gameRepository();
        $running = $repository->listForPlayer($student->id, Game::STATE_RUNNING);
        $finished = $repository->listForPlayer($student->id, Game::STATE_FINISHED, 50);
        $ids = [];
        foreach ([...$running, ...$finished] as $game) {
            $ids[] = $game->whiteId;
            $ids[] = $game->blackId;
        }

        return $this->services->pages()->render($request, 'school/student_games', $student->username, [
            'student' => $student,
            'running' => $running,
            'finished' => $finished,
            'usernames' => $this->services->users()->usernamesOf($ids),
        ]);
    }

    // --- Logging in with a card (SCH-04) ---

    /**
     * @param array<string, string> $parts
     */
    public function showCardLogin(Request $request, array $parts): Response
    {
        $token = $parts['token'] ?? '';

        return $this->pages->render($request, 'school/card_login', 'school.card_login_title', [
            'token' => preg_match('/^[0-9a-f]{64}$/', $token) === 1 ? $token : '',
        ]);
    }

    /**
     * @param array<string, string> $parts
     */
    public function cardLogin(Request $request, array $parts): Response
    {
        $userId = $this->services->classes()->useLoginCard($parts['token'] ?? '');
        $user = $userId === null ? null : $this->services->users()->findById($userId);
        if ($user === null || !$user->isActive()) {
            return $this->services->pages()->redirectWithMessage($request, '/login', 'error', $this->pages->text('school.card_not_valid'));
        }
        $this->services->authentication()->logIn($user, true);
        $this->services->users()->markLoggedIn($user->id);

        return Response::redirect($request->basePath . '/');
    }
}
