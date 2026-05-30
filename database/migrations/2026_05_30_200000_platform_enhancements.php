<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nastroiki_sistemy', function (Blueprint $table) {
            $table->string('klyuch', 64)->primary();
            $table->text('znachenie')->nullable();
            $table->timestamp('obnovleno_at')->nullable();
        });

        Schema::create('sohranennye_poiski', function (Blueprint $table) {
            $table->id();
            $table->foreignId('polzovatel_id')->constrained('polzovateli')->cascadeOnDelete();
            $table->string('nazvanie', 120);
            $table->json('filtry');
            $table->boolean('uvedomleniya')->default(true);
            $table->timestamp('poslednyaya_proverka_at')->nullable();
            $table->timestamps();
        });

        if (Schema::hasTable('nedvizhimost')) {
            Schema::table('nedvizhimost', function (Blueprint $table) {
                if (!Schema::hasColumn('nedvizhimost', 'tip_pomeshcheniya')) {
                    $table->string('tip_pomeshcheniya', 32)->nullable()->after('tip_doma');
                }
                if (!Schema::hasColumn('nedvizhimost', 'vysota_potolkov')) {
                    $table->decimal('vysota_potolkov', 4, 2)->nullable()->after('tip_pomeshcheniya');
                }
                if (!Schema::hasColumn('nedvizhimost', 'otdelnyy_vhod')) {
                    $table->boolean('otdelnyy_vhod')->nullable()->after('vysota_potolkov');
                }
            });
        }

        if (Schema::hasTable('roli')) {
            DB::table('roli')->insertOrIgnore([
                'kod' => 'moderator',
                'nazvanie' => 'Модератор',
                'sozdano_at' => now(),
                'obnovleno_at' => now(),
            ]);
        }

        DB::table('nastroiki_sistemy')->insertOrIgnore([
            ['klyuch' => 'inquiry_sla_hours', 'znachenie' => '24', 'obnovleno_at' => now()],
            ['klyuch' => 'contact_email', 'znachenie' => 'info@agency.local', 'obnovleno_at' => now()],
            ['klyuch' => 'agency_name', 'znachenie' => 'Агентство недвижимости', 'obnovleno_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('sohranennye_poiski');
        Schema::dropIfExists('nastroiki_sistemy');

        if (Schema::hasTable('nedvizhimost')) {
            Schema::table('nedvizhimost', function (Blueprint $table) {
                foreach (['otdelnyy_vhod', 'vysota_potolkov', 'tip_pomeshcheniya'] as $col) {
                    if (Schema::hasColumn('nedvizhimost', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('roli')) {
            DB::table('roli')->where('kod', 'moderator')->delete();
        }
    }
};
