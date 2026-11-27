<?php

declare(strict_types=1);

namespace PhpChess\Http\Web\Admin;

use PhpChess\Domain\ActionRefused;
use PhpChess\Domain\Lichess\ContentScanner;
use PhpChess\Domain\Lichess\LichessContent;
use PhpChess\Domain\Lichess\StudyImport;
use PhpChess\Domain\Lichess\StudyTopics;
use PhpChess\Domain\System\FeatureSwitches;
use PhpChess\Http\Request;
use PhpChess\Http\Response;
use PhpChess\Domain\Study\StudyBoards;
use PhpChess\Http\Web\MemberPages;
use PhpChess\Infra\Lichess\LichessClient;
use PhpChess\Kernel\Services;

/**
 * The admin's Lichess and study page (C5-1, L-03, A74, A78, A80, A81, A87).
 *
 * Addresses (Routes.php), admins only:
 *   GET  /admin/lichess                    connection test, study themes, imports
 *   POST /admin/lichess/test               the "Test connection" button (works with the switch off, A80)
 *   POST /admin/lichess/proxy              the Internet connection: direct or through a proxy (A94)
 *   POST /admin/lichess/content            "external content" and the kinds of Lichess content (A96)
 *   POST /admin/lichess/import-study       a Lichess study into a theme ("external content", A95)
 *   POST /admin/lichess/themes             create a study theme
 *   POST /admin/lichess/themes/{id}/remove remove a theme and its games
 *   POST /admin/lichess/upload             upload a PGN file into a theme (works offline)
 *   POST /admin/lichess/paste              paste PGN text into a theme (works offline, B37)
 *   POST /admin/lichess/settings           players' limits and the paste switch (B37)
 *   POST /admin/lichess/import-game        one Lichess game into a theme ("external content")
 *   POST /admin/lichess/import-player      a Lichess player's newest games, up to 50 ("external content")
 *
 * Every game goes through the content scanner (A81); rejected games are
 * listed in the audit log under "study.rejected".
 */
final class LichessController
{
    public function __construct(
        private readonly Services $services,
        private readonly AdminPages $pages,
    ) {
    }

    public function show(Request $request): Response
    {
        $settings = $this->services->settings();

        return $this->pages->render($request, 'admin/lichess', 'admin.lichess.title', [
            'themes' => $this->services->studies()->themes(),
            'lichessIsOn' => $this->services->switches()->isOn(FeatureSwitches::EXTERNAL_CONTENT),
            'testPassedAt' => $settings->getInt(LichessClient::TEST_PASSED_AT_SETTING),
            'testResult' => $settings->getString(LichessClient::TEST_RESULT_SETTING),
            'proxy' => $this->services->proxySettings(),
            'content' => new LichessContent($this->services->switches(), $settings),
            'contentKinds' => LichessContent::KINDS,
            'sections' => StudyTopics::SECTIONS,
            'ownNameSections' => StudyTopics::OWN_NAME_SECTIONS,
            'maxGames' => ContentScanner::MAX_GAMES,
            'maxBytes' => ContentScanner::MAX_BYTES,
            'maxOwnGames' => StudyImport::maxOwnGamesIn($settings),
            'importsPerHour' => StudyImport::importsPerHourIn($settings),
            'pasteIsOpen' => StudyImport::pasteIsOpenIn($settings),
            'boardCreators' => StudyBoards::creatorsIn($settings),
            'ownGameDays' => StudyImport::ownGameDaysIn($settings),
            // A plain link that opens in a new tab: our server makes no request (A87).
            'lichessDatabaseLink' => 'https://database.lichess.org/',
        ]);
    }

    public function test(Request $request): Response
    {
        $passed = $this->services->lichess()->selfTest();
        $this->pages->audit($request, 'lichess.test', $passed ? 'passed' : 'failed');

        return $this->pages->redirect($request, '/admin/lichess', $passed ? 'success' : 'error', $passed ? 'admin.lichess.test_passed' : 'admin.lichess.test_failed');
    }

