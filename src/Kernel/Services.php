<?php

declare(strict_types=1);

namespace PhpChess\Kernel;

use Closure;
use PhpChess\Domain\Community\Avatars;
use PhpChess\Domain\Community\GameChat;
use PhpChess\Domain\Community\Messages;
use PhpChess\Domain\Community\WordFilter;
use PhpChess\Domain\Identity\AccountService;
use PhpChess\Domain\Identity\OwnData;
use PhpChess\Domain\Notify\NotificationChoices;
use PhpChess\Domain\School\Classes;
use PhpChess\Domain\System\Archive;
use PhpChess\Domain\Lichess\ContentScanner;
use PhpChess\Domain\Lichess\StudyImport;
use PhpChess\Domain\Puzzles\PuzzleSet;
use PhpChess\Infra\Lichess\LichessClient;
use PhpChess\Infra\Lichess\StudyRepository;
use PhpChess\Infra\Community\BuddyRepository;
use PhpChess\Infra\Community\GameChatRepository;
use PhpChess\Infra\Community\MessageRepository;
use PhpChess\Infra\Community\ProfileRepository;
use PhpChess\Infra\Content\ContentRepository;
use PhpChess\Infra\Content\NewsletterRepository;
use PhpChess\Infra\Identity\ApiTokenRepository;
use PhpChess\Infra\Identity\OwnDataRepository;
use PhpChess\Infra\Notify\PushSubscriptionRepository;
use PhpChess\Infra\School\SchoolRepository;
use PhpChess\Infra\System\ArchiveRepository;
use PhpChess\Domain\Identity\LoginService;
use PhpChess\Domain\Identity\PasswordResetService;
use PhpChess\Domain\Identity\Preferences;
use PhpChess\Domain\Language\Translator;
use PhpChess\Domain\Notify\MoveEmails;
use PhpChess\Domain\Look\Skin;
use PhpChess\Domain\System\FeatureSwitches;
use PhpChess\Domain\System\UnsafeStateCheck;
use PhpChess\Http\PageRenderer;
use PhpChess\Http\Request;
use PhpChess\Http\Template;
use PhpChess\Infra\Database;
use PhpChess\Infra\Identity\LoginAttemptRepository;
use PhpChess\Infra\Identity\PreferenceRepository;
use PhpChess\Infra\Identity\TokenRepository;
use PhpChess\Infra\Identity\UserRepository;
use PhpChess\Infra\Mail\LogFileMailer;
use PhpChess\Infra\Mail\Mailer;
use PhpChess\Infra\Mail\SmtpMailer;
use PhpChess\Infra\Migration\MigrationRunner;
use PhpChess\Infra\System\AuditLogRepository;
use PhpChess\Infra\System\JobRepository;
use PhpChess\Infra\System\RateLimiter;
use PhpChess\Infra\System\SettingsRepository;
use RuntimeException;

/**
 * Builds the services for one request, each once, when first asked for.
 *
 * This is the one place where phpChess's parts are put together, so a reader
 * can see what each part needs. There is no magic: every method creates one
 * object from the others, by hand. Controllers get what they need from here
 * in Routes.php.
 */
final class Services
{
    private ?Database $database = null;
    private ?SettingsRepository $settings = null;
    private ?UserRepository $users = null;
    private ?TokenRepository $tokens = null;
    private ?RateLimiter $rateLimiter = null;
    private ?LoginService $loginService = null;
    private ?Authentication $authentication = null;
    private ?Translator $translator = null;
    private ?Mailer $mailer = null;
    private ?Skin $skin = null;
    private ?PageRenderer $pages = null;
    private ?PlayParts $play = null;

    /**
     * @param Closure(Config): Database $connectDatabase
     */
    public function __construct(
        public readonly string $appRoot,
        public readonly Config $config,
        public readonly Request $request,
        public readonly Session $session,
        public readonly Csrf $csrf,
        public readonly int $now,
        private readonly Closure $connectDatabase,
        private readonly ?Mailer $mailerOverride = null,
        private readonly ?int $fixedNowMs = null,
    ) {
    }

    /**
     * The time in Unix milliseconds, for the chess clock (K12). Tests that
     * fix $now get $now × 1000.
     */
    public function nowMs(): int
    {
        return $this->fixedNowMs ?? $this->now * 1000;
    }

    // --- Database and System ---

    public function database(): Database
    {
        return $this->database ??= ($this->connectDatabase)($this->config);
    }

    public function settings(): SettingsRepository
    {
        return $this->settings ??= new SettingsRepository($this->database());
    }

    public function switches(): FeatureSwitches
    {
        return new FeatureSwitches($this->settings());
    }

    public function rateLimiter(): RateLimiter
    {
        return $this->rateLimiter ??= new RateLimiter($this->database());
    }

    public function auditLog(): AuditLogRepository
    {
        return new AuditLogRepository($this->database());
    }

    public function migrationRunner(): MigrationRunner
    {
        return new MigrationRunner($this->database(), MigrationRunner::loadFolder($this->appRoot . '/migrations'));
    }

