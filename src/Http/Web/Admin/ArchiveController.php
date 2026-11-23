<?php

declare(strict_types=1);

namespace PhpChess\Http\Web\Admin;

use PhpChess\Domain\ActionRefused;
use PhpChess\Http\Request;
use PhpChess\Http\Response;
use PhpChess\Kernel\Services;

/**
 * The archive page (A42, A43): measure, write an archive file, download
 * it, remove the archived rows, and read a file back in.
 *
 * Addresses (Routes.php), admins only:
 *   GET  /admin/archive?before=DATE   the last check, and what an archive before DATE would hold
 *   POST /admin/archive/check         time the typical reads now
 *   POST /admin/archive/write         write the archive file for a date
 *   GET  /admin/archive/download?before=DATE  download that file
 *   POST /admin/archive/remove        remove the archived rows (only after writing the file)
 *   POST /admin/archive/restore       read an uploaded archive file back in
 */
final class ArchiveController
{
    public function __construct(
        private readonly Services $services,
        private readonly AdminPages $pages,
    ) {
    }

    public function show(Request $request): Response
    {
        $settings = $this->services->settings();
        $before = $request->queryValue('before', gmdate('Y-01-01', $this->services->now));
        $archive = $this->services->archive();
        try {
            $preview = $archive->preview($before);
            $fileExists = $archive->existingFile($before) !== '';
        } catch (ActionRefused) {
            $preview = [];
            $fileExists = false;
        }

        return $this->pages->render($request, 'admin/archive', 'admin.archive.title', [
            'before' => $before,
            'preview' => $preview,
            'fileExists' => $fileExists,
            'isSuggested' => $archive->isSuggested(),
            'lastCheckAt' => $settings->getInt('archive.last_check_at'),
            'lastSlowestMs' => $settings->getInt('archive.last_slowest_ms'),
            'cutoffMs' => $archive->cutoffMs(),
        ]);
    }

    public function check(Request $request): Response
    {
        $result = $this->services->archive()->check($this->services->now);

        return $this->services->pages()->redirectWithMessage($request, '/admin/archive', $result['too_slow'] ? 'error' : 'success', $this->services->translator()->text(
            $result['too_slow'] ? 'admin.archive.too_slow' : 'admin.archive.fast_enough',
            ['ms' => $result['slowest_ms'], 'cutoff' => $result['cutoff_ms']],
        ));
    }

    public function write(Request $request): Response
    {
        $before = $request->formValue('before');
        try {
            $result = $this->services->archive()->write($before, $this->services->now);
        } catch (ActionRefused $refusal) {
            return $this->services->pages()->redirectWithMessage($request, '/admin/archive', 'error', $this->services->translator()->text($refusal->languageKey));
        }
        $this->pages->audit($request, 'archive.write', "before {$before}: {$result['rows']} rows");

        return $this->services->pages()->redirectWithMessage(
            $request,
            '/admin/archive?before=' . rawurlencode($before),
            'success',
            $this->services->translator()->text('admin.archive.written', ['count' => $result['rows']]),
        );
    }

    public function download(Request $request): Response
    {
        try {
            $path = $this->services->archive()->existingFile($request->queryValue('before'));
        } catch (ActionRefused) {
            $path = '';
        }
        $bytes = $path === '' ? false : file_get_contents($path);
        if ($bytes === false) {
            return Response::text('There is no archive file for this date.', 404);
        }

        return new Response(200, $bytes, [
            'Content-Type' => 'application/gzip',
            'Content-Disposition' => 'attachment; filename="' . basename($path) . '"',
            'Cache-Control' => 'no-store',
        ]);
    }

    public function remove(Request $request): Response
    {
        $before = $request->formValue('before');
        if ($request->formValue('kept_file') !== 'yes') {
            return $this->pages->redirect($request, '/admin/archive?before=' . rawurlencode($before), 'error', 'admin.archive.confirm_kept');
        }
        try {
            $removed = $this->services->archive()->remove($before);
        } catch (ActionRefused $refusal) {
            return $this->services->pages()->redirectWithMessage($request, '/admin/archive', 'error', $this->services->translator()->text($refusal->languageKey));
        }
        $this->pages->audit($request, 'archive.remove', "before {$before}: {$removed} rows removed");

        return $this->services->pages()->redirectWithMessage(
            $request,
            '/admin/archive',
            'success',
            $this->services->translator()->text('admin.archive.removed', ['count' => $removed]),
        );
    }

    public function restore(Request $request): Response
    {
        $file = $request->file('archive');
        if ($file === null || !$file->isUsable()) {
            return $this->pages->redirect($request, '/admin/archive', 'error', 'archive.not_an_archive');
        }
        try {
            $result = $this->services->archive()->restore($file->path);
        } catch (ActionRefused $refusal) {
            return $this->services->pages()->redirectWithMessage($request, '/admin/archive', 'error', $this->services->translator()->text($refusal->languageKey));
        }
        $this->pages->audit($request, 'archive.restore', "{$result['restored']} rows restored, {$result['skipped']} skipped");

        return $this->services->pages()->redirectWithMessage(
            $request,
            '/admin/archive',
            'success',
            $this->services->translator()->text('admin.archive.restored', ['restored' => $result['restored'], 'skipped' => $result['skipped']]),
        );
    }
}