    /** The Internet connection: direct, or through the network's proxy (A94). */
    public function saveProxy(Request $request): Response
    {
        try {
            $this->services->proxySettings()->save(
                $request->formValue('proxy_mode'),
                trim($request->formValue('proxy_host')),
                (int) $request->formValue('proxy_port', '8080'),
                trim($request->formValue('proxy_user')),
                $request->formValue('proxy_password'),
                $request->formValue('proxy_clear_login') === 'on',
            );
        } catch (ActionRefused $refusal) {
            return $this->pages->redirect($request, '/admin/lichess', 'error', $refusal->languageKey);
        }
        // The password never goes into the audit log, only that the settings changed.
        $this->pages->audit($request, 'lichess.proxy', $this->services->proxySettings()->usesProxy() ? 'proxy set' : 'direct connection');

        return $this->pages->redirect($request, '/admin/lichess', 'success', 'admin.saved');
    }

    /**
     * "External content" and the kinds of Lichess content (A96). The switch
     * can only be turned on after a passed connection test (A80); it is the
     * same switch as on the switches page.
     */
    public function saveContent(Request $request): Response
    {
        $wantsOn = $request->formValue('external_content') === 'on';
        $switches = $this->services->switches();
        if ($wantsOn && !$this->services->lichess()->hasPassedSelfTest()) {
            return $this->pages->redirect($request, '/admin/lichess', 'error', 'admin.switches.needs.lichess_test');
        }
        $switches->set(FeatureSwitches::EXTERNAL_CONTENT, $wantsOn);
        $content = new LichessContent($switches, $this->services->settings());
        foreach (LichessContent::KINDS as $kind) {
            $content->choose($kind, $request->formValue('show_' . $kind) === 'on');
        }
        $this->pages->audit($request, 'lichess.content', 'external content ' . ($wantsOn ? 'on' : 'off'));

        return $this->pages->redirect($request, '/admin/lichess', 'success', 'admin.saved');
    }

    public function importStudy(Request $request): Response
    {
        $link = $request->formValue('link');

        return $this->runImport($request, 'lichess study', fn (int $themeId, int $adminId): array => $this->services->studyImport()->importStudy($link, $themeId, $adminId));
    }

    /**
     * A new theme from the Lichess list (A95): the form sends "section:topic",
     * or "openings:" / "famous_games:" with the admin's own name in "title".
     */
    public function createTheme(Request $request): Response
    {
        [$section, $topic] = array_pad(explode(':', $request->formValue('topic'), 2), 2, '');
        if (!StudyTopics::isValid($section, $topic)) {
            return $this->pages->redirect($request, '/admin/lichess', 'error', 'admin.lichess.bad_title');
        }
        if ($topic !== '') {
            $title = $this->pages->text(StudyTopics::topicKey($section, $topic));
        } else {
            $title = trim($request->formValue('title'));
            if ($title === '' || mb_strlen($title) > 100 || $this->services->wordFilter()->blocks($title)) {
                return $this->pages->redirect($request, '/admin/lichess', 'error', 'admin.lichess.bad_title');
            }
        }
        $this->services->studies()->createTheme($title, $this->pages->admin()->id, $section, $topic);
        $this->pages->audit($request, 'study.theme', 'created "' . $title . '"');

        return $this->pages->redirect($request, '/admin/lichess', 'success', 'admin.saved');
    }

    /** @param array<string, string> $parts */
    public function removeTheme(Request $request, array $parts): Response
    {
        $themeId = MemberPages::idFrom($parts);
        $this->services->studies()->removeTheme($themeId);
        $this->pages->audit($request, 'study.theme', 'removed theme ' . $themeId);

        return $this->pages->redirect($request, '/admin/lichess', 'success', 'admin.saved');
    }

