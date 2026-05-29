<?php

/**
 * Очистка 3НФ: удалить gorod, rol, status_* (строки) после синхронизации с *_id.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Применение: убрать текстовые дубли (gorod, rol, status_*) — остаются только *_id на справочники.
     */
    public function up(): void
    {
        if (Schema::hasTable('nedvizhimost')) {
            $this->syncNedvizhimostStatusFromEnumIfPresent();
            $this->syncNedvizhimostGorodIdFromStringIfPresent();
            $this->dropColumnsIfExist('nedvizhimost', ['gorod', 'status_obyavleniya']);
        }

        if (Schema::hasTable('dogovory')) {
            $this->syncDogovoryStatusFromEnumIfPresent();
            $this->dropColumnsIfExist('dogovory', ['status_dogovora']);
        }

        if (Schema::hasTable('polzovateli')) {
            $this->syncPolzovateliRolIdFromStringIfPresent();
            $this->dropColumnsIfExist('polzovateli', ['rol']);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('nedvizhimost')) {
            Schema::table('nedvizhimost', function (Blueprint $table) {
                if (!Schema::hasColumn('nedvizhimost', 'gorod')) {
                    $table->string('gorod', 255)->nullable()->after('tsena');
                }
                if (!Schema::hasColumn('nedvizhimost', 'status_obyavleniya')) {
                    $table->string('status_obyavleniya', 32)->nullable()->after('polzovatel_id');
                }
            });
        }

        if (Schema::hasTable('dogovory')) {
            Schema::table('dogovory', function (Blueprint $table) {
                if (!Schema::hasColumn('dogovory', 'status_dogovora')) {
                    $table->string('status_dogovora', 32)->nullable()->after('data_okonchaniya');
                }
            });
        }

        if (Schema::hasTable('polzovateli')) {
            Schema::table('polzovateli', function (Blueprint $table) {
                if (!Schema::hasColumn('polzovateli', 'rol')) {
                    $table->string('rol', 50)->nullable()->after('rol_id');
                }
            });
        }

        $this->restoreDenormalizedFromFk();
    }

    private function dropColumnsIfExist(string $table, array $columns): void
    {
        $toDrop = array_values(array_filter($columns, fn ($c) => Schema::hasColumn($table, $c)));
        if ($toDrop === []) {
            return;
        }
        Schema::table($table, function (Blueprint $blueprint) use ($toDrop) {
            $blueprint->dropColumn($toDrop);
        });
    }

    private function syncNedvizhimostStatusFromEnumIfPresent(): void
    {
        if (!Schema::hasColumn('nedvizhimost', 'status_obyavleniya') || !Schema::hasTable('statusy_obyavleniy')) {
            return;
        }

        $map = DB::table('statusy_obyavleniy')->pluck('id', 'kod')->all();
        $rows = DB::table('nedvizhimost')->select('id', 'status_obyavleniya', 'status_obyavleniya_id')->get();
        foreach ($rows as $row) {
            $kod = $row->status_obyavleniya ?? null;
            if ($kod === null || $kod === '') {
                continue;
            }
            $expectedId = $map[$kod] ?? null;
            if ($expectedId === null) {
                continue;
            }
            if ((int) $row->status_obyavleniya_id !== (int) $expectedId) {
                DB::table('nedvizhimost')->where('id', $row->id)->update(['status_obyavleniya_id' => $expectedId]);
            }
        }
    }

    private function syncNedvizhimostGorodIdFromStringIfPresent(): void
    {
        if (!Schema::hasColumn('nedvizhimost', 'gorod') || !Schema::hasTable('goroda')) {
            return;
        }

        $rows = DB::table('nedvizhimost')->select('id', 'gorod', 'gorod_id')->whereNotNull('gorod')->get();
        foreach ($rows as $row) {
            if (!empty($row->gorod_id)) {
                continue;
            }
            $cityId = DB::table('goroda')->where('nazvanie', $row->gorod)->value('id');
            if ($cityId) {
                DB::table('nedvizhimost')->where('id', $row->id)->update(['gorod_id' => $cityId]);
            }
        }
    }

    private function syncDogovoryStatusFromEnumIfPresent(): void
    {
        if (!Schema::hasColumn('dogovory', 'status_dogovora') || !Schema::hasTable('statusy_dogovorov')) {
            return;
        }

        $map = DB::table('statusy_dogovorov')->pluck('id', 'kod')->all();
        $rows = DB::table('dogovory')->select('id', 'status_dogovora', 'status_dogovora_id')->get();
        foreach ($rows as $row) {
            $kod = $row->status_dogovora ?? null;
            if ($kod === null || $kod === '') {
                continue;
            }
            $expectedId = $map[$kod] ?? null;
            if ($expectedId === null) {
                continue;
            }
            if ((int) $row->status_dogovora_id !== (int) $expectedId) {
                DB::table('dogovory')->where('id', $row->id)->update(['status_dogovora_id' => $expectedId]);
            }
        }
    }

    private function syncPolzovateliRolIdFromStringIfPresent(): void
    {
        if (!Schema::hasColumn('polzovateli', 'rol') || !Schema::hasTable('roli')) {
            return;
        }

        $map = DB::table('roli')->pluck('id', 'kod')->all();
        $rows = DB::table('polzovateli')->select('id', 'rol', 'rol_id')->get();
        foreach ($rows as $row) {
            $kod = $row->rol ?? null;
            if ($kod === null || $kod === '') {
                continue;
            }
            $expectedId = $map[$kod] ?? null;
            if ($expectedId === null) {
                continue;
            }
            if ((int) $row->rol_id !== (int) $expectedId) {
                DB::table('polzovateli')->where('id', $row->id)->update(['rol_id' => $expectedId]);
            }
        }
    }

    private function restoreDenormalizedFromFk(): void
    {
        if (Schema::hasTable('nedvizhimost') && Schema::hasColumn('nedvizhimost', 'gorod_id') && Schema::hasColumn('nedvizhimost', 'gorod')) {
            $rows = DB::table('nedvizhimost')->whereNotNull('gorod_id')->get();
            foreach ($rows as $row) {
                $name = DB::table('goroda')->where('id', $row->gorod_id)->value('nazvanie');
                if ($name) {
                    DB::table('nedvizhimost')->where('id', $row->id)->update(['gorod' => $name]);
                }
            }
        }

        if (Schema::hasTable('nedvizhimost') && Schema::hasColumn('nedvizhimost', 'status_obyavleniya_id') && Schema::hasColumn('nedvizhimost', 'status_obyavleniya')) {
            $map = DB::table('statusy_obyavleniy')->pluck('kod', 'id')->all();
            foreach (DB::table('nedvizhimost')->whereNotNull('status_obyavleniya_id')->get() as $row) {
                $kod = $map[$row->status_obyavleniya_id] ?? null;
                if ($kod) {
                    DB::table('nedvizhimost')->where('id', $row->id)->update(['status_obyavleniya' => $kod]);
                }
            }
        }

        if (Schema::hasTable('dogovory') && Schema::hasColumn('dogovory', 'status_dogovora_id') && Schema::hasColumn('dogovory', 'status_dogovora')) {
            $map = DB::table('statusy_dogovorov')->pluck('kod', 'id')->all();
            foreach (DB::table('dogovory')->whereNotNull('status_dogovora_id')->get() as $row) {
                $kod = $map[$row->status_dogovora_id] ?? null;
                if ($kod) {
                    DB::table('dogovory')->where('id', $row->id)->update(['status_dogovora' => $kod]);
                }
            }
        }

        if (Schema::hasTable('polzovateli') && Schema::hasColumn('polzovateli', 'rol_id') && Schema::hasColumn('polzovateli', 'rol')) {
            $map = DB::table('roli')->pluck('kod', 'id')->all();
            foreach (DB::table('polzovateli')->whereNotNull('rol_id')->get() as $row) {
                $kod = $map[$row->rol_id] ?? null;
                if ($kod) {
                    DB::table('polzovateli')->where('id', $row->id)->update(['rol' => $kod]);
                }
            }
        }
    }
};
