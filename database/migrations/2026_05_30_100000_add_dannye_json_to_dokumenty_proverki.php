<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dokumenty_proverki', function (Blueprint $table) {
            $table->json('dannye_json')->nullable()->after('kommentariy_mod');
        });
    }

    public function down(): void
    {
        Schema::table('dokumenty_proverki', function (Blueprint $table) {
            $table->dropColumn('dannye_json');
        });
    }
};
