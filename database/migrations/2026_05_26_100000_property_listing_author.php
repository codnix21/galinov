<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nedvizhimost', function (Blueprint $table) {
            if (!Schema::hasColumn('nedvizhimost', 'rieltor_id')) {
                $table->foreignId('rieltor_id')->nullable()->after('polzovatel_id')
                    ->constrained('polzovateli')->nullOnDelete();
            }
            if (!Schema::hasColumn('nedvizhimost', 'sozdal_kak')) {
                $table->string('sozdal_kak', 32)->nullable()->after('rieltor_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('nedvizhimost', function (Blueprint $table) {
            if (Schema::hasColumn('nedvizhimost', 'rieltor_id')) {
                $table->dropForeign(['rieltor_id']);
                $table->dropColumn('rieltor_id');
            }
            if (Schema::hasColumn('nedvizhimost', 'sozdal_kak')) {
                $table->dropColumn('sozdal_kak');
            }
        });
    }
};
