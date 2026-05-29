<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('dogovory') || Schema::hasColumn('dogovory', 'ozhidaet_podtverzhdeniya')) {
            return;
        }

        Schema::table('dogovory', function (Blueprint $table) {
            $table->string('ozhidaet_podtverzhdeniya', 20)->nullable()->after('status_dogovora_id');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('dogovory') && Schema::hasColumn('dogovory', 'ozhidaet_podtverzhdeniya')) {
            Schema::table('dogovory', function (Blueprint $table) {
                $table->dropColumn('ozhidaet_podtverzhdeniya');
            });
        }
    }
};
