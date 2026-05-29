<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Поиск файла документа на диске public (с учётом симлинка public/storage).
 */
final class DocumentStorage
{
    public static function resolveRelativePath(?string $raw): ?string
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        $path = str_replace('\\', '/', trim((string) $raw));
        if (preg_match('#^https?://#i', $path)) {
            return null;
        }

        $path = ltrim($path, '/');
        while (str_starts_with($path, 'storage/')) {
            $path = substr($path, strlen('storage/'));
        }
        if (str_starts_with($path, 'public/')) {
            $path = substr($path, strlen('public/'));
        }
        $path = ltrim($path, '/');

        if ($path === '' || str_contains($path, '..')) {
            return null;
        }

        $disk = Storage::disk('public');
        if ($disk->exists($path)) {
            return $path;
        }

        if (is_file(public_path('storage/' . $path))) {
            return $path;
        }

        return null;
    }

    public static function absolutePath(?string $raw): ?string
    {
        $relative = self::resolveRelativePath($raw);
        if ($relative === null) {
            return null;
        }

        $viaDisk = Storage::disk('public')->path($relative);
        if (is_file($viaDisk)) {
            return $viaDisk;
        }

        $viaSymlink = public_path('storage/' . $relative);

        return is_file($viaSymlink) ? $viaSymlink : null;
    }

    public static function isJsonRegistryFile(?string $raw): bool
    {
        if ($raw === null || $raw === '') {
            return false;
        }

        return str_ends_with(strtolower((string) $raw), '.json');
    }
}
