<?php

namespace App\Support;

/**
 * Публичные ссылки на файлы с диска storage без симлинка public/storage.
 */
final class PublicDisk
{
    /**
     * URL для файла на disk «public» через маршрут /media/… (не зависит от симлинка public/storage).
     */
    public static function publicUrl(?string $raw): string
    {
        if ($raw === null || $raw === '') {
            return '';
        }

        $path = str_replace('\\', '/', trim((string) $raw));

        if (preg_match('#^https?://#i', $path)) {
            return $path;
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
            return '';
        }

        return route('media.public', ['path' => $path], false);
    }
}
