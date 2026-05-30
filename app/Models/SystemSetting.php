<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    public $incrementing = false;

    protected $table = 'nastroiki_sistemy';

    protected $primaryKey = 'klyuch';

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = ['klyuch', 'znachenie', 'obnovleno_at'];

    public static function get(string $key, ?string $default = null): ?string
    {
        return Cache::remember('system_setting_'.$key, 300, function () use ($key, $default) {
            $row = self::query()->find($key);

            return $row?->znachenie ?? $default;
        });
    }

    public static function set(string $key, ?string $value): void
    {
        self::query()->updateOrInsert(
            ['klyuch' => $key],
            ['znachenie' => $value, 'obnovleno_at' => now()]
        );
        Cache::forget('system_setting_'.$key);
    }
}
