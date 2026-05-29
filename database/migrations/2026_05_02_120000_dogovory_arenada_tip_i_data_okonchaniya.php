<?php

/**
 * Договоры: снова тип rent (аренда) и дата окончания data_okonchaniya.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('dogovory')) {
            DB::statement("ALTER TABLE dogovory MODIFY COLUMN tip ENUM('sale', 'rent') NOT NULL");

            if (!Schema::hasColumn('dogovory', 'data_okonchaniya')) {
                Schema::table('dogovory', function (Blueprint $table) {
                    $table->date('data_okonchaniya')->nullable()->after('data_nachala'); // для аренды
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('dogovory') && Schema::hasColumn('dogovory', 'data_okonchaniya')) {
            Schema::table('dogovory', function (Blueprint $table) {
                $table->dropColumn('data_okonchaniya');
            });
        }

        if (Schema::hasTable('dogovory')) {
            DB::statement("ALTER TABLE dogovory MODIFY COLUMN tip ENUM('sale') NOT NULL");
        }
    }
};
