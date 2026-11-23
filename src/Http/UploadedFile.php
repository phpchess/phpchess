<?php

declare(strict_types=1);

namespace PhpChess\Http;

/**
 * One file sent with a form (C-02 avatars, the archive upload in A42).
 *
 * "path" is where PHP saved the file for this request. "fromBrowser" is
 * true for a real upload, so the code can check is_uploaded_file(); tests
 * build one from a file of their own and pass false.
 */
final class UploadedFile
{
    public function __construct(
        public readonly string $path,
        public readonly int $size,
        public readonly int $error = UPLOAD_ERR_OK,
        public readonly bool $fromBrowser = false,
    ) {
    }

    /** True when a file arrived complete and, for a real upload, really came from the browser. */
    public function isUsable(): bool
    {
        if ($this->error !== UPLOAD_ERR_OK || $this->path === '' || !is_file($this->path)) {
            return false;
        }

        return !$this->fromBrowser || is_uploaded_file($this->path);
    }

    /** The file's bytes, or '' when it can't be read. */
    public function contents(): string
    {
        $contents = $this->isUsable() ? file_get_contents($this->path) : false;

        return $contents === false ? '' : $contents;
    }
}