    public function unsafeStateCheck(): UnsafeStateCheck
    {
        return new UnsafeStateCheck($this->appRoot, $this->settings(), new JobRepository($this->database()), $this->migrationRunner());
    }

    // --- Identity ---

    public function users(): UserRepository
    {
        return $this->users ??= new UserRepository($this->database());
    }

    public function tokens(): TokenRepository
    {
        return $this->tokens ??= new TokenRepository($this->database());
    }

    public function preferenceRepository(): PreferenceRepository
    {
        return new PreferenceRepository($this->database());
    }

    public function loginService(): LoginService
    {
        return $this->loginService ??= new LoginService(
            $this->users(),
            $this->tokens(),
            new LoginAttemptRepository($this->database()),
            $this->rateLimiter(),
        );
    }

    public function authentication(): Authentication
    {
        return $this->authentication ??= new Authentication(
            $this->session,
            $this->users(),
            $this->loginService(),
            $this->request,
            new ApiTokenRepository($this->database()),
        );
    }

    public function accountService(): AccountService
    {
        return new AccountService(
            $this->users(),
            $this->tokens(),
            $this->settings(),
            $this->switches(),
            $this->rateLimiter(),
            $this->mailer(),
            $this->translator(),
        );
    }

    public function passwordResetService(): PasswordResetService
    {
        return new PasswordResetService(
            $this->users(),
            $this->tokens(),
            $this->loginService(),
            $this->rateLimiter(),
            $this->mailer(),
            $this->translator(),
            $this->settings(),
        );
    }

    /** The logged-in user's preferences, or the defaults for a visitor. */
    public function currentPreferences(): Preferences
    {
        $user = $this->authentication()->currentUser();

        return $user === null ? Preferences::defaults() : $this->preferenceRepository()->findForUser($user->id);
    }

    // --- Play (C2-1) ---

    /** Games, challenges, vacations, ratings and notices, built in PlayParts. */
    public function play(): PlayParts
    {
        return $this->play ??= new PlayParts($this->database(), $this->settings());
    }

    public function moveEmails(): MoveEmails
    {
        return new MoveEmails(
            $this->play()->noticeRepository(),
            $this->users(),
            $this->preferenceRepository(),
            $this->settings(),
            $this->mailer(),
            $this->translator(),
            $this->notificationChoices(),
        );
    }

    /** The badge on the Play link: games waiting for the logged-in user's move (A44); 0 for visitors. */
    public function myMoveCount(): int
    {
        $user = $this->authentication()->currentUser();

        return $user === null ? 0 : $this->play()->gameRepository()->countWaitingFor($user->id);
    }

    /** The badge on the Messages link: unread messages (C-04); 0 for visitors or with messages off. */
    public function unreadMessageCount(): int
    {
        $user = $this->authentication()->currentUser();
        if ($user === null || !$this->switches()->isOn(FeatureSwitches::DIRECT_MESSAGES)) {
            return 0;
        }

        return $this->messageRepository()->countUnread($user->id);
    }

    // --- Notifications (N-03, N-04) ---

    public function pushSubscriptions(): PushSubscriptionRepository
    {
        return new PushSubscriptionRepository($this->database());
    }

    public function notificationChoices(): NotificationChoices
    {
        return new NotificationChoices(
            $this->preferenceRepository(),
            $this->pushSubscriptions(),
            $this->switches()->isOn(FeatureSwitches::PUSH_NOTIFICATIONS),
        );
    }

    // --- Community (C-01 to C-10, G-12) ---

    public function wordFilter(): WordFilter
    {
        return WordFilter::fromSettings($this->settings());
    }

    public function profiles(): ProfileRepository
    {
        return new ProfileRepository($this->database());
    }

    public function avatars(): Avatars
    {
        return new Avatars($this->profiles(), $this->appRoot . '/storage/uploads/avatars');
    }

    public function buddies(): BuddyRepository
    {
        return new BuddyRepository($this->database());
    }

    public function messageRepository(): MessageRepository
    {
        return new MessageRepository($this->database());
    }

    public function messages(): Messages
    {
        return new Messages(
            $this->messageRepository(),
            $this->users(),
            $this->play()->noticeRepository(),
            $this->play()->classRules(),
            $this->wordFilter(),
        );
    }

    public function gameChat(): GameChat
    {
        return new GameChat(new GameChatRepository($this->database()), $this->settings(), $this->wordFilter());
    }

    public function content(): ContentRepository
    {
        return new ContentRepository($this->database());
    }

    public function newsletters(): NewsletterRepository
    {
        return new NewsletterRepository($this->database());
    }

    // --- School mode (SCH-02 to SCH-06) ---

    public function school(): SchoolRepository
    {
        return new SchoolRepository($this->database());
    }

    public function classes(): Classes
    {
        return new Classes($this->school(), $this->users(), $this->tokens(), $this->wordFilter());
    }

    // --- Own data, API tokens and the archive (ID-10, ID-11, A42) ---

    public function apiTokens(): ApiTokenRepository
    {
        return new ApiTokenRepository($this->database());
    }

