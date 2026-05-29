<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('polzovateli', function (Blueprint $table) {
            if (!Schema::hasColumn('polzovateli', 'pol')) {
                $table->string('pol', 16)->nullable()->after('telefon');
            }
        });
    }

    public function down(): void
    {
        Schema::table('polzovateli', function (Blueprint $table) {
            if (Schema::hasColumn('polzovateli', 'pol')) {
                $table->dropColumn('pol');
            }
        });
    }
};
