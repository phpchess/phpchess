<?php

declare(strict_types=1);

namespace PhpChess\Tests\School;

use PhpChess\Domain\Identity\User;
use PhpChess\Domain\Play\Game;
use PhpChess\Domain\System\FeatureSwitches;
use PhpChess\Infra\School\SchoolRepository;
use PhpChess\Kernel\PlayParts;
use PhpChess\Tests\Support\InstalledSite;
use PhpChess\Tests\Support\Values;
use PhpChess\Tests\Support\TestBrowser;
use PHPUnit\Framework\TestCase;

/**
 * School mode (SCH-02 to SCH-06) through the Kernel, as a teacher and
 * students use it: the switch, classes, students in bulk, login cards,
 * passwords, the class limit (a limited student can't reach anyone
 * outside the class) and the teacher's view of games. The last test is
 * the chunk's scenario: a teacher, a class of 25, messaging off.
 */
final class SchoolTest extends TestCase
{
    private InstalledSite $site;
    private PlayParts $play;
    private FeatureSwitches $switches;

    protected function setUp(): void
    {
        $this->site = new InstalledSite($this);
        $this->play = new PlayParts($this->site->database, $this->site->settings());
        $this->switches = new FeatureSwitches($this->site->settings());
        $this->switches->set(FeatureSwitches::SCHOOL_MODE, true);
        $this->site->createUser('teacher1', User::ROLE_TEACHER);
        $this->site->createUser('outsider');
    }

    protected function tearDown(): void
    {
        if (isset($this->site)) {
            $this->site->remove();
        }
    }

    // --- The switch and who may enter (K8, K14) ---

    public function testSchoolPagesExistOnlyWithSchoolModeOnAndOnlyForTeachers(): void
    {
        $teacher = $this->site->loggedInBrowser('teacher1');
        self::assertSame(200, $teacher->get('/school')->status);
        self::assertSame(403, $this->site->loggedInBrowser('outsider')->get('/school')->status);

        $this->switches->set(FeatureSwitches::SCHOOL_MODE, false);
        self::assertSame(404, $teacher->get('/school')->status);
        self::assertSame(404, $teacher->get('/c/' . str_repeat('a', 64))->status);
        self::assertStringNotContainsString('/school"', $teacher->get('/')->body);
    }

    // --- Classes, students and login cards (SCH-02 to SCH-04) ---

    public function testATeacherCreatesStudentsWithoutEmailAndPrintsLoginCards(): void
    {
        $teacher = $this->site->loggedInBrowser('teacher1');
        $classId = $this->createClass($teacher, '5b');
        $teacher->post("/school/classes/{$classId}/students", ['names' => "Mia Müller\nLeo\nLeo"]);

        $mia = $this->site->users()->findByUsernameOrEmail('Mia.Mueller');
        self::assertNotNull($mia);
        self::assertSame(User::ROLE_STUDENT, $mia->role);
        self::assertSame('', $mia->email);
        self::assertNotNull($this->site->users()->findByUsernameOrEmail('Leo2'));

        $teacher->get("/school/classes/{$classId}");
        $cards = $teacher->post("/school/classes/{$classId}/cards")->body;
        self::assertSame(3, substr_count($cards, 'class="login-card"'));
        self::assertSame(3, substr_count($cards, 'class="qr-code"'));
        $card = Values::firstMatch('#/chess/c/([0-9a-f]{64})#', $cards);

        // The card logs in once; the second time it no longer works.
        $student = $this->site->browser();
        $student->get('/c/' . $card);
        $student->post('/c/' . $card);
        self::assertStringContainsString('Log out', $student->get('/')->body);
        $other = $this->site->browser();
        $other->get('/c/' . $card);
        self::assertSame('/chess/login', $other->post('/c/' . $card)->header('Location'));
    }

    public function testATeacherSetsAStudentsPasswordButNobodyElses(): void
    {
        $this->site->createUser('teacher2', User::ROLE_TEACHER);
        $teacher = $this->site->loggedInBrowser('teacher1');
        $classId = $this->createClass($teacher, '6a');
        $teacher->post("/school/classes/{$classId}/students", ['names' => 'Ida']);
        $idaId = $this->userId('Ida');

        $teacher->get("/school/classes/{$classId}");
        $teacher->post("/school/classes/{$classId}/members/{$idaId}/password", ['password' => 'a new long secret 7']);
        $ida = $this->site->browser();
        $ida->submit('/login', '/login', ['name_or_email' => 'Ida', 'password' => 'a new long secret 7']);
        self::assertStringContainsString('Log out', $ida->get('/')->body);

        // Another teacher, or an ordinary player, can't be pulled into the class and taken over.
        $school = new SchoolRepository($this->site->database);
        foreach (['teacher2', 'outsider'] as $name) {
            $teacher->get("/school/classes/{$classId}");
            $teacher->post("/school/classes/{$classId}/members", ['username' => $name]);
            self::assertFalse($school->isMember($classId, $this->userId($name)), $name);
            $school->addMember($classId, $this->userId($name));
            $teacher->get("/school/classes/{$classId}");
            $teacher->post("/school/classes/{$classId}/members/{$this->userId($name)}/password", ['password' => 'a new long secret 7']);
            $attempt = $this->site->browser();
            $attempt->submit('/login', '/login', ['name_or_email' => $name, 'password' => 'a new long secret 7']);
            self::assertStringNotContainsString('Log out', $attempt->get('/')->body, $name);
        }
    }

