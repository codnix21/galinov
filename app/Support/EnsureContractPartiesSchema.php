<?php

namespace App\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Создаёт колонки сторон договора, если миграция ещё не была выполнена.
 */
class EnsureContractPartiesSchema
{
    private static bool $checked = false;

    public static function apply(): void
    {
        if (self::$checked || !Schema::hasTable('dogovory')) {
            return;
        }

        self::$checked = true;

        if (Schema::hasColumn('dogovory', 'vladelets_id')
            && Schema::hasColumn('dogovory', 'pokupatel_id')) {
            return;
        }

        try {
            Artisan::call('migrate', [
                '--force' => true,
                '--path' => 'database/migrations/2026_05_22_140000_contract_parties_and_approvals.php',
            ]);
        } catch (\Throwable) {
            self::applyViaSchemaBuilder();
        }

        if (!Schema::hasColumn('dogovory', 'vladelets_id')) {
            self::applyViaSchemaBuilder();
        }

        self::backfill();
    }

    private static function applyViaSchemaBuilder(): void
    {
        Schema::table('dogovory', function (Blueprint $table) {
            if (!Schema::hasColumn('dogovory', 'vladelets_id')) {
                $table->unsignedBigInteger('vladelets_id')->nullable()->after('nedvizhimost_id');
            }
            if (!Schema::hasColumn('dogovory', 'pokupatel_id')) {
                $table->unsignedBigInteger('pokupatel_id')->nullable()->after('vladelets_id');
            }
            if (!Schema::hasColumn('dogovory', 'sozdal_kak')) {
                $table->string('sozdal_kak', 20)->nullable()->after('rieltor_id');
            }
            if (!Schema::hasColumn('dogovory', 'sozdal_storona')) {
                $table->string('sozdal_storona', 20)->nullable()->after('sozdal_kak');
            }
            if (!Schema::hasColumn('dogovory', 'podtverzhden_vladelets_at')) {
                $table->timestamp('podtverzhden_vladelets_at')->nullable();
            }
            if (!Schema::hasColumn('dogovory', 'podtverzhden_pokupatel_at')) {
                $table->timestamp('podtverzhden_pokupatel_at')->nullable();
            }
            if (!Schema::hasColumn('dogovory', 'podtverzhden_rieltor_at')) {
                $table->timestamp('podtverzhden_rieltor_at')->nullable();
            }
        });
    }

    private static function backfill(): void
    {
        $contracts = DB::table('dogovory')->get();
        foreach ($contracts as $row) {
            $vladeletsId = $row->vladelets_id ?? null;
            $pokupatelId = $row->pokupatel_id ?? null;

            if (!$vladeletsId && $row->nedvizhimost_id) {
                $vladeletsId = DB::table('nedvizhimost')->where('id', $row->nedvizhimost_id)->value('polzovatel_id');
            }
            if (!$pokupatelId && !empty($row->klient_id)) {
                $pokupatelId = $row->klient_id;
            }

            $updates = array_filter([
                'vladelets_id' => $vladeletsId,
                'pokupatel_id' => $pokupatelId,
                'sozdal_kak' => $row->sozdal_kak ?? 'client',
            ], fn ($v) => $v !== null && $v !== '');

            if ($updates !== []) {
                DB::table('dogovory')->where('id', $row->id)->update($updates);
            }
        }
    }
}
