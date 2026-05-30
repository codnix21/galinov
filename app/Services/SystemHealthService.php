<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class SystemHealthService
{
    /** @return list<array{label: string, status: string, detail: string}> */
    public static function checks(): array
    {
        $items = [];

        try {
            DB::connection()->getPdo();
            $items[] = ['label' => 'База данных', 'status' => 'ok', 'detail' => 'Подключение активно'];
        } catch (\Throwable $e) {
            $items[] = ['label' => 'База данных', 'status' => 'error', 'detail' => $e->getMessage()];
        }

        $storageWritable = is_writable(storage_path());
        $items[] = [
            'label' => 'Каталог storage',
            'status' => $storageWritable ? 'ok' : 'error',
            'detail' => $storageWritable ? 'Запись доступна' : 'Нет прав на запись',
        ];

        try {
            Storage::disk('public')->put('_health_check.txt', (string) time());
            Storage::disk('public')->delete('_health_check.txt');
            $items[] = ['label' => 'Диск public', 'status' => 'ok', 'detail' => 'Чтение и запись OK'];
        } catch (\Throwable $e) {
            $items[] = ['label' => 'Диск public', 'status' => 'error', 'detail' => $e->getMessage()];
        }

        $backupDir = storage_path('app/backups');
        $backups = File::isDirectory($backupDir)
            ? collect(File::files($backupDir))->sortByDesc(fn ($f) => $f->getMTime())->take(3)
            : collect();
        $items[] = [
            'label' => 'Резервные копии',
            'status' => $backups->isNotEmpty() ? 'ok' : 'warn',
            'detail' => $backups->isNotEmpty()
                ? 'Последний: '.$backups->first()->getFilename().' ('.date('d.m.Y H:i', $backups->first()->getMTime()).')'
                : 'Файлов бэкапа не найдено',
        ];

        $queueDriver = config('queue.default');
        $items[] = [
            'label' => 'Очередь',
            'status' => $queueDriver === 'sync' ? 'warn' : 'ok',
            'detail' => 'Драйвер: '.$queueDriver,
        ];

        $items[] = [
            'label' => 'Окружение',
            'status' => app()->environment('production') ? 'ok' : 'warn',
            'detail' => config('app.env').' · PHP '.PHP_VERSION,
        ];

        $robokassa = app(RobokassaService::class)->isConfigured();
        $items[] = [
            'label' => 'Robokassa',
            'status' => $robokassa ? 'ok' : 'warn',
            'detail' => $robokassa ? 'Настроена' : 'Не заданы ROBOKASSA_* в .env',
        ];

        return $items;
    }
}
