<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dogovory', function (Blueprint $table) {
            if (!Schema::hasColumn('dogovory', 'oplata_status')) {
                $table->string('oplata_status', 32)->default('none')->after('primechaniya');
            }
            if (!Schema::hasColumn('dogovory', 'oplata_at')) {
                $table->timestamp('oplata_at')->nullable()->after('oplata_status');
            }
            if (!Schema::hasColumn('dogovory', 'avto_zapolnen')) {
                $table->boolean('avto_zapolnen')->default(false)->after('oplata_at');
            }
        });

        Schema::create('dokumenty_proverki', function (Blueprint $table) {
            $table->id();
            $table->foreignId('polzovatel_id')->constrained('polzovateli')->cascadeOnDelete();
            $table->string('tip', 32);
            $table->string('nazvanie', 255)->nullable();
            $table->string('put_fajla', 500);
            $table->string('status', 32)->default('pending');
            $table->text('kommentariy_mod')->nullable();
            $table->timestamp('provereno_at')->nullable();
            $table->timestamp('sozdano_at')->useCurrent();
            $table->timestamp('obnovleno_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dokumenty_proverki');

        Schema::table('dogovory', function (Blueprint $table) {
            foreach (['avto_zapolnen', 'oplata_at', 'oplata_status'] as $col) {
                if (Schema::hasColumn('dogovory', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