    public function ownData(): OwnData
    {
        return new OwnData(
            new OwnDataRepository($this->database()),
            $this->users(),
            $this->play()->gameRepository(),
            $this->play()->games(),
            $this->avatars(),
        );
    }

    public function archive(): Archive
    {
        return new Archive(new ArchiveRepository($this->database()), $this->settings(), $this->appRoot . '/storage/archives');
    }

    // --- Lichess, study games and puzzles (C5-1: L-01, L-03) ---

    /** The one door to lichess.org; it refuses while "external content" is off (K9). */
    public function lichess(): LichessClient
    {
        $lockFolder = $this->appRoot . '/storage/logs';
        if (!is_dir($lockFolder)) {
            mkdir($lockFolder, 0775, true);
        }

        return new LichessClient($this->switches(), $this->settings(), $lockFolder . '/lichess.lock', $this->now);
    }

    public function studies(): StudyRepository
    {
        return new StudyRepository($this->database());
    }

    public function studyImport(): StudyImport
    {
        return new StudyImport(
            $this->lichess(),
            new ContentScanner($this->wordFilter()),
            $this->studies(),
            $this->auditLog(),
            $this->request->clientIp,
        );
    }

    public function puzzleSet(): PuzzleSet
    {
        return new PuzzleSet();
    }

    // --- Mail ---

    /** The mailer chosen on the mail settings page: the log file (default) or SMTP (ADM-02). */
    public function mailer(): Mailer
    {
        if ($this->mailerOverride !== null) {
            return $this->mailerOverride;
        }
        if ($this->mailer !== null) {
            return $this->mailer;
        }

        return $this->mailer = self::mailerFromSettings($this->settings(), $this->appRoot, $this->request->host);
    }

    /**
     * The mailer the settings choose, for a request and for the background
     * jobs alike: the log file (default) or SMTP (ADM-02).
     */
    public static function mailerFromSettings(SettingsRepository $settings, string $appRoot, string $host): Mailer
    {
        $fromAddress = $settings->getString('mail.from_address', 'noreply@' . $host);

        if ($settings->getString('mail.transport', 'log') === 'smtp') {
            return new SmtpMailer(
                $settings->getString('mail.smtp_host'),
                $settings->getInt('mail.smtp_port', 587),
                $settings->getString('mail.smtp_username'),
                $settings->getString('mail.smtp_password'),
                $settings->getString('mail.smtp_encryption', 'tls'),
                $fromAddress,
                $settings->getString('site.name', 'phpChess'),
            );
        }

        return new LogFileMailer($appRoot . '/storage/logs/mail.log', $fromAddress);
    }

    // --- Language and look ---

    /** The Translator in the user's language, else the site's, else English (UI-06). */
    public function translator(): Translator
    {
        if ($this->translator !== null) {
            return $this->translator;
        }

        $language = $this->settings()->getString('site.language', Translator::FALLBACK_LANGUAGE);
        $user = $this->authentication()->currentUser();
        if ($user !== null) {
            $language = $this->preferenceRepository()->findForUser($user->id)->language;
        }

        return $this->translator = new Translator($this->appRoot . '/lang', $language);
    }

    /** The site's skin (setting look.skin); the default skin when that one is broken. */
    public function skin(): Skin
    {
        if ($this->skin !== null) {
            return $this->skin;
        }

        $skinsFolder = $this->appRoot . '/skins';
        try {
            return $this->skin = Skin::load($skinsFolder, $this->settings()->getString('look.skin', 'default'));
        } catch (RuntimeException) {
            return $this->skin = Skin::load($skinsFolder, 'default');
        }
    }

    public function template(): Template
    {
        return new Template($this->appRoot . '/templates');
    }

    public function pages(): PageRenderer
    {
        return $this->pages ??= new PageRenderer(
            $this->template(),
            $this->translator(),
            $this->session,
            $this->csrf,
            $this->skin(),
            $this->settings()->getString('site.name', 'phpChess'),
            $this->authentication()->currentUser(),
            $this->currentPreferences(),
            $this->myMoveCount(),
            $this->unreadMessageCount(),
            $this->serverMessage(),
            [
                'messages' => $this->switches()->isOn(FeatureSwitches::DIRECT_MESSAGES),
                'school' => $this->switches()->isOn(FeatureSwitches::SCHOOL_MODE),
                'players' => $this->switches()->isOn(FeatureSwitches::PLAYER_LIST_VISIBILITY),
                'push' => $this->switches()->isOn(FeatureSwitches::PUSH_NOTIFICATIONS),
            ],
        );
    }

    /**
     * The newest published server message (C-07), shown on every page; null when there is none.
     *
     * @return array{id: int, title: string, body: string}|null
     */
    public function serverMessage(): ?array
    {
        $newest = $this->content()->listKind(ContentRepository::SERVER_MESSAGE, true, 1)[0] ?? null;

        return $newest === null ? null : ['id' => $newest['id'], 'title' => $newest['title'], 'body' => $newest['body']];
    }
}
