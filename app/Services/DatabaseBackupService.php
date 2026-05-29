<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use RuntimeException;

class DatabaseBackupService
{
    public function backupDirectory(): string
    {
        $dir = storage_path('app/backups');
        if (!File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        return $dir;
    }

    /** @return list<array{name: string, size: int, created_at: string}> */
    public function listBackups(): array
    {
        $files = collect(File::files($this->backupDirectory()))
            ->filter(fn ($f) => in_array($f->getExtension(), ['sql', 'sqlite'], true))
            ->sortByDesc(fn ($f) => $f->getMTime())
            ->values();

        return $files->map(fn ($f) => [
            'name' => $f->getFilename(),
            'size' => $f->getSize(),
            'created_at' => date('d.m.Y H:i:s', $f->getMTime()),
        ])->all();
    }

    public function createBackup(): string
    {
        $driver = config('database.default');
        $filename = 'backup_'.now()->format('Y-m-d_His').($driver === 'sqlite' ? '.sqlite' : '.sql');
        $path = $this->backupDirectory().DIRECTORY_SEPARATOR.$filename;

        if ($driver === 'sqlite') {
            $source = config('database.connections.sqlite.database');
            if (!is_file($source)) {
                throw new RuntimeException('Файл SQLite не найден: '.$source);
            }
            File::copy($source, $path);

            return $filename;
        }

        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            throw new RuntimeException('Резервное копирование поддерживается для SQLite и MySQL/MariaDB.');
        }

        $cfg = config('database.connections.'.$driver);
        $command = [
            'mysqldump',
            '--host='.$cfg['host'],
            '--port='.$cfg['port'],
            '--user='.$cfg['username'],
            '--single-transaction',
            '--routines',
            '--triggers',
            $cfg['database'],
        ];

        $env = [];
        if (!empty($cfg['password'])) {
            $env['MYSQL_PWD'] = $cfg['password'];
        }

        $result = Process::timeout(300)->env($env)->run($command);
        if (!$result->successful()) {
            throw new RuntimeException('mysqldump: '.trim($result->errorOutput() ?: $result->output()));
        }

        File::put($path, $result->output());

        return $filename;
    }

    public function restoreFromFile(string $filename): void
    {
        $path = $this->backupDirectory().DIRECTORY_SEPARATOR.$filename;
        if (!File::exists($path)) {
            throw new RuntimeException('Файл резервной копии не найден.');
        }

        if (!Str::startsWith(realpath($path), realpath($this->backupDirectory()))) {
            throw new RuntimeException('Недопустимый путь к файлу.');
        }

        $driver = config('database.default');

        if ($driver === 'sqlite') {
            $target = config('database.connections.sqlite.database');
            DB::disconnect();
            File::copy($path, $target);

            return;
        }

        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            throw new RuntimeException('Восстановление поддерживается для SQLite и MySQL/MariaDB.');
        }

        $cfg = config('database.connections.'.$driver);
        $command = [
            'mysql',
            '--host='.$cfg['host'],
            '--port='.$cfg['port'],
            '--user='.$cfg['username'],
            $cfg['database'],
        ];
        $env = [];
        if (!empty($cfg['password'])) {
            $env['MYSQL_PWD'] = $cfg['password'];
        }

        $sql = File::get($path);
        $result = Process::timeout(600)->env($env)->input($sql)->run($command);
        if (!$result->successful()) {
            throw new RuntimeException('mysql restore: '.trim($result->errorOutput() ?: $result->output()));
        }
    }

    public function resolvePath(string $filename): string
    {
        return $this->backupDirectory().DIRECTORY_SEPARATOR.$filename;
    }
}