    public function upload(Request $request): Response
    {
        $file = $request->file('pgn_file');
        if ($file === null || !$file->isUsable()) {
            return $this->pages->redirect($request, '/admin/lichess', 'error', 'admin.lichess.no_file');
        }
        if ($file->size > ContentScanner::MAX_BYTES) {
            return $this->pages->redirect($request, '/admin/lichess', 'error', 'admin.lichess.too_large');
        }

        return $this->runImport($request, 'upload', fn (int $themeId, int $adminId): array => $this->services->studyImport()->importUpload($file->contents(), $themeId, $adminId));
    }

    /** PGN text pasted into the page, scanned like an upload (B37). */
    public function paste(Request $request): Response
    {
        $pgnText = $request->formValue('pgn');
        if (trim($pgnText) === '') {
            return $this->pages->redirect($request, '/admin/lichess', 'error', 'admin.lichess.no_file');
        }
        if (strlen($pgnText) > ContentScanner::MAX_BYTES) {
            return $this->pages->redirect($request, '/admin/lichess', 'error', 'admin.lichess.too_large');
        }

        return $this->runImport($request, 'paste', fn (int $themeId, int $adminId): array => $this->services->studyImport()->importUpload($pgnText, $themeId, $adminId));
    }

    /** Players' limits and whether every player may paste PGN (B37). */
    public function saveSettings(Request $request): Response
    {
        $settings = $this->services->settings();
        $settings->set(StudyImport::MAX_OWN_SETTING, max(1, min(500, (int) $request->formValue('max_own_games', (string) StudyImport::MAX_OWN_GAMES))));
        $settings->set(StudyImport::PER_HOUR_SETTING, max(1, min(100, (int) $request->formValue('imports_per_hour', (string) StudyImport::IMPORTS_PER_HOUR))));
        $settings->set(StudyImport::PASTE_SETTING, $request->formValue('paste_for_players') === 'on');
        $settings->set(StudyImport::DAYS_SETTING, max(0, min(3650, (int) $request->formValue('own_game_days', (string) StudyImport::OWN_GAME_DAYS))));
        // Who may make study boards (C6-1, A133).
        $creators = $request->formValue('board_creators');
        $settings->set(StudyBoards::CREATORS_SETTING, in_array($creators, StudyBoards::CREATORS, true) ? $creators : StudyBoards::CREATORS_ALL);
        $this->pages->audit($request, 'study.settings', 'players\' study limits and paste switch saved');

        return $this->pages->redirect($request, '/admin/lichess', 'success', 'admin.lichess.settings_saved');
    }

    public function importGame(Request $request): Response
    {
        $link = $request->formValue('link');

        return $this->runImport($request, 'lichess game', function (int $themeId, int $adminId) use ($link): array {
            $this->services->studyImport()->importOneGame($link, $themeId, $adminId);

            return ['added' => 1, 'rejected' => 0];
        });
    }

    public function importPlayer(Request $request): Response
    {
        $username = trim($request->formValue('username'));
        $count = (int) $request->formValue('count', '10');

        return $this->runImport($request, 'lichess player ' . $username, fn (int $themeId, int $adminId): array => $this->services->studyImport()->importUserGames($username, $count, $themeId, $adminId));
    }

    /**
     * Runs one import into the chosen theme and reports how many games were
     * added and how many the scanner kept out.
     *
     * @param \Closure(int $themeId, int $adminId): array{added: int, rejected: int} $import
     */
    private function runImport(Request $request, string $from, \Closure $import): Response
    {
        $themeId = (int) $request->formValue('theme_id');
        try {
            $counts = $import($themeId, $this->pages->admin()->id);
        } catch (ActionRefused $refusal) {
            return $this->services->pages()->redirectWithMessage($request, '/admin/lichess', 'error', $this->services->translator()->text($refusal->languageKey, $refusal->values));
        }
        $this->pages->audit($request, 'study.import', $from . ': ' . $counts['added'] . ' added, ' . $counts['rejected'] . ' rejected');

        return $this->services->pages()->redirectWithMessage(
            $request,
            '/admin/lichess',
            $counts['added'] > 0 ? 'success' : 'error',
            $this->services->translator()->text('admin.lichess.imported', $counts),
        );
    }
}
