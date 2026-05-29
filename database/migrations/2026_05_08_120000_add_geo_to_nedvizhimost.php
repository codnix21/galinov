<?php

/**
 * Координаты объекта на карте (из DaData при вводе адреса).
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('nedvizhimost')) {
            return;
        }
        Schema::table('nedvizhimost', function (Blueprint $table) {
            if (! Schema::hasColumn('nedvizhimost', 'geo_shirota')) {
                $table->decimal('geo_shirota', 10, 7)->nullable(); // широта
            }
            if (! Schema::hasColumn('nedvizhimost', 'geo_dolgota')) {
                $table->decimal('geo_dolgota', 11, 7)->nullable(); // долгота
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('nedvizhimost')) {
            return;
        }
        Schema::table('nedvizhimost', function (Blueprint $table) {
            if (Schema::hasColumn('nedvizhimost', 'geo_dolgota')) {
                $table->dropColumn('geo_dolgota');
            }
            if (Schema::hasColumn('nedvizhimost', 'geo_shirota')) {
                $table->dropColumn('geo_shirota');
            }
        });
    }
};
