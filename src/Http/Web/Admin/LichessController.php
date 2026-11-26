<?php

declare(strict_types=1);

namespace PhpChess\Http\Web\Admin;

use PhpChess\Domain\ActionRefused;
use PhpChess\Domain\Lichess\ContentScanner;
use PhpChess\Domain\System\FeatureSwitches;
use PhpChess\Http\Request;
use PhpChess\Http\Response;
use PhpChess\Http\Web\MemberPages;
use PhpChess\Infra\Lichess\LichessClient;
use PhpChess\Kernel\Services;

/**
 * The admin's Lichess and study page (C5-1, L-03, A74, A78, A80, A81, A87).
 *
 * Addresses (Routes.php), admins only:
 *   GET  /admin/lichess                    connection test, study themes, imports
 *   POST /admin/lichess/test               the "Test connection" button (works with the switch off, A80)
 *   POST /admin/lichess/themes             create a study theme
 *   POST /admin/lichess/themes/{id}/remove remove a theme and its games
 *   POST /admin/lichess/upload             upload a PGN file into a theme (works offline)
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
            'maxGames' => ContentScanner::MAX_GAMES,
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

    public function createTheme(Request $request): Response
    {
        $title = trim($request->formValue('title'));
        if ($title === '' || mb_strlen($title) > 100 || $this->services->wordFilter()->blocks($title)) {
            return $this->pages->redirect($request, '/admin/lichess', 'error', 'admin.lichess.bad_title');
        }
        $this->services->studies()->createTheme($title, $this->pages->admin()->id);
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