    public function testATeacherCannotPullAnotherTeachersStudentIntoTheirClass(): void
    {
        $this->site->createUser('teacher2', User::ROLE_TEACHER);
        $first = $this->site->loggedInBrowser('teacher1');
        $firstClass = $this->createClass($first, '6a');
        $first->post("/school/classes/{$firstClass}/students", ['names' => 'Ida']);

        $second = $this->site->loggedInBrowser('teacher2');
        $secondClass = $this->createClass($second, '6b');
        $second->get("/school/classes/{$secondClass}");
        $second->post("/school/classes/{$secondClass}/members", ['username' => 'Ida']);

        self::assertFalse((new SchoolRepository($this->site->database))->isMember($secondClass, $this->userId('Ida')));
    }

    public function testATeacherCannotOpenAnotherTeachersClass(): void
    {
        $this->site->createUser('teacher2', User::ROLE_TEACHER);
        $classId = $this->createClass($this->site->loggedInBrowser('teacher1'), '7c');

        $other = $this->site->loggedInBrowser('teacher2');
        self::assertSame(404, $other->get("/school/classes/{$classId}")->status);
        $other->get('/school');
        $other->post("/school/classes/{$classId}/students", ['names' => 'Intruder']);
        self::assertFalse($this->site->users()->usernameExists('Intruder'));
    }

    // --- The class limit (SCH-05) ---

    public function testALimitedStudentCannotReachAnyoneOutsideTheClass(): void
    {
        [$classId, $annaId, $benId] = $this->classWithTwoStudents();
        $outsiderId = $this->userId('outsider');
        $anna = $this->site->loggedInBrowser('anna');

        // A direct challenge, a message, a buddy, a profile: all refused outside the class.
        $anna->submit('/play', '/challenges', ['kind' => 'direct', 'opponent' => 'outsider', 'colour' => 'random', 'days_per_move' => '3']);
        $anna->submit('/messages/outsider', '/messages/outsider', ['body' => 'hi']);
        $anna->get('/players');
        $anna->post('/players/outsider/buddy');
        self::assertSame(404, $anna->get('/players/outsider')->status);
        self::assertSame([], $this->play->challengeRepository()->waitingFor($outsiderId, time()));
        self::assertSame(0, $this->countRows('messages'));
        self::assertSame(0, $this->countRows('buddies'));

        // The outsider can't reach in either: accepting the student's open challenge fails.
        $anna->submit('/play', '/challenges', ['kind' => 'open', 'colour' => 'white', 'days_per_move' => '3']);
        $challengeId = $this->play->challengeRepository()->waitingOpen(time())[0]->id;
        $outsider = $this->site->loggedInBrowser('outsider');
        $outsider->get('/play');
        $outsider->post("/challenges/{$challengeId}/accept");
        $outsider->get('/');
        $outsider->post('/messages/anna', ['body' => 'hello']);
        self::assertSame([], $this->play->gameRepository()->listAll(Game::STATE_RUNNING));
        self::assertSame(0, $this->countRows('messages'));

        // Inside the class everything works.
        $ben = $this->site->loggedInBrowser('ben');
        $ben->get('/play');
        $ben->post("/challenges/{$challengeId}/accept");
        $ben->submit('/messages/anna', '/messages/anna', ['body' => 'good luck']);
        self::assertCount(1, $this->play->gameRepository()->listForPlayer($annaId, Game::STATE_RUNNING));
        self::assertSame(1, $this->countRows('messages'));

        // With the limit off, the outsider is fine again.
        (new SchoolRepository($this->site->database))->setLimitToClass($classId, false);
        $this->play->games()->start($outsiderId, $benId, 3, true, '', time());
        self::assertCount(1, $this->play->gameRepository()->listForPlayer($outsiderId, Game::STATE_RUNNING));
    }

