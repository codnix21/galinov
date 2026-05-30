<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('otzyvy_sdelok', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dogovor_id')->constrained('dogovory')->cascadeOnDelete();
            $table->foreignId('polzovatel_id')->constrained('polzovateli')->cascadeOnDelete();
            $table->unsignedTinyInteger('ocenka');
            $table->text('tekst')->nullable();
            $table->timestamps();
            $table->unique(['dogovor_id', 'polzovatel_id']);
        });

        Schema::create('arenda_platezhi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dogovor_id')->constrained('dogovory')->cascadeOnDelete();
            $table->date('data_platezha');
            $table->decimal('summa', 12, 2);
            $table->string('status', 32)->default('pending');
            $table->timestamp('oplacheno_at')->nullable();
            $table->unsignedSmallInteger('poryadok')->default(0);
            $table->timestamps();
        });

        Schema::create('shablony_otvetov', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rieltor_id')->nullable()->constrained('polzovateli')->nullOnDelete();
            $table->string('kod', 64);
            $table->string('nazvanie', 120);
            $table->text('tekst');
            $table->string('kontekst', 32)->default('info');
            $table->timestamps();
        });

        Schema::create('shablony_dogovorov', function (Blueprint $table) {
            $table->id();
            $table->string('kod', 64)->unique();
            $table->string('nazvanie', 120);
            $table->string('tip_dogovora', 16)->default('sale');
            $table->text('vvedenie')->nullable();
            $table->text('predmet')->nullable();
            $table->text('obyazannosti')->nullable();
            $table->text('zaklyuchenie')->nullable();
            $table->boolean('aktiven')->default(true);
            $table->timestamps();
        });

        foreach (['zayavki_obekta', 'zayavki_podbora'] as $tableName) {
            if (Schema::hasTable($tableName) && !Schema::hasColumn($tableName, 'naznachen_rieltor_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->foreignId('naznachen_rieltor_id')->nullable()->after('polzovatel_id')
                        ->constrained('polzovateli')->nullOnDelete();
                });
            }
        }

        if (Schema::hasTable('shablony_dogovorov') && \DB::table('shablony_dogovorov')->count() === 0) {
            \DB::table('shablony_dogovorov')->insert([
                [
                    'kod' => 'sale_default',
                    'nazvanie' => 'Купля-продажа (стандарт)',
                    'tip_dogovora' => 'sale',
                    'vvedenie' => 'Настоящий договор заключён между сторонами в соответствии с законодательством РФ.',
                    'predmet' => 'Продавец обязуется передать в собственность Покупателю объект недвижимости, указанный в приложении.',
                    'obyazannosti' => 'Стороны обязуются исполнить обязательства по расчётам и государственной регистрации перехода права.',
                    'zaklyuchenie' => 'Договор составлен в электронной форме с применением усиленной подписи.',
                    'aktiven' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'kod' => 'rent_default',
                    'nazvanie' => 'Аренда (стандарт)',
                    'tip_dogovora' => 'rent',
                    'vvedenie' => 'Договор аренды недвижимости между Арендодателем и Арендатором.',
                    'predmet' => 'Арендодатель предоставляет Арендатору объект во временное пользование.',
                    'obyazannosti' => 'Арендатор своевременно вносит арендную плату согласно графику платежей.',
                    'zaklyuchenie' => 'Споры разрешаются в порядке, установленном законом.',
                    'aktiven' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }
    }

    public function down(): void
    {
        foreach (['zayavki_obekta', 'zayavki_podbora'] as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'naznachen_rieltor_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropConstrainedForeignId('naznachen_rieltor_id');
                });
            }
        }

        Schema::dropIfExists('shablony_dogovorov');
        Schema::dropIfExists('shablony_otvetov');
        Schema::dropIfExists('arenda_platezhi');
        Schema::dropIfExists('otzyvy_sdelok');
    }
};
