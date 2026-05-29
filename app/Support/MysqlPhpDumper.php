<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Резервное копирование MySQL/MariaDB без mysqldump (чистый PHP).
 */
class MysqlPhpDumper
{
    public function dumpToFile(string $path, string $database): void
    {
        $lines = [
            '-- Резервная копия Galinov (PHP)',
            '-- База: '.$database,
            '-- Дата: '.now()->toDateTimeString(),
            '',
            'SET NAMES utf8mb4;',
            'SET FOREIGN_KEY_CHECKS=0;',
            '',
        ];

        foreach ($this->baseTables($database) as $table) {
            $escaped = $this->escapeIdentifier($table);
            $create = DB::selectOne('SHOW CREATE TABLE '.$escaped);
            $ddl = $create->{'Create Table'} ?? null;
            if ($ddl === null) {
                continue;
            }

            $lines[] = 'DROP TABLE IF EXISTS '.$escaped.';';
            $lines[] = $ddl.';';
            $lines[] = '';

            foreach (DB::table($table)->cursor() as $row) {
                $lines[] = $this->buildInsert($table, (array) $row);
            }

            $lines[] = '';
        }

        $lines[] = 'SET FOREIGN_KEY_CHECKS=1;';
        $lines[] = '';

        file_put_contents($path, implode("\n", $lines));
    }

    public function restoreFromFile(string $path): void
    {
        $sql = file_get_contents($path);
        if ($sql === false || $sql === '') {
            throw new RuntimeException('Пустой файл резервной копии.');
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            foreach ($this->splitStatements($sql) as $statement) {
                DB::unprepared($statement);
            }
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    /** @return list<string> */
    private function baseTables(string $database): array
    {
        $key = 'Tables_in_'.$database;
        $tables = [];

        foreach (DB::select('SHOW FULL TABLES') as $row) {
            $arr = (array) $row;
            $name = $arr[$key] ?? reset($arr);
            $type = $arr['Table_type'] ?? 'BASE TABLE';
            if ($name && $type === 'BASE TABLE') {
                $tables[] = $name;
            }
        }

        sort($tables);

        return $tables;
    }

    private function escapeIdentifier(string $name): string
    {
        return '`'.str_replace('`', '``', $name).'`';
    }

    /** @param array<string, mixed> $row */
    private function buildInsert(string $table, array $row): string
    {
        $columns = array_keys($row);
        $escapedTable = $this->escapeIdentifier($table);
        $columnList = implode(', ', array_map(fn ($c) => $this->escapeIdentifier($c), $columns));
        $values = implode(', ', array_map(fn ($v) => $this->quoteValue($v), array_values($row)));

        return 'INSERT INTO '.$escapedTable.' ('.$columnList.') VALUES ('.$values.');';
    }

    private function quoteValue(mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if ($value instanceof \DateTimeInterface) {
            return DB::getPdo()->quote($value->format('Y-m-d H:i:s'));
        }

        return DB::getPdo()->quote((string) $value);
    }

    /** @return list<string> */
    private function splitStatements(string $sql): array
    {
        $statements = [];
        $buffer = '';

        foreach (preg_split("/\r\n|\n|\r/", $sql) as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '--')) {
                continue;
            }

            $buffer .= $line."\n";

            if (str_ends_with(rtrim($line), ';')) {
                $statement = trim($buffer);
                $buffer = '';
                if ($statement !== '') {
                    $statements[] = $statement;
                }
            }
        }

        $tail = trim($buffer);
        if ($tail !== '') {
            $statements[] = $tail;
        }

        return $statements;
    }
}
