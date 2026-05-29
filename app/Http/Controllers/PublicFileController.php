<?php

namespace App\Http\Controllers;

use App\Support\DocumentStorage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Раздача загруженных файлов из storage/app/public по URL /media/…
 * Нужен, когда симлинк public/storage не создан (частая ситуация на Windows).
 */
class PublicFileController extends Controller
{
    /**
     * Отдаёт один файл с диска public; защита от обхода каталога через «..».
     */
    public function publicDisk(string $path): BinaryFileResponse
    {
        $path = str_replace('\\', '/', $path);
        $path = ltrim($path, '/');

        if ($path === '' || str_contains($path, '..')) {
            abort(404);
        }

        $absolute = DocumentStorage::absolutePath($path);
        if ($absolute === null) {
            abort(404);
        }

        return response()->file($absolute);
    }
}
