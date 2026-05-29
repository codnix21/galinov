<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dogovory', function (Blueprint $table) {
            foreach (['vladelets', 'pokupatel', 'rieltor'] as $party) {
                $at = 'ecp_podpis_' . $party . '_at';
                $nom = 'ecp_podpis_' . $party . '_nomera';
                $fio = 'ecp_podpis_' . $party . '_fio';
                if (!Schema::hasColumn('dogovory', $at)) {
                    $table->timestamp($at)->nullable();
                }
                if (!Schema::hasColumn('dogovory', $nom)) {
                    $table->string($nom, 64)->nullable();
                }
                if (!Schema::hasColumn('dogovory', $fio)) {
                    $table->string($fio, 255)->nullable();
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('dogovory', function (Blueprint $table) {
            foreach (['vladelets', 'pokupatel', 'rieltor'] as $party) {
                foreach (['_at', '_nomera', '_fio'] as $suf) {
                    $col = 'ecp_podpis_' . $party . $suf;
                    if (Schema::hasColumn('dogovory', $col)) {
                        $table->dropColumn($col);
                    }
                }
            }
        });
    }
};
