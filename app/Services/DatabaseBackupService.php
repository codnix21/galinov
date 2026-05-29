<?php

namespace App\Services;

use App\Support\MysqlPhpDumper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class DatabaseBackupService
{
    /** Alpine/mariadb-client ставит mariadb-dump, не mysqldump. */
    private const DUMP_BINARIES = ['mariadb-dump', 'mysqldump'];

    private const CLIENT_BINARIES = ['mariadb', 'mysql'];

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
        $this->createMysqlBackup($cfg, $path);

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
        $this->restoreMysqlBackup($cfg, $path);
    }

    public function resolvePath(string $filename): string
    {
        return $this->backupDirectory().DIRECTORY_SEPARATOR.$filename;
    }

    /** @param array<string, mixed> $cfg */
    private function createMysqlBackup(array $cfg, string $path): void
    {
        $dumpBinary = $this->findExecutable(self::DUMP_BINARIES);

        if ($dumpBinary !== null) {
            try {
                $this->createMysqlBackupViaCli($dumpBinary, $cfg, $path);

                return;
            } catch (RuntimeException $e) {
                if (!$this->shouldFallbackToPhp($e)) {
                    throw $e;
                }
            }
        }

        (new MysqlPhpDumper)->dumpToFile($path, (string) $cfg['database']);
    }

    /** @param array<string, mixed> $cfg */
    private function restoreMysqlBackup(array $cfg, string $path): void
    {
        $clientBinary = $this->findExecutable(self::CLIENT_BINARIES);

        if ($clientBinary !== null) {
            try {
                $this->restoreMysqlViaCli($clientBinary, $cfg, $path);

                return;
            } catch (RuntimeException $e) {
                if (!$this->shouldFallbackToPhp($e)) {
                    throw $e;
                }
            }
        }

        (new MysqlPhpDumper)->restoreFromFile($path);
    }

    /** @param array<string, mixed> $cfg */
    private function createMysqlBackupViaCli(string $binary, array $cfg, string $path): void
    {
        $command = [
            $binary,
            '--host='.$cfg['host'],
            '--port='.$cfg['port'],
            '--user='.$cfg['username'],
            '--single-transaction',
            '--routines',
            '--triggers',
            $cfg['database'],
        ];

        $result = Process::timeout(300)->env($this->mysqlEnv($cfg))->run($command);
        if (!$result->successful()) {
            throw new RuntimeException($binary.': '.trim($result->errorOutput() ?: $result->output()));
        }

        $output = $result->output();
        if (trim($output) === '') {
            throw new RuntimeException($binary.': пустой вывод дампа.');
        }

        File::put($path, $output);
    }

    /** @param array<string, mixed> $cfg */
    private function restoreMysqlViaCli(string $binary, array $cfg, string $path): void
    {
        $command = [
            $binary,
            '--host='.$cfg['host'],
            '--port='.$cfg['port'],
            '--user='.$cfg['username'],
            $cfg['database'],
        ];

        $sql = File::get($path);
        $result = Process::timeout(600)->env($this->mysqlEnv($cfg))->input($sql)->run($command);
        if (!$result->successful()) {
            throw new RuntimeException($binary.' restore: '.trim($result->errorOutput() ?: $result->output()));
        }
    }

    /** @param array<string, mixed> $cfg */
    private function mysqlEnv(array $cfg): array
    {
        $env = [];
        if (!empty($cfg['password'])) {
            $env['MYSQL_PWD'] = $cfg['password'];
        }

        return $env;
    }

    /** @param list<string> $names */
    private function findExecutable(array $names): ?string
    {
        foreach ($names as $name) {
            $path = $this->resolveExecutablePath($name);
            if ($path !== null && $this->binaryRespondsToVersion($path)) {
                return $path;
            }
        }

        return null;
    }

    private function resolveExecutablePath(string $name): ?string
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $result = Process::run(['where', $name]);
            if (!$result->successful()) {
                return null;
            }

            $line = trim(strtok($result->output(), "\r\n"));
            if ($line === '' || !is_file($line)) {
                return null;
            }

            return $line;
        }

        $result = Process::run(['sh', '-c', 'command -v '.escapeshellarg($name).' 2>/dev/null']);
        if (!$result->successful()) {
            return null;
        }

        $path = trim($result->output());
        if ($path === '' || !is_file($path)) {
            return null;
        }

        return $path;
    }

    private function binaryRespondsToVersion(string $path): bool
    {
        try {
            $result = Process::timeout(10)->run([$path, '--version']);

            return $result->successful();
        } catch (Throwable) {
            return false;
        }
    }

    private function shouldFallbackToPhp(RuntimeException $e): bool
    {
        $msg = strtolower($e->getMessage());

        return str_contains($msg, 'not found')
            || str_contains($msg, 'no such file')
            || str_contains($msg, 'cannot run')
            || str_contains($msg, 'failed to execute')
            || str_contains($msg, '127');
    }
}
