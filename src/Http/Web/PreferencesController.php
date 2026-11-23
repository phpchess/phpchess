<?php

declare(strict_types=1);

namespace PhpChess\Http\Web;

use PhpChess\Domain\Identity\AccountRefused;
use PhpChess\Domain\Identity\PasswordRules;
use PhpChess\Domain\Identity\Preferences;
use PhpChess\Domain\Identity\User;
use PhpChess\Http\Request;
use PhpChess\Http\Response;
use LogicException;
use PhpChess\Kernel\Services;

/**
 * The logged-in member's own settings: preferences and password (ID-13).
 *
 * Addresses (Routes.php), members only:
 *   GET/POST /account/preferences   language, light/dark, board, pieces, email notices, move emails (A34)
 *   GET/POST /account/password      change the password (the old one is asked for)
 */
final class PreferencesController
{
    public function __construct(private readonly Services $services)
    {
    }

    // --- Preferences ---

    public function showPreferences(Request $request): Response
    {
        $user = $this->currentUser();

        return $this->services->pages()->render($request, 'account/preferences', $this->text('preferences.title'), [
            'preferences' => $this->services->preferenceRepository()->findForUser($user->id),
            'languages' => $this->services->translator()->availableLanguages(),
            'moveEmailModes' => $this->services->moveEmails()->allowedModes(),
            'moveEmail' => $this->services->moveEmails()->modeFor($user->id),
        ]);
    }

    public function savePreferences(Request $request): Response
    {
        $user = $this->currentUser();
        $languages = $this->services->translator()->availableLanguages();

        // Only values from the fixed lists are kept; anything else falls back to the default.
        $preferences = new Preferences(
            language: self::oneOf($request->formValue('language'), $languages, 'en'),
            colourScheme: self::oneOf($request->formValue('colour_scheme'), Preferences::COLOUR_SCHEMES, 'auto'),
            boardTheme: self::oneOf($request->formValue('board_theme'), Preferences::BOARD_THEMES, 'green'),
            pieceSet: self::oneOf($request->formValue('piece_set'), Preferences::PIECE_SETS, 'standard'),
            emailNotices: $request->formValue('email_notices') === 'yes',
        );
        $this->services->preferenceRepository()->save($user->id, $preferences);
        // How move and challenge emails arrive (A34), among the modes the admin allows.
        $moveEmail = $request->formValue('move_email');
        if (in_array($moveEmail, $this->services->moveEmails()->allowedModes(), true)) {
            $this->services->preferenceRepository()->saveMoveEmail($user->id, $moveEmail);
        }

        return $this->services->pages()->redirectWithMessage($request, '/account/preferences', 'success', $this->text('preferences.saved'));
    }

    // --- Password ---

    public function showPasswordForm(Request $request): Response
    {
        return $this->services->pages()->render($request, 'account/password', $this->text('password.title'), ['error' => '']);
    }

    public function changePassword(Request $request): Response
    {
        $user = $this->currentUser();
        $logins = $this->services->loginService();

        try {
            if (!$logins->isPasswordRight($user->id, $request->formValue('old_password'))) {
                throw new AccountRefused('password.old_wrong');
            }
            PasswordRules::checkNewPassword($request->formValue('password'), $request->formValue('password_again'), $user->username);
        } catch (AccountRefused $refusal) {
            return $this->services->pages()->render($request, 'account/password', $this->text('password.title'), [
                'error' => $this->text($refusal->languageKey, $refusal->values),
            ], 422);
        }

        $this->services->users()->setPasswordHash($user->id, PasswordRules::hash($request->formValue('password')));
        // Other devices that were remembered must log in with the new password.
        $logins->forgetAllDevices($user->id);

        return $this->services->pages()->redirectWithMessage($request, '/account/preferences', 'success', $this->text('password.changed'));
    }

    // --- Helpers ---

    /** The logged-in user; the route is for members only, so there always is one. */
    private function currentUser(): User
    {
        $user = $this->services->authentication()->currentUser();
        if ($user === null) {
            throw new LogicException('A members-only route ran without a logged-in user.');
        }

        return $user;
    }

    /**
     * @param list<string> $allowed
     */
    private static function oneOf(string $value, array $allowed, string $default): string
    {
        return in_array($value, $allowed, true) ? $value : $default;
    }

    /**
     * @param array<string, string|int> $values
     */
    private function text(string $key, array $values = []): string
    {
        return $this->services->translator()->text($key, $values);
    }
}
