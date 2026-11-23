<?php

declare(strict_types=1);

namespace PhpChess\Http\Web;

use PhpChess\Domain\Look\Skin;
use PhpChess\Http\Request;
use PhpChess\Http\Response;
use PhpChess\Kernel\Services;

/**
 * Serves the active skin's CSS variables and logo (UI-01).
 *
 *   GET /skin/theme.css   the colours and fonts of the skin, as CSS variables
 *   GET /skin/logo        the skin's logo image
 *
 * The skins/ folder itself is never web-visible (it has a deny .htaccess),
 * so these two addresses are the only way out of it, and they only ever
 * hand out the CSS made from skin.json and the one logo file it names.
 */
final class SkinController
{
    public function __construct(private readonly Services $services)
    {
    }

    public function themeCss(Request $request): Response
    {
        return new Response(200, $this->services->skin()->themeCss(), [
            'Content-Type' => 'text/css; charset=utf-8',
            'Cache-Control' => 'public, max-age=300',
        ]);
    }

    public function logo(Request $request): Response
    {
        $skin = $this->services->skin();
        $file = $this->services->appRoot . '/skins/' . $skin->folderName . '/' . $skin->logoFile;
        $extension = strtolower(pathinfo($skin->logoFile, PATHINFO_EXTENSION));

        if ($skin->logoFile === '' || !is_file($file) || !isset(Skin::LOGO_TYPES[$extension])) {
            return Response::text('No logo.', 404);
        }

        return new Response(200, (string) file_get_contents($file), [
            'Content-Type' => Skin::LOGO_TYPES[$extension],
            // An SVG file could hold a script. The site's Content-Security-Policy
            // (SecurityHeaders) allows no inline scripts, so opened on its own it runs nothing.
            'Cache-Control' => 'public, max-age=300',
        ]);
    }
}
