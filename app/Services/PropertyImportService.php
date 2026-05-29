<?php

namespace App\Services;

use App\Models\City;
use App\Models\Property;
use App\Models\PropertyStatus;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;

class PropertyImportService
{
    /** @return array{ok: bool, imported: int, skipped: int, errors: list<string>} */
    public function import(UploadedFile $file, User $defaultOwner): array
    {
        $path = $file->getRealPath();
        $ext = strtolower($file->getClientOriginalExtension());

        $rows = match ($ext) {
            'csv', 'txt' => $this->readCsv($path),
            'xlsx' => $this->readXlsx($path),
            default => throw new \InvalidArgumentException('Поддерживаются файлы CSV и XLSX.'),
        };

        if ($rows === []) {
            throw new \InvalidArgumentException('Файл пуст или не содержит данных.');
        }

        $header = array_map(fn ($h) => $this->normalizeHeader((string) $h), array_shift($rows));
        $required = ['nazvanie', 'tsena'];
        foreach ($required as $col) {
            if (!in_array($col, $header, true)) {
                throw new \InvalidArgumentException('В файле должны быть колонки: nazvanie, tsena (и опционально gorod, adres, tip, operatsiya, status_kod, email_vladelca).');
            }
        }

        $imported = 0;
        $skipped = 0;
        $errors = [];

        foreach ($rows as $lineNum => $row) {
            $data = $this->rowToAssoc($header, $row);
            if ($this->rowEmpty($data)) {
                continue;
            }

            $line = $lineNum + 2;
            try {
                $this->importRow($data, $defaultOwner);
                $imported++;
            } catch (\Throwable $e) {
                $skipped++;
                $errors[] = "Строка {$line}: ".$e->getMessage();
            }
        }

        return [
            'ok' => $imported > 0,
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => array_slice($errors, 0, 20),
        ];
    }

    private function importRow(array $data, User $defaultOwner): void
    {
        $title = trim((string) ($data['nazvanie'] ?? ''));
        $price = (float) str_replace([' ', ','], ['', '.'], (string) ($data['tsena'] ?? '0'));
        if ($title === '' || $price <= 0) {
            throw new \InvalidArgumentException('Некорректные nazvanie или tsena.');
        }

        $owner = $defaultOwner;
        if (!empty($data['email_vladelca'])) {
            $found = User::where('email_polzovatela', strtolower(trim($data['email_vladelca'])))->first();
            if ($found) {
                $owner = $found;
            }
        }

        $cityName = trim((string) ($data['gorod'] ?? ''));
        $cityId = null;
        if ($cityName !== '') {
            $city = City::firstOrCreate(['nazvanie' => $cityName]);
            $cityId = $city->id;
        }

        $statusKod = trim((string) ($data['status_kod'] ?? 'draft'));
        $statusId = PropertyStatus::idFor($statusKod) ?? PropertyStatus::idFor('draft');

        $tip = in_array($data['tip'] ?? '', ['apartment', 'house', 'commercial', 'land'], true)
            ? $data['tip'] : 'apartment';
        $operatsiya = in_array($data['operatsiya'] ?? '', ['sale', 'rent'], true)
            ? $data['operatsiya'] : 'sale';

        DB::transaction(function () use ($title, $price, $owner, $cityId, $statusId, $tip, $operatsiya, $data) {
            Property::create([
                'polzovatel_id' => $owner->id,
                'nazvanie' => $title,
                'opisanie' => trim((string) ($data['opisanie'] ?? '')) ?: null,
                'tsena' => $price,
                'gorod_id' => $cityId,
                'adres_ulitsy' => trim((string) ($data['adres'] ?? '')) ?: null,
                'tip' => $tip,
                'operatsiya' => $operatsiya,
                'status_obyavleniya_id' => $statusId,
            ]);
        });
    }

    private function normalizeHeader(string $h): string
    {
        return strtolower(trim(preg_replace('/\s+/', '_', $h)));
    }

    /** @return list<list<string>> */
    private function readCsv(string $path): array
    {
        $reader = new CsvReader;
        $reader->open($path);
        $rows = [];
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $rows[] = array_map(fn ($c) => (string) $c->getValue(), $row->getCells());
            }
        }
        $reader->close();

        return $rows;
    }

    /** @return list<list<string>> */
    private function readXlsx(string $path): array
    {
        $reader = new XlsxReader;
        $reader->open($path);
        $rows = [];
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $rows[] = array_map(fn ($c) => (string) $c->getValue(), $row->getCells());
            }
        }
        $reader->close();

        return $rows;
    }

    /** @param list<string> $header @param list<string> $row @return array<string, string> */
    private function rowToAssoc(array $header, array $row): array
    {
        $out = [];
        foreach ($header as $i => $key) {
            $out[$key] = trim((string) ($row[$i] ?? ''));
        }

        return $out;
    }

    private function rowEmpty(array $data): bool
    {
        return trim((string) ($data['nazvanie'] ?? '')) === '';
    }
}
