<?php

declare(strict_types=1);

namespace PhpChess\Kernel;

use Closure;
use PhpChess\Domain\Identity\AccountService;
use PhpChess\Domain\Identity\LoginService;
use PhpChess\Domain\Identity\PasswordResetService;
use PhpChess\Domain\Identity\Preferences;
use PhpChess\Domain\Language\Translator;
use PhpChess\Domain\Look\Skin;
use PhpChess\Domain\System\FeatureSwitches;
use PhpChess\Domain\System\SetupToken;
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
    ) {
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

    public function setupToken(): SetupToken
    {
        return new SetupToken($this->appRoot);
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
        return $this->authentication ??= new Authentication($this->session, $this->users(), $this->loginService(), $this->request);
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

        $settings = $this->settings();
        $fromAddress = $settings->getString('mail.from_address', 'noreply@' . $this->request->host);

        if ($settings->getString('mail.transport', 'log') === 'smtp') {
            return $this->mailer = new SmtpMailer(
                $settings->getString('mail.smtp_host'),
                $settings->getInt('mail.smtp_port', 587),
                $settings->getString('mail.smtp_username'),
                $settings->getString('mail.smtp_password'),
                $settings->getString('mail.smtp_encryption', 'tls'),
                $fromAddress,
                $settings->getString('site.name', 'phpChess'),
            );
        }

        return $this->mailer = new LogFileMailer($this->appRoot . '/storage/logs/mail.log', $fromAddress);
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
        );
    }
}
