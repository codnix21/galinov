<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nedvizhimost', function (Blueprint $table) {
            $table->string('tip_doma', 32)->nullable()->after('tip');
            $table->boolean('est_tsokol')->nullable()->after('vsego_etazhey');
            $table->decimal('ploshchad_uchastka', 10, 2)->nullable()->comment('сотки')->after('est_tsokol');
            $table->boolean('garazh')->nullable()->after('ploshchad_uchastka');
            $table->boolean('parking')->nullable()->after('garazh');
            $table->boolean('internet')->nullable()->after('parking');
            $table->boolean('otoplenie')->nullable()->after('internet');
            $table->boolean('kanalizatsiya')->nullable()->after('otoplenie');
            $table->boolean('vodosnabzhenie')->nullable()->after('kanalizatsiya');
            $table->boolean('gaz')->nullable()->after('vodosnabzhenie');
            $table->boolean('banya')->nullable()->after('gaz');
            $table->boolean('bassein')->nullable()->after('banya');
            $table->boolean('okhrana')->nullable()->after('bassein');
            $table->boolean('zabor')->nullable()->after('okhrana');
        });
    }

    public function down(): void
    {
        Schema::table('nedvizhimost', function (Blueprint $table) {
            $table->dropColumn([
                'tip_doma',
                'est_tsokol',
                'ploshchad_uchastka',
                'garazh',
                'parking',
                'internet',
                'otoplenie',
                'kanalizatsiya',
                'vodosnabzhenie',
                'gaz',
                'banya',
                'bassein',
                'okhrana',
                'zabor',
            ]);
        });
    }
};
