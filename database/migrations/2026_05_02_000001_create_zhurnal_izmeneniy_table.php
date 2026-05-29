<?php

/**
 * Журнал изменений: кто, какой объект, какое действие (аудит для админки).
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zhurnal_izmeneniy', function (Blueprint $table) {
            $table->id();
            $table->foreignId('polzovatel_id')->nullable()->constrained('polzovateli')->nullOnDelete();
            $table->string('obyekt_type', 255); // класс модели (Property, Contract…)
            $table->unsignedBigInteger('obyekt_id');
            $table->string('deystvie', 64); // created, updated, deleted и т.д.
            $table->json('detalizatsiya')->nullable(); // старые/новые значения
            $table->text('kommentariy')->nullable();
            $table->timestamp('sozdano_at')->useCurrent();

            $table->index(['obyekt_type', 'obyekt_id']);
            $table->index('sozdano_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zhurnal_izmeneniy');
    }
};
