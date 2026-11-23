<?php

declare(strict_types=1);

namespace PhpChess\Http\Web\Admin;

use PhpChess\Domain\Look\Skin;
use PhpChess\Domain\System\FeatureSwitches;
use PhpChess\Domain\System\ServerSupport;
use PhpChess\Http\Request;
use PhpChess\Http\Response;
use PhpChess\Infra\Mail\Email;
use PhpChess\Infra\Mail\MailFailed;
use PhpChess\Kernel\Services;

/**
 * Site settings, mail and the feature switches (ADM-02, ID-03, SCH-01, K8).
 *
 *   GET/POST /admin/settings     site name, language, skin, approval of new accounts
 *   GET/POST /admin/mail         mail log file or SMTP server
 *   POST     /admin/mail/test    sends a test email to the admin
 *   GET/POST /admin/switches     every feature switch, on or off
 *
 * Settings live in the settings table (SettingsRepository); every change is
 * written to the audit log. The SMTP password is kept when the field is left empty.
 */
final class SettingsController
{
    public function __construct(
        private readonly Services $services,
        private readonly AdminPages $adminPages,
    ) {
    }

    // --- Site settings ---

    public function showSettings(Request $request): Response
    {
        $settings = $this->services->settings();

        return $this->adminPages->render($request, 'admin/settings', 'admin.settings.title', [
            'siteName' => $settings->getString('site.name', 'phpChess'),
            'siteLanguage' => $settings->getString('site.language', 'en'),
            'languages' => $this->services->translator()->availableLanguages(),
            'skinName' => $settings->getString('look.skin', 'default'),
            'skins' => Skin::installedSkins($this->services->appRoot . '/skins'),
            'approvalRequired' => $settings->getBool('accounts.approval_required', false),
        ]);
    }

    public function saveSettings(Request $request): Response
    {
        $settings = $this->services->settings();
        $siteName = trim($request->formValue('site_name'));
        $language = $request->formValue('site_language');
        $skinName = $request->formValue('skin');

        if ($siteName === '' || mb_strlen($siteName) > 60) {
            return $this->adminPages->redirect($request, '/admin/settings', 'error', 'admin.settings.name_rules');
        }

        $settings->set('site.name', $siteName);
        if (in_array($language, $this->services->translator()->availableLanguages(), true)) {
            $settings->set('site.language', $language);
        }
        if (array_key_exists($skinName, Skin::installedSkins($this->services->appRoot . '/skins'))) {
            $settings->set('look.skin', $skinName);
        }
        $settings->set('accounts.approval_required', $request->formValue('approval_required') === 'yes');

        $this->adminPages->audit($request, 'settings.change', "Site settings saved (name: {$siteName}, skin: {$skinName}).");

        return $this->adminPages->redirect($request, '/admin/settings', 'success', 'admin.saved');
    }

    // --- Mail (ADM-02) ---

    public function showMail(Request $request): Response
    {
        $settings = $this->services->settings();

        return $this->adminPages->render($request, 'admin/mail', 'admin.mail.title', [
            'transport' => $settings->getString('mail.transport', 'log'),
            'fromAddress' => $settings->getString('mail.from_address', 'noreply@' . $request->host),
            'smtpHost' => $settings->getString('mail.smtp_host'),
            'smtpPort' => $settings->getInt('mail.smtp_port', 587),
            'smtpUsername' => $settings->getString('mail.smtp_username'),
            'smtpEncryption' => $settings->getString('mail.smtp_encryption', 'tls'),
            'hasSmtpPassword' => $settings->getString('mail.smtp_password') !== '',
        ]);
    }

    public function saveMail(Request $request): Response
    {
        $settings = $this->services->settings();
        $transport = $request->formValue('transport') === 'smtp' ? 'smtp' : 'log';
        $fromAddress = trim($request->formValue('from_address'));
        if (filter_var($fromAddress, FILTER_VALIDATE_EMAIL) === false) {
            return $this->adminPages->redirect($request, '/admin/mail', 'error', 'account.email_invalid');
        }

        $settings->set('mail.transport', $transport);
        $settings->set('mail.from_address', $fromAddress);
        $settings->set('mail.smtp_host', trim($request->formValue('smtp_host')));
        $settings->set('mail.smtp_port', max(1, min(65535, (int) $request->formValue('smtp_port', '587'))));
        $settings->set('mail.smtp_username', trim($request->formValue('smtp_username')));
        $encryption = $request->formValue('smtp_encryption');
        $settings->set('mail.smtp_encryption', in_array($encryption, ['tls', 'ssl', 'none'], true) ? $encryption : 'tls');
        if ($request->formValue('smtp_password') !== '') {
            $settings->set('mail.smtp_password', $request->formValue('smtp_password'));
        }

        $this->adminPages->audit($request, 'mail.change', "Mail settings saved (transport: {$transport}).");

        return $this->adminPages->redirect($request, '/admin/mail', 'success', 'admin.mail.saved_try_test');
    }

    /** Sends a test email to the admin; on success, mail counts as set up (INS-07 banner). */
    public function sendTestMail(Request $request): Response
    {
        $admin = $this->adminPages->admin();
        try {
            $this->services->mailer()->send(new Email(
                $admin->email,
                $admin->username,
                $this->adminPages->text('admin.mail.test_subject'),
                $this->adminPages->text('admin.mail.test_text'),
            ));
        } catch (MailFailed $error) {
            $this->services->pages()->addFlashMessage('error', $this->adminPages->text('admin.mail.test_failed') . ' ' . $error->getMessage());

            return Response::redirect($request->basePath . '/admin/mail');
        }

        $isRealMail = $this->services->settings()->getString('mail.transport', 'log') === 'smtp';
        $this->services->settings()->set('mail.configured', $isRealMail);
        $messageKey = $isRealMail ? 'admin.mail.test_sent' : 'admin.mail.test_logged';

        return $this->adminPages->redirect($request, '/admin/mail', 'success', $messageKey);
    }

    // --- Feature switches (SCH-01, K8) ---

    public function showSwitches(Request $request): Response
    {
        $switches = $this->services->switches();
        $states = [];
        $unavailable = [];
        foreach (array_keys(FeatureSwitches::ALL) as $name) {
            $states[$name] = $switches->isOn($name);
            // A feature the server can't run is shown greyed out, with the reason (ServerSupport).
            if (!ServerSupport::canRun($name)) {
                $unavailable[$name] = 'admin.switches.needs.' . ServerSupport::neededBy($name);
            }
        }

        return $this->adminPages->render($request, 'admin/switches', 'admin.switches.title', [
            'states' => $states,
            'unavailable' => $unavailable,
        ]);
    }

    public function saveSwitches(Request $request): Response
    {
        $switches = $this->services->switches();
        $changes = [];
        foreach (array_keys(FeatureSwitches::ALL) as $name) {
            $isOn = $request->formValue('switch_' . $name) === 'on';
            if ($switches->isOn($name) !== $isOn) {
                $changes[] = $name . ($isOn ? ' on' : ' off');
            }
            $switches->set($name, $isOn);
        }

        if ($changes !== []) {
            $this->adminPages->audit($request, 'switches.change', 'Switched: ' . implode(', ', $changes) . '.');
        }

        return $this->adminPages->redirect($request, '/admin/switches', 'success', 'admin.saved');
    }
}
