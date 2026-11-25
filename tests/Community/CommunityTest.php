<?php

declare(strict_types=1);

namespace PhpChess\Tests\Community;

use PhpChess\Domain\Community\WordFilter;
use PhpChess\Domain\System\FeatureSwitches;
use PhpChess\Http\UploadedFile;
use PhpChess\Infra\Content\ContentRepository;
use PhpChess\Kernel\Kernel;
use PhpChess\Kernel\PlayParts;
use PhpChess\Tests\Support\InstalledSite;
use PhpChess\Tests\Support\Values;
use PHPUnit\Framework\TestCase;

/**
 * The community through the Kernel, as a browser uses it (C2-2):
 * profiles, avatars and upload validation (C-01, C-02), buddies (C-03),
 * messages and the word filter (C-04, C-05), game chat (G-12), content
 * (C-06 to C-09, ADM-05), who's online (C-10), the newsletter (ADM-07)
 * and each community switch (K8).
 */
final class CommunityTest extends TestCase
{
    private InstalledSite $site;
    private PlayParts $play;

    /** @var list<string> files made by a test, removed afterwards */
    private array $temporaryFiles = [];

    protected function setUp(): void
    {
        $this->site = new InstalledSite($this);
        $this->play = new PlayParts($this->site->database, $this->site->settings());
        $this->site->createUser('anna');
        $this->site->createUser('ben');
    }

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        if (isset($this->site)) {
            $this->site->remove();
        }
    }

    // --- Profiles and buddies (C-01, C-03) ---

    public function testAProfileShowsTheTextAndAddsABuddy(): void
    {
        $anna = $this->site->loggedInBrowser('anna');
        $anna->submit('/account/profile', '/account/profile', ['about' => 'I like the Sicilian.']);

        $ben = $this->site->loggedInBrowser('ben');
        $profile = $ben->get('/players/anna');
        self::assertSame(200, $profile->status);
        self::assertStringContainsString('I like the Sicilian.', $profile->body);

        $ben->post('/players/anna/buddy');
        self::assertStringContainsString('/players/anna', $ben->get('/players')->body);
        $ben->get('/players');
        $ben->post('/players/anna/unbuddy');
        self::assertStringContainsString('No buddies yet', $ben->get('/players')->body);
    }

    public function testVisitorsSeeProfilesOnlyWithPublicProfilesOn(): void
    {
        $visitor = $this->site->browser();
        self::assertSame(303, $visitor->get('/players/anna')->status);

        (new FeatureSwitches($this->site->settings()))->set(FeatureSwitches::PUBLIC_PROFILES, true);
        self::assertSame(200, $visitor->get('/players/anna')->status);
    }

    public function testWhosOnlineFollowsThePlayerListSwitch(): void
    {
        $this->site->loggedInBrowser('anna')->get('/');
        $ben = $this->site->loggedInBrowser('ben');
        self::assertStringContainsString('>anna</a>', $ben->get('/players')->body);

        (new FeatureSwitches($this->site->settings()))->set(FeatureSwitches::PLAYER_LIST_VISIBILITY, false);
        self::assertStringNotContainsString('Online now', $ben->get('/players')->body);
    }

    // --- Avatars and upload validation (C-02) ---

    public function testABundledAvatarCanBeChosen(): void
    {
        $anna = $this->site->loggedInBrowser('anna');
        $anna->submit('/account/profile', '/account/avatar', ['avatar' => 'knight']);
        self::assertStringContainsString('/assets/avatars/knight.svg', $anna->get('/players/anna')->body);

        $anna->submit('/account/profile', '/account/avatar', ['avatar' => '../../config/config']);
        self::assertStringContainsString('/assets/avatars/knight.svg', $anna->get('/players/anna')->body);
    }

    public function testAPictureIsReEncodedToASmallPng(): void
    {
        $this->requireGd();
        $anna = $this->site->loggedInBrowser('anna');
        $anna->get('/account/profile');
        $anna->postWithFiles('/account/avatar/upload', [], ['picture' => $this->pictureFile(800, 600, 'jpeg')]);

        $userId = $this->userId('anna');
        $stored = $this->site->folder->path . '/storage/uploads/avatars/' . $userId . '.png';
        self::assertFileExists($stored);
        $size = getimagesize($stored);
        self::assertNotFalse($size);
        self::assertSame([256, 256, IMAGETYPE_PNG], [$size[0], $size[1], $size[2]]);

        $image = $anna->get('/avatars/' . $userId);
        self::assertSame(200, $image->status);
        self::assertSame('image/png', $image->header('Content-Type'));
    }

    public function testUploadsThatAreNotPicturesAreRefused(): void
    {
        $this->requireGd();
        $anna = $this->site->loggedInBrowser('anna');
        $userId = $this->userId('anna');
        $stored = $this->site->folder->path . '/storage/uploads/avatars/' . $userId . '.png';

        // A script pretending to be a picture.
        $anna->get('/account/profile');
        $anna->postWithFiles('/account/avatar/upload', [], ['picture' => $this->fileWith('<?php echo "hello"; ?>')]);
        self::assertStringContainsString('not a JPEG, PNG, GIF or WebP picture', $anna->get('/account/profile')->body);

        // Too many bytes.
        $anna->postWithFiles('/account/avatar/upload', [], ['picture' => $this->fileWith(str_repeat('x', 2_000_001))]);
        self::assertStringContainsString('too big', $anna->get('/account/profile')->body);

        // Too many pixels (a tiny file claiming a huge picture).
        $anna->postWithFiles('/account/avatar/upload', [], ['picture' => $this->pictureFile(6000, 2, 'png')]);
        self::assertStringContainsString('at most 5000 pixels', $anna->get('/account/profile')->body);

        // An upload that never arrived.
        $anna->postWithFiles('/account/avatar/upload', [], ['picture' => new UploadedFile('', 0, UPLOAD_ERR_PARTIAL)]);
        self::assertStringContainsString('did not arrive', $anna->get('/account/profile')->body);

        self::assertFileDoesNotExist($stored);
    }

    public function testScriptHiddenInAPictureIsNotKept(): void
    {
        $this->requireGd();
        // A real GIF with PHP code after it: PHP accepts it as a picture, but only the pixels survive re-encoding.
        $gif = $this->pictureFile(40, 40, 'gif');
        file_put_contents($gif->path, '<?php system("id"); ?>', FILE_APPEND);
        $anna = $this->site->loggedInBrowser('anna');
        $anna->get('/account/profile');
        $anna->postWithFiles('/account/avatar/upload', [], ['picture' => new UploadedFile($gif->path, (int) filesize($gif->path))]);

        $stored = $this->site->folder->path . '/storage/uploads/avatars/' . $this->userId('anna') . '.png';
        self::assertFileExists($stored);
        self::assertStringNotContainsString('<?php', (string) file_get_contents($stored));
    }

    public function testTheUploadSwitchTurnsUploadsOff(): void
    {
        (new FeatureSwitches($this->site->settings()))->set(FeatureSwitches::FILE_UPLOADS, false);
        $anna = $this->site->loggedInBrowser('anna');
        $anna->get('/account/profile');

        self::assertSame(404, $anna->postWithFiles('/account/avatar/upload', [], ['picture' => $this->fileWith('x')])->status);
        self::assertStringNotContainsString('name="picture"', $anna->get('/account/profile')->body);
    }

    // --- Messages and the word filter (C-04, C-05) ---

    public function testAMessageArrivesFilteredAndCountsAsUnread(): void
    {
        $this->site->settings()->set(WordFilter::SETTING, "darn\nbother");
        $anna = $this->site->loggedInBrowser('anna');
        $anna->submit('/messages/ben', '/messages/ben', ['body' => 'Darn, good game!']);

        $ben = $this->site->loggedInBrowser('ben');
        self::assertMatchesRegularExpression('/Messages <span class="badge"[^>]*>1</', $ben->get('/')->body);
        $conversation = $ben->get('/messages/anna')->body;
        self::assertStringContainsString('****, good game!', $conversation);
        self::assertStringNotContainsString('Darn', $conversation);
        self::assertStringNotContainsString('class="badge"', $ben->get('/messages')->body);

        // The page's polling gets only newer messages, as JSON.
        $anna->submit('/messages/ben', '/messages/ben', ['body' => 'Rematch?']);
        $news = Values::rows(Values::jsonObject($ben->get('/messages/anna/new', ['after' => '1'])->body)['messages'] ?? null);
        self::assertSame(['Rematch?'], array_column($news, 'body'));
    }

    public function testMessagesCanBeSwitchedOff(): void
    {
        (new FeatureSwitches($this->site->settings()))->set(FeatureSwitches::DIRECT_MESSAGES, false);
        $anna = $this->site->loggedInBrowser('anna');

        self::assertSame(404, $anna->get('/messages')->status);
        self::assertSame(404, $anna->post('/messages/ben', ['body' => 'hi'])->status);
        self::assertStringNotContainsString('/messages"', $anna->get('/')->body);
    }

    public function testAUserNameWithAFilteredWordIsRefused(): void
    {
        $this->site->settings()->set(WordFilter::SETTING, 'badword');
        $response = $this->site->browser()->submit('/register', '/register', [
            'username' => 'mybadword1',
            'email' => 'x@example.test',
            'password' => 'a good password 9',
            'password_again' => 'a good password 9',
        ]);

        self::assertFalse($this->site->users()->usernameExists('mybadword1'));
        self::assertSame(422, $response->status);
        self::assertStringContainsString('This user name is not allowed here.', $response->body);
    }

    // --- Game chat (G-12) ---

    public function testGameChatOffersPhrasesByDefaultAndFreeTextWhenChosen(): void
    {
        $gameId = $this->play->games()->start($this->userId('anna'), $this->userId('ben'), 3, true, '', time());
        $anna = $this->site->loggedInBrowser('anna');
        $anna->submit("/games/{$gameId}", "/games/{$gameId}/chat", ['text' => 'good_luck']);
        $anna->post("/games/{$gameId}/chat", ['text' => 'free text is not allowed']);
        $page = $anna->get("/games/{$gameId}")->body;
        self::assertStringContainsString('Good luck!', $page);
        self::assertStringNotContainsString('free text is not allowed', $page);

        $this->site->settings()->set('game_chat.mode', 'free');
        $anna->post("/games/{$gameId}/chat", ['text' => 'Nice opening']);
        self::assertStringContainsString('Nice opening', $anna->get("/games/{$gameId}")->body);

        (new FeatureSwitches($this->site->settings()))->set(FeatureSwitches::GAME_CHAT, false);
        self::assertSame(404, $anna->post("/games/{$gameId}/chat", ['text' => 'hello'])->status);
        self::assertStringNotContainsString('Nice opening', $anna->get("/games/{$gameId}")->body);
    }

    public function testAFinishedGamesChatCanBeReadButNotAddedTo(): void
    {
        $gameId = $this->play->games()->start($this->userId('anna'), $this->userId('ben'), 3, true, '', time());
        $anna = $this->site->loggedInBrowser('anna');
        $anna->post("/games/{$gameId}/chat", ['text' => 'good_luck']);
        $this->play->games()->resign($gameId, $this->userId('ben'), time());

        $anna->post("/games/{$gameId}/chat", ['text' => 'good_game']);
        $page = $anna->get("/games/{$gameId}")->body;
        self::assertStringContainsString('Good luck!', $page);
        self::assertStringNotContainsString('Good game!', $page);
        self::assertStringNotContainsString("/games/{$gameId}/chat", $page);
    }

    public function testAGameAgainstTheComputerHasNoChat(): void
    {
        $gameId = $this->play->computerGames()->start($this->userId('anna'), 1, 'w', time());
        $anna = $this->site->loggedInBrowser('anna');

        self::assertSame(404, $anna->post("/games/{$gameId}/chat", ['text' => 'hello'])->status);
        self::assertStringNotContainsString('game-chat-title', $anna->get("/games/{$gameId}")->body);
    }

    // --- Content and the newsletter (C-06 to C-09, ADM-05, ADM-07) ---

    public function testContentAppearsWhereItBelongs(): void
    {
        $content = new ContentRepository($this->site->database);
        $content->create(ContentRepository::NEWS, '', 'Club evening', 'Friday at 7.', true, 0);
        $content->create(ContentRepository::SERVER_MESSAGE, '', 'Maintenance tonight', '', true, 0);
        $content->create(ContentRepository::TIP, '', 'Castle early', 'Your king is safer.', true, 0);
        $content->create(ContentRepository::FAQ, '', 'How do I resign?', 'Tap the flag.', true, 0);
        $content->create(ContentRepository::PAGE, 'rules', 'Club rules', "Be kind.\n\n<script>alert(1)</script>", true, 0);

        $visitor = $this->site->browser();
        $home = $visitor->get('/')->body;
        self::assertStringContainsString('Club evening', $home);
        self::assertStringContainsString('Maintenance tonight', $home);
        self::assertStringContainsString('Castle early', $home);
        self::assertStringContainsString('How do I resign?', $visitor->get('/faq')->body);
        $page = $visitor->get('/pages/rules')->body;
        self::assertStringContainsString('&lt;script&gt;', $page);
        self::assertStringNotContainsString('<script>alert', $page);
        self::assertSame(404, $visitor->get('/pages/nothing')->status);
    }

    public function testTheAdminWritesContentAndTheNewsletterGoesOutAFewAtATime(): void
    {
        $admin = $this->site->loggedInBrowser(InstalledSite::ADMIN_NAME, true);
        $admin->submit('/admin/content', '/admin/content', ['kind' => 'news', 'title' => 'Hello club', 'body' => 'Welcome', 'published' => 'yes']);
        self::assertStringContainsString('Hello club', $this->site->browser()->get('/')->body);

        $this->site->settings()->set('newsletter.per_run', 2);
        $admin->submit('/admin/newsletter', '/admin/newsletter', ['subject' => 'Spring cup', 'body' => 'Sign up now.']);
        $this->runJobs(time());
        $newsletters = array_filter($this->site->mailer->sent, fn ($email): bool => $email->subject === 'Spring cup');
        self::assertCount(2, $newsletters);

        $this->runJobs(time() + 301);
        $newsletters = array_filter($this->site->mailer->sent, fn ($email): bool => $email->subject === 'Spring cup');
        self::assertCount(3, $newsletters);
    }

    // --- Helpers ---

    private function userId(string $username): int
    {
        return (int) $this->site->users()->findByUsernameOrEmail($username)?->id;
    }

    private function runJobs(int $now): void
    {
        $kernel = new Kernel($this->site->folder->path, now: $now, mailer: $this->site->mailer, pushSender: $this->site->pushSender);
        $kernel->jobRunner()->runFromCron($now);
    }

    private function requireGd(): void
    {
        if (!function_exists('imagecreatetruecolor')) {
            self::fail('These tests need PHP\'s GD extension (run with -d extension=gd).');
        }
    }

    /** A real picture file of the given size and type, made by GD. */
    private function pictureFile(int $width, int $height, string $type): UploadedFile
    {
        if ($width < 1 || $height < 1) {
            self::fail('A picture needs at least one pixel.');
        }
        $image = imagecreatetruecolor($width, $height);
        imagefilledrectangle($image, 0, 0, $width - 1, $height - 1, (int) imagecolorallocate($image, 200, 30, 30));
        $path = $this->temporaryPath();
        match ($type) {
            'jpeg' => imagejpeg($image, $path),
            'gif' => imagegif($image, $path),
            default => imagepng($image, $path),
        };

        return new UploadedFile($path, (int) filesize($path));
    }

    private function fileWith(string $contents): UploadedFile
    {
        $path = $this->temporaryPath();
        file_put_contents($path, $contents);

        return new UploadedFile($path, strlen($contents));
    }

    private function temporaryPath(): string
    {
        $path = sys_get_temp_dir() . '/phpchess-upload-' . bin2hex(random_bytes(6));
        $this->temporaryFiles[] = $path;

        return $path;
    }
}
