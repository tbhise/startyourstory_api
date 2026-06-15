<?php

namespace App\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Image optimisation helper.
 *
 * Converts uploaded raster images (jpg/jpeg/png/webp) to optimised WebP using
 * the PHP GD extension — no third-party packages required. If GD or its WebP
 * support is unavailable for any reason, it transparently falls back to storing
 * the original file, so callers never fail because of the optimisation step.
 *
 * Only NEW uploads pass through here; existing stored images are untouched, so
 * backward compatibility is preserved.
 */
class ImageHelper
{
    /** Formats we are willing to read + optimise. */
    public const ALLOWED_MIMES = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    /**
     * Optimise an uploaded image to WebP and store it on the given disk.
     *
     * @param  UploadedFile $file     The uploaded image.
     * @param  string       $dir      Target directory on the disk (e.g. "blog-images/featured").
     * @param  string       $disk     Filesystem disk (default "public").
     * @param  int          $quality  WebP quality 0-100 (default 82 — balance of quality/size).
     * @return string                 The stored relative path (e.g. "blog-images/featured/abc123.webp").
     */
    public static function optimizeToWebp(
        UploadedFile $file,
        string $dir,
        string $disk = 'public',
        int $quality = 82
    ): string {
        $dir = trim($dir, '/');

        // If GD or WebP encoding is unavailable, store the original untouched.
        if (! self::canEncodeWebp()) {
            return $file->store($dir, $disk);
        }

        try {
            $src = self::createImageResource($file);
            if ($src === null) {
                // Unsupported/corrupt — fall back to storing the original.
                return $file->store($dir, $disk);
            }

            $filename = $dir . '/' . Str::random(40) . '.webp';
            $tmpPath  = tempnam(sys_get_temp_dir(), 'webp');

            $ok = imagewebp($src, $tmpPath, max(0, min(100, $quality)));
            imagedestroy($src);

            if (! $ok) {
                @unlink($tmpPath);
                return $file->store($dir, $disk);
            }

            Storage::disk($disk)->put($filename, file_get_contents($tmpPath));
            @unlink($tmpPath);

            return $filename;
        } catch (\Throwable $e) {
            Log::warning('ImageHelper::optimizeToWebp fell back to original', [
                'message' => $e->getMessage(),
            ]);
            return $file->store($dir, $disk);
        }
    }

    /** Whether GD is present and can encode WebP on this server. */
    private static function canEncodeWebp(): bool
    {
        return function_exists('imagewebp') && function_exists('imagecreatefromstring');
    }

    /**
     * Build a GD image resource from the uploaded file, preserving PNG/WebP
     * transparency. Returns null for unsupported types.
     */
    private static function createImageResource(UploadedFile $file): ?\GdImage
    {
        $path = $file->getRealPath();
        $mime = $file->getMimeType();

        $img = match ($mime) {
            'image/jpeg' => function_exists('imagecreatefromjpeg') ? imagecreatefromjpeg($path) : null,
            'image/png'  => function_exists('imagecreatefrompng')  ? imagecreatefrompng($path)  : null,
            'image/webp' => function_exists('imagecreatefromwebp') ? imagecreatefromwebp($path) : null,
            default      => null,
        };

        if (! $img) {
            return null;
        }

        // Preserve alpha channel for PNG/WebP sources.
        if ($mime === 'image/png' || $mime === 'image/webp') {
            imagepalettetotruecolor($img);
            imagealphablending($img, false);
            imagesavealpha($img, true);
        }

        return $img;
    }
}
