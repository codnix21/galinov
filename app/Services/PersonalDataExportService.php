<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\Favorite;
use App\Models\Property;
use App\Models\SavedSearch;
use App\Models\User;
use App\Models\UserPersonalData;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use ZipArchive;

class PersonalDataExportService
{
    public static function buildJson(User $user): array
    {
        $user->loadMissing(['roleRelation']);

        $personal = UserPersonalData::where('polzovatel_id', $user->id)->first();

        $properties = Property::where('polzovatel_id', $user->id)->get(['id', 'nazvanie', 'tip', 'tsena', 'adres_ulitsy', 'sozdano_at']);
        $contracts = Contract::query()
            ->where(fn ($q) => $q->where('vladelets_id', $user->id)->orWhere('pokupatel_id', $user->id))
            ->get(['id', 'tip', 'tsena', 'data_nachala', 'status_dogovora_id']);

        return [
            'exported_at' => now()->toIso8601String(),
            'legal_basis' => '152-ФЗ — запрос субъекта персональных данных',
            'profile' => [
                'id' => $user->id,
                'familia' => $user->familia,
                'imya' => $user->imya,
                'otchestvo' => $user->otchestvo,
                'email' => $user->email_polzovatela,
                'telefon' => $user->telefon,
                'rol' => $user->role,
                'sozdano_at' => $user->sozdano_at?->toIso8601String(),
            ],
            'personal_data' => $personal ? [
                'has_passport' => (bool) $personal->pasport_seriya_nomer,
                'has_inn' => (bool) $personal->inn,
                'has_snils' => (bool) $personal->snils,
                'pasport_data_vydachi' => $personal->pasport_data_vydachi?->format('Y-m-d'),
            ] : null,
            'properties' => $properties->toArray(),
            'contracts' => $contracts->toArray(),
            'favorites_count' => Favorite::where('polzovatel_id', $user->id)->count(),
            'saved_searches_count' => SavedSearch::where('polzovatel_id', $user->id)->count(),
        ];
    }

    public static function storeZip(User $user): string
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('Расширение PHP zip не установлено на сервере.');
        }

        $json = json_encode(self::buildJson($user), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        if ($json === false) {
            throw new RuntimeException('Не удалось сформировать JSON выгрузки.');
        }

        $dir = 'exports/pd/'.$user->id;
        Storage::disk('local')->makeDirectory($dir);

        $stamp = now()->format('Ymd_His');
        $zipName = 'pd_export_'.$user->id.'_'.$stamp.'.zip';
        $zipPath = Storage::disk('local')->path($dir.'/'.$zipName);

        $parent = dirname($zipPath);
        if (! is_dir($parent) && ! mkdir($parent, 0775, true) && ! is_dir($parent)) {
            throw new RuntimeException('Не удалось создать каталог для выгрузки.');
        }

        $zip = new ZipArchive;
        $openResult = $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        if ($openResult !== true) {
            throw new RuntimeException('Не удалось создать ZIP-архив (код '.$openResult.').');
        }

        if (! $zip->addFromString('personal_data.json', $json)) {
            $zip->close();
            @unlink($zipPath);
            throw new RuntimeException('Не удалось записать данные в архив.');
        }

        if (! $zip->close()) {
            @unlink($zipPath);
            throw new RuntimeException('Не удалось завершить запись архива.');
        }

        if (! is_file($zipPath) || filesize($zipPath) === 0) {
            throw new RuntimeException('Файл выгрузки не был создан на диске.');
        }

        return $zipPath;
    }
}
