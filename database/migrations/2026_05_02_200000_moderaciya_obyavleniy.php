<?php

/**
 * Модерация объявлений: статус «на модерации» и причина отказа публикации.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('statusy_obyavleniy')) {
            DB::table('statusy_obyavleniy')->insertOrIgnore([
                'kod' => 'pending_review',
                'nazvanie' => 'На модерации',
                'sozdano_at' => now(),
                'obnovleno_at' => now(),
            ]);
        }

        if (Schema::hasTable('nedvizhimost') && !Schema::hasColumn('nedvizhimost', 'prichina_otkaza_mod')) {
            Schema::table('nedvizhimost', function (Blueprint $table) {
                $table->text('prichina_otkaza_mod')->nullable()->after('status_obyavleniya_id'); // видна автору при отклонении
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('nedvizhimost') && Schema::hasColumn('nedvizhimost', 'prichina_otkaza_mod')) {
            Schema::table('nedvizhimost', function (Blueprint $table) {
                $table->dropColumn('prichina_otkaza_mod');
            });
        }

        if (Schema::hasTable('statusy_obyavleniy')) {
            DB::table('statusy_obyavleniy')->where('kod', 'pending_review')->delete();
        }
    }
};
