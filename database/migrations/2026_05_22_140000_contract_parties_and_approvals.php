<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('dogovory')) {
            return;
        }

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

        $this->backfillParties();

        if (Schema::hasColumn('dogovory', 'klient_id')) {
            DB::statement('ALTER TABLE dogovory MODIFY COLUMN klient_id BIGINT UNSIGNED NULL');
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('dogovory')) {
            return;
        }

        Schema::table('dogovory', function (Blueprint $table) {
            foreach ([
                'vladelets_id',
                'pokupatel_id',
                'sozdal_kak',
                'sozdal_storona',
                'podtverzhden_vladelets_at',
                'podtverzhden_pokupatel_at',
                'podtverzhden_rieltor_at',
            ] as $col) {
                if (Schema::hasColumn('dogovory', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }

    private function backfillParties(): void
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
            if ($vladeletsId && $pokupatelId && (int) $vladeletsId === (int) $pokupatelId) {
                $pokupatelId = null;
            }

            $sozdalKak = $row->sozdal_kak ?? null;
            if (!$sozdalKak) {
                $sozdalKak = ($row->ozhidaet_podtverzhdeniya ?? '') === 'client' ? 'realtor' : 'client';
                if (($row->ozhidaet_podtverzhdeniya ?? '') === 'realtor') {
                    $sozdalKak = 'client';
                }
            }

            $updates = array_filter([
                'vladelets_id' => $vladeletsId,
                'pokupatel_id' => $pokupatelId,
                'sozdal_kak' => $sozdalKak,
            ], fn ($v) => $v !== null);

            if ($updates !== []) {
                DB::table('dogovory')->where('id', $row->id)->update($updates);
            }
        }
    }
};
