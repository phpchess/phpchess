<?php

declare(strict_types=1);

namespace PhpChess\Domain\Community;

use PhpChess\Domain\ActionRefused;
use PhpChess\Http\UploadedFile;
use PhpChess\Infra\Community\ProfileRepository;

/**
 * Avatars (C-02): a bundled picture, or the member's own upload.
 *
 * The bundled avatars are our own SVG files in public/assets/avatars/ (A41).
 * An upload is checked before anything is kept (C2-2 test "upload validation"):
 *   1. it arrived complete, and is at most MAX_BYTES;
 *   2. PHP recognises it as a JPEG, PNG, GIF or WebP picture of sensible size;
 *   3. GD can read it.
 * It is then re-encoded by GD (A41) into a new PNG of at most 256 by 256
 * pixels. Re-encoding drops anything hidden in the file (scripts, metadata),
 * so only the pixels are kept. The file goes to storage/uploads/avatars/,
 * outside public/, and is served by the /avatars/{id} route.
 */
final class Avatars
{
    /** The bundled avatars: public/assets/avatars/<name>.svg. */
    public const array BUNDLED = ['king', 'queen', 'rook', 'bishop', 'knight', 'pawn', 'circle', 'square'];

    public const string UPLOADED = 'upload';
    public const int MAX_BYTES = 2_000_000;
    public const int MAX_SOURCE_PIXELS = 5000;
    public const int SIZE = 256;

    private const array ALLOWED_TYPES = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP];

    public function __construct(
        private readonly ProfileRepository $profiles,
        private readonly string $uploadFolder,
    ) {
    }

    /** Chooses a bundled avatar, or none with ''. */
    public function choose(int $userId, string $name): void
    {
        if ($name !== '' && !in_array($name, self::BUNDLED, true)) {
            throw new ActionRefused('avatar.unknown');
        }
        $this->deleteUpload($userId);
        $this->profiles->saveAvatar($userId, $name);
    }

    /** Checks, re-encodes and keeps an uploaded picture; throws ActionRefused when it is not acceptable. */
    public function upload(int $userId, UploadedFile $file): void
    {
        if (!function_exists('imagecreatefromstring')) {
            throw new ActionRefused('avatar.no_image_support');
        }
        if (!$file->isUsable()) {
            throw new ActionRefused('avatar.not_received');
        }
        if ($file->size > self::MAX_BYTES || filesize($file->path) > self::MAX_BYTES) {
            throw new ActionRefused('avatar.too_big', ['max' => (int) (self::MAX_BYTES / 1_000_000)]);
        }

        $bytes = $file->contents();
        $info = @getimagesizefromstring($bytes);
        if ($info === false || !in_array($info[2], self::ALLOWED_TYPES, true)) {
            throw new ActionRefused('avatar.not_a_picture');
        }
        if ($info[0] < 1 || $info[1] < 1 || $info[0] > self::MAX_SOURCE_PIXELS || $info[1] > self::MAX_SOURCE_PIXELS) {
            throw new ActionRefused('avatar.bad_size', ['max' => self::MAX_SOURCE_PIXELS]);
        }
        $source = @imagecreatefromstring($bytes);
        if ($source === false) {
            throw new ActionRefused('avatar.not_a_picture');
        }

        $this->saveSquare($source, $this->uploadPath($userId));
        $this->profiles->saveAvatar($userId, self::UPLOADED);
    }

    /** Where a member's uploaded picture is kept. */
    public function uploadPath(int $userId): string
    {
        return $this->uploadFolder . '/' . $userId . '.png';
    }

    /** Removes a member's uploaded picture, if there is one. */
    public function deleteUpload(int $userId): void
    {
        $path = $this->uploadPath($userId);
        if (is_file($path)) {
            unlink($path);
        }
    }

    /** Crops the middle square, scales it to at most SIZE pixels and writes a new PNG. */
    private function saveSquare(\GdImage $source, string $path): void
    {
        $width = imagesx($source);
        $height = imagesy($source);
        $side = min($width, $height);
        $targetSide = max(1, min(self::SIZE, $side));

        $target = imagecreatetruecolor($targetSide, $targetSide);
        if ($target === false) {
            throw new ActionRefused('avatar.not_a_picture');
        }
        imagealphablending($target, false);
        imagesavealpha($target, true);
        imagecopyresampled(
            $target,
            $source,
            0,
            0,
            intdiv($width - $side, 2),
            intdiv($height - $side, 2),
            $targetSide,
            $targetSide,
            $side,
            $side,
        );

        if (!is_dir($this->uploadFolder)) {
            mkdir($this->uploadFolder, 0775, true);
        }
        imagepng($target, $path);
    }
}
