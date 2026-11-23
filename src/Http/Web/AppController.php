<?php

declare(strict_types=1);

namespace PhpChess\Http\Web;

use PhpChess\Http\Request;
use PhpChess\Http\Response;
use PhpChess\Kernel\Services;

/**
 * The installable app (UI-05, UI-08): the manifest, the service worker and
 * the offline page.
 *
 * Addresses (Routes.php), for everyone:
 *   GET /manifest.webmanifest   name, icons, start page and colours, for "Add to Home Screen"
 *   GET /sw.js                  the service worker (templates/pwa/service_worker.php)
 *   GET /offline                the page shown when there is no connection and nothing saved
 * Both files are made here, not stored, because they need the install's
 * address (base path) and name. The service worker must come from the top
 * of the install, so it may look after every page of it.
 */
final class AppController
{
    public function __construct(private readonly Services $services)
    {
    }

    public function manifest(Request $request): Response
    {
        $siteName = $this->services->settings()->getString('site.name', 'phpChess');
        $base = $request->basePath;
        $manifest = [
            'name' => $siteName,
            'short_name' => mb_substr($siteName, 0, 12),
            'start_url' => $base . '/',
            'scope' => $base . '/',
            'display' => 'standalone',
            'background_color' => '#ffffff',
            'theme_color' => '#2f6f4f',
            'icons' => [
                ['src' => $base . '/assets/icons/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png'],
                ['src' => $base . '/assets/icons/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png'],
                ['src' => $base . '/assets/icons/icon-maskable-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
            ],
        ];

        return new Response(200, (string) json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), [
            'Content-Type' => 'application/manifest+json; charset=utf-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    public function serviceWorker(Request $request): Response
    {
        $script = $this->services->template()->render('pwa/service_worker');

        return new Response(200, $script, [
            'Content-Type' => 'text/javascript; charset=utf-8',
            'Cache-Control' => 'no-cache',
            'Service-Worker-Allowed' => $request->basePath . '/',
        ]);
    }

    public function offline(Request $request): Response
    {
        return $this->services->pages()->render($request, 'pwa/offline', $this->services->translator()->text('offline.title'));
    }
}
