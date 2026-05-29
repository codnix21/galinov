<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dokumenty_proverki', function (Blueprint $table) {
            if (!Schema::hasColumn('dokumenty_proverki', 'nedvizhimost_id')) {
                $table->foreignId('nedvizhimost_id')->nullable()->after('polzovatel_id')
                    ->constrained('nedvizhimost')->nullOnDelete();
            }
            if (!Schema::hasColumn('dokumenty_proverki', 'tip_obekta')) {
                $table->string('tip_obekta', 32)->nullable()->after('tip');
            }
            if (!Schema::hasColumn('dokumenty_proverki', 'vneshniy_id')) {
                $table->string('vneshniy_id', 64)->nullable()->after('status');
            }
            if (!Schema::hasColumn('dokumenty_proverki', 'vneshniy_status')) {
                $table->string('vneshniy_status', 64)->nullable()->after('vneshniy_id');
            }
            if (!Schema::hasColumn('dokumenty_proverki', 'vneshniy_provereno_at')) {
                $table->timestamp('vneshniy_provereno_at')->nullable()->after('vneshniy_status');
            }
        });

        Schema::table('dogovory', function (Blueprint $table) {
            if (!Schema::hasColumn('dogovory', 'oplata_metod')) {
                $table->string('oplata_metod', 32)->nullable()->after('oplata_at');
            }
            if (!Schema::hasColumn('dogovory', 'oplata_tranzaktsiya')) {
                $table->string('oplata_tranzaktsiya', 64)->nullable()->after('oplata_metod');
            }
            if (!Schema::hasColumn('dogovory', 'oplata_summa')) {
                $table->decimal('oplata_summa', 14, 2)->nullable()->after('oplata_tranzaktsiya');
            }
        });
    }

    public function down(): void
    {
        Schema::table('dogovory', function (Blueprint $table) {
            foreach (['oplata_summa', 'oplata_tranzaktsiya', 'oplata_metod'] as $col) {
                if (Schema::hasColumn('dogovory', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('dokumenty_proverki', function (Blueprint $table) {
            if (Schema::hasColumn('dokumenty_proverki', 'nedvizhimost_id')) {
                $table->dropForeign(['nedvizhimost_id']);
                $table->dropColumn('nedvizhimost_id');
            }
            foreach (['tip_obekta', 'vneshniy_id', 'vneshniy_status', 'vneshniy_provereno_at'] as $col) {
                if (Schema::hasColumn('dokumenty_proverki', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