    public function testWithSchoolModeOffTheLimitDoesNothing(): void
    {
        $this->classWithTwoStudents();
        $this->switches->set(FeatureSwitches::SCHOOL_MODE, false);

        $this->play->games()->start($this->userId('anna'), $this->userId('outsider'), 3, true, '', time());
        self::assertCount(1, $this->play->gameRepository()->listAll(Game::STATE_RUNNING));
    }

    // --- Teacher views (SCH-06) ---

    public function testATeacherSeesTheirStudentsGamesEvenWithThePlayerListOff(): void
    {
        [, $annaId, $benId] = $this->classWithTwoStudents();
        $gameId = $this->play->games()->start($annaId, $benId, 3, true, '', time());
        $this->switches->set(FeatureSwitches::PLAYER_LIST_VISIBILITY, false);

        $teacher = $this->site->loggedInBrowser('teacher1');
        self::assertSame(200, $teacher->get("/games/{$gameId}")->status);
        self::assertStringContainsString("/games/{$gameId}", $teacher->get("/school/students/{$annaId}/games")->body);
        self::assertSame(404, $this->site->loggedInBrowser('outsider')->get("/games/{$gameId}")->status);

        $this->site->createUser('teacher2', User::ROLE_TEACHER);
        self::assertSame(404, $this->site->loggedInBrowser('teacher2')->get("/school/students/{$annaId}/games")->status);
    }

    // --- The chunk's scenario ---

    public function testASchoolScenarioWithAClassOf25AndMessagingOff(): void
    {
        $this->switches->set(FeatureSwitches::DIRECT_MESSAGES, false);
        $teacher = $this->site->loggedInBrowser('teacher1');
        $classId = $this->createClass($teacher, 'Chess club');
        $names = [];
        for ($number = 1; $number <= 25; $number++) {
            $names[] = 'Pupil' . $number;
        }
        $teacher->post("/school/classes/{$classId}/students", ['names' => implode("\n", $names)]);
        self::assertCount(25, (new SchoolRepository($this->site->database))->members($classId));

        $teacher->get("/school/classes/{$classId}");
        preg_match_all('#/chess/c/([0-9a-f]{64})#', $teacher->post("/school/classes/{$classId}/cards")->body, $found);
        self::assertCount(25, array_unique($found[1]));

        // Two pupils log in with their cards and play a game against each other.
        $first = $this->logInWithCard($found[1][0]);
        $second = $this->logInWithCard($found[1][1]);
        $first->submit('/play', '/play/quick', ['days_per_move' => '3', 'rated' => 'yes']);
        $second->submit('/play', '/play/quick', ['days_per_move' => '3', 'rated' => 'yes']);
        $games = $this->play->gameRepository()->listAll(Game::STATE_RUNNING);
        self::assertCount(1, $games);

        // Messaging is off: no page, no menu link, no API.
        self::assertSame(404, $first->get('/messages')->status);
        self::assertStringNotContainsString('/messages"', $first->get('/')->body);

        // The outsider cannot join the class's games, the teacher sees them.
        $outsider = $this->site->loggedInBrowser('outsider');
        $outsider->submit('/play', '/play/quick', ['days_per_move' => '3', 'rated' => 'yes']);
        self::assertCount(1, $this->play->gameRepository()->listAll(Game::STATE_RUNNING));
        self::assertSame(200, $teacher->get('/games/' . $games[0]->id)->status);
    }

    // --- Helpers ---

    private function createClass(TestBrowser $teacher, string $name): int
    {
        $response = $teacher->submit('/school', '/school/classes', ['name' => $name]);
        $classId = (int) Values::firstMatch('#/school/classes/(\d+)#', $response->header('Location'));
        $teacher->get('/school/classes/' . $classId);

        return $classId;
    }

    /**
     * A limited class with the students "anna" and "ben" (password PASSWORD).
     *
     * @return array{0: int, 1: int, 2: int} class id, anna's id, ben's id
     */
    private function classWithTwoStudents(): array
    {
        $annaId = $this->site->createUser('anna', User::ROLE_STUDENT);
        $benId = $this->site->createUser('ben', User::ROLE_STUDENT);
        $school = new SchoolRepository($this->site->database);
        $classId = $school->createClass('4a', $this->userId('teacher1'), true, time());
        $school->addMember($classId, $annaId);
        $school->addMember($classId, $benId);

        return [$classId, $annaId, $benId];
    }

    private function logInWithCard(string $token): TestBrowser
    {
        $browser = $this->site->browser();
        $browser->get('/c/' . $token);
        $browser->post('/c/' . $token);

        return $browser;
    }

    private function userId(string $username): int
    {
        return (int) $this->site->users()->findByUsernameOrEmail($username)?->id;
    }

    private function countRows(string $table): int
    {
        return Values::int($this->site->database->fetchValue('SELECT COUNT(*) FROM ' . $this->site->database->table($table)));
    }
}
