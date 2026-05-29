<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zaprosy_dop_informacii', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nedvizhimost_id')->constrained('nedvizhimost')->cascadeOnDelete();
            $table->foreignId('polzovatel_id')->constrained('polzovateli')->cascadeOnDelete();
            $table->string('tip', 32);
            $table->string('status', 32)->default('open');
            $table->timestamp('sozdano_at')->useCurrent();
            $table->timestamp('obnovleno_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('zaprosy_dop_soobshcheniya', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zapros_id')->constrained('zaprosy_dop_informacii')->cascadeOnDelete();
            $table->foreignId('polzovatel_id')->constrained('polzovateli')->cascadeOnDelete();
            $table->string('ot_kogo', 16);
            $table->text('tekst');
            $table->timestamp('sozdano_at')->useCurrent();
        });

        if (Schema::hasTable('zayavki_podbora') && !Schema::hasColumn('zayavki_podbora', 'istochnik')) {
            Schema::table('zayavki_podbora', function (Blueprint $table) {
                $table->string('istochnik', 32)->default('catalog')->after('status');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('zaprosy_dop_soobshcheniya');
        Schema::dropIfExists('zaprosy_dop_informacii');

        if (Schema::hasColumn('zayavki_podbora', 'istochnik')) {
            Schema::table('zayavki_podbora', function (Blueprint $table) {
                $table->dropColumn('istochnik');
            });
        }
    }
};
