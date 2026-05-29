<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personalnye_dannye', function (Blueprint $table) {
            $table->id();
            $table->foreignId('polzovatel_id')
                ->unique()
                ->constrained('polzovateli')
                ->cascadeOnDelete();

            // Все значения шифруются на уровне приложения (encrypted cast)
            $table->text('pasport_seriya_nomer')->nullable();
            $table->text('pasport_kem_vydan')->nullable();
            $table->date('pasport_data_vydachi')->nullable();
            $table->text('inn')->nullable();
            $table->text('snils')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personalnye_dannye');
    }
};

