<?php

declare(strict_types=1);

namespace PhpChess\Http\Web\Admin;

use PhpChess\Domain\Computer\ComputerGames;
use PhpChess\Http\Request;
use PhpChess\Http\Response;
use PhpChess\Kernel\Services;

/**
 * The admin's engine page (AI-07, decisions A47, A49, A51):
 *
 *   GET  /admin/engine   the active engine (name, version, licence, source),
 *                        the depth cap, every computer player with its
 *                        settings, and the latest calibration result
 *   POST /admin/engine   saves the depth cap
 *
 * Read-only apart from the depth cap (A51). How to use another engine is in
 * docs/ENGINE_HOWTO.md (A47).
 */
final class EngineController
{
    // --- The active engine (A47) ---
    public const string ENGINE_NAME = 'GarboChess-JS';
    public const string ENGINE_VERSION = 'commit 4ff0f9e (2012-12-25), unchanged';
    public const string ENGINE_LICENCE = 'BSD-3-Clause';
    public const string ENGINE_SOURCE = 'github.com/glinscott/Garbochess-JS';
    public const string ENGINE_FOLDER = '/assets/vendor/garbochess/';

    public function __construct(
        private readonly Services $services,
        private readonly AdminPages $adminPages,
    ) {
    }

    public function show(Request $request): Response
    {
        $computer = $this->services->play()->computerGames();

        return $this->adminPages->render($request, 'admin/engine', 'admin.engine.title', [
            'engineName' => self::ENGINE_NAME,
            'engineVersion' => self::ENGINE_VERSION,
            'engineLicence' => self::ENGINE_LICENCE,
            'engineSource' => self::ENGINE_SOURCE,
            'licenceUrl' => $request->basePath . self::ENGINE_FOLDER . 'LICENSE',
            'depthCap' => $computer->depthCap($this->services->settings()),
            'players' => $computer->allPlayers(),
            'calibration' => $computer->calibrationResult(),
        ]);
    }

    /** Saves the highest search depth any computer player may use (A49). */
    public function save(Request $request): Response
    {
        $cap = max(ComputerGames::LOWEST_DEPTH_CAP, min(ComputerGames::HIGHEST_DEPTH_CAP, (int) $request->formValue('depth_cap', (string) ComputerGames::DEFAULT_DEPTH_CAP)));
        $this->services->settings()->set(ComputerGames::DEPTH_CAP_SETTING, $cap);
        $this->adminPages->audit($request, 'settings.engine', 'Engine depth cap set to ' . $cap . '.');

        return $this->adminPages->redirect($request, '/admin/engine', 'success', 'admin.saved');
    }
}
