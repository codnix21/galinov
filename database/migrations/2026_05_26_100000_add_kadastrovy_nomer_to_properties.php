<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nedvizhimost', function (Blueprint $table) {
            if (!Schema::hasColumn('nedvizhimost', 'kadastrovy_nomer')) {
                $table->string('kadastrovy_nomer', 64)->nullable()->after('adres_ulitsy');
            }
        });
    }

    public function down(): void
    {
        Schema::table('nedvizhimost', function (Blueprint $table) {
            if (Schema::hasColumn('nedvizhimost', 'kadastrovy_nomer')) {
                $table->dropColumn('kadastrovy_nomer');
            }
        });
    }
};
