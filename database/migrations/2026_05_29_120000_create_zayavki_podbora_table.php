<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zayavki_podbora', function (Blueprint $table) {
            $table->id();
            $table->foreignId('polzovatel_id')->nullable()->constrained('polzovateli')->nullOnDelete();
            $table->string('imya', 120);
            $table->string('telefon', 32)->nullable();
            $table->string('email', 255)->nullable();
            $table->text('kommentariy')->nullable();
            $table->json('filtry')->nullable();
            $table->string('status', 32)->default('new');
            $table->timestamp('sozdano_at')->useCurrent();
            $table->timestamp('obnovleno_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zayavki_podbora');
    }
};
