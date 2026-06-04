<?php

namespace App\Support;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

final class SubmissionMedia
{
    private const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    /** @var list<string> */
    private const DISKS = ['supabase', 'public'];

    public static function isImage(string $path): bool
    {
        return in_array(
            strtolower(pathinfo($path, PATHINFO_EXTENSION)),
            self::IMAGE_EXTENSIONS,
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

    /**
     * @return array{disk: string, path: string}|null
     */
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

    /** @return list<string> */
    private static function disksToTry(): array
    {
        $default = (string) config('filesystems.default', 'supabase');

        return array_values(array_unique([$default, ...self::DISKS]));
    }
}
