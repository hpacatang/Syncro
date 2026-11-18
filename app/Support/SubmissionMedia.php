<?php

namespace App\Support;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

final class SubmissionMedia
{
    public const MAX_FILES = 10;

    public const MAX_FILE_SIZE_KB = 50 * 1024;

    public const MAX_TOTAL_SIZE_BYTES = 100 * 1024 * 1024;

    private const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'jpe', 'jfif', 'png', 'webp', 'gif', 'avif', 'bmp'];

    private const ADDITIONAL_IMAGE_EXTENSIONS = ['heic', 'heif', 'tif', 'tiff'];

    private const VIDEO_EXTENSIONS = ['mp4', 'mov', 'webm', 'ogg', 'avi', 'mkv', 'wmv', 'm4v', '3gp'];

    private const DOCUMENT_EXTENSIONS = ['doc', 'docx', 'pdf', 'txt'];

    public static function allowedExtensions(): array
    {
        return [
            ...self::IMAGE_EXTENSIONS,
            ...self::ADDITIONAL_IMAGE_EXTENSIONS,
            ...self::VIDEO_EXTENSIONS,
            ...self::DOCUMENT_EXTENSIONS,
        ];
    }

    private const DISKS = ['supabase', 'public'];

    public static function isImage(string $path): bool
    {
        return in_array(
            strtolower(pathinfo($path, PATHINFO_EXTENSION)),
            self::IMAGE_EXTENSIONS,
            true
        );
    }

    public static function isVideo(string $path): bool
    {
        return in_array(
            strtolower(pathinfo($path, PATHINFO_EXTENSION)),
            self::VIDEO_EXTENSIONS,
            true
        );
    }

    public static function url(?string $path): ?string
    {
        if (! $path || ! is_string($path)) {
            return null;
        }

        return route('submission.media', [
            'path' => ltrim($path, '/'),
        ]);
    }

    public static function resolve(string $path): ?array
    {
        $path = ltrim($path, '/');

        foreach (self::disksToTry() as $disk) {
            try {
                if (Storage::disk($disk)->exists($path)) {
                    return ['disk' => $disk, 'path' => $path];
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }

    public static function publicUrl(string $path): ?string
    {
        $resolved = self::resolve($path);
        if ($resolved === null) {
            return null;
        }

        try {
            return self::diskPublicUrl(Storage::disk($resolved['disk']), $resolved['disk'], $resolved['path']);
        } catch (\Throwable) {
            return null;
        }
    }

    private static function diskPublicUrl(Filesystem $storage, string $disk, string $path): string
    {
        if ($disk === 'supabase') {
            $base = rtrim((string) config('filesystems.disks.supabase.public_url', ''), '/');
            if ($base !== '') {
                return $base.'/'.ltrim($path, '/');
            }
        }

        return $storage->url($path);
    }

    private static function disksToTry(): array
    {
        $default = (string) config('filesystems.default', 'supabase');

        return array_values(array_unique([$default, ...self::DISKS]));
    }
}
