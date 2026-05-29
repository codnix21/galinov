<?php

/**
 * Путь к скану подписанного договора (PDF/изображение).
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('dogovory') && !Schema::hasColumn('dogovory', 'skan_dogovora')) {
            Schema::table('dogovory', function (Blueprint $table) {
                $table->string('skan_dogovora', 512)->nullable()->after('primechaniya'); // файл в storage
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('dogovory') && Schema::hasColumn('dogovory', 'skan_dogovora')) {
            Schema::table('dogovory', function (Blueprint $table) {
                $table->dropColumn('skan_dogovora');
            });
        }
    }
};
