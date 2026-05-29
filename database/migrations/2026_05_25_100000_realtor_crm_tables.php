<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('klienty_rieltora', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rieltor_id')->constrained('polzovateli')->cascadeOnDelete();
            $table->foreignId('klient_id')->constrained('polzovateli')->cascadeOnDelete();
            $table->string('status', 32)->default('new');
            $table->text('zametki')->nullable();
            $table->timestamp('sozdano_at')->useCurrent();
            $table->timestamp('obnovleno_at')->useCurrent()->useCurrentOnUpdate();
            $table->unique(['rieltor_id', 'klient_id']);
        });

        Schema::create('zadachi_rieltora', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rieltor_id')->constrained('polzovateli')->cascadeOnDelete();
            $table->foreignId('klient_id')->nullable()->constrained('polzovateli')->nullOnDelete();
            $table->foreignId('nedvizhimost_id')->nullable()->constrained('nedvizhimost')->nullOnDelete();
            $table->string('nazvanie', 255);
            $table->text('opisanie')->nullable();
            $table->string('tip', 32)->default('other');
            $table->timestamp('srok_do')->nullable();
            $table->timestamp('vypolneno_at')->nullable();
            $table->timestamp('sozdano_at')->useCurrent();
            $table->timestamp('obnovleno_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('pokazy_nedvizhimosti', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rieltor_id')->constrained('polzovateli')->cascadeOnDelete();
            $table->foreignId('klient_id')->constrained('polzovateli')->cascadeOnDelete();
            $table->foreignId('nedvizhimost_id')->constrained('nedvizhimost')->cascadeOnDelete();
            $table->timestamp('naznacheno_na');
            $table->string('rezultat', 32)->nullable();
            $table->text('zametki')->nullable();
            $table->timestamp('sozdano_at')->useCurrent();
            $table->timestamp('obnovleno_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('podborki', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rieltor_id')->constrained('polzovateli')->cascadeOnDelete();
            $table->foreignId('klient_id')->nullable()->constrained('polzovateli')->nullOnDelete();
            $table->string('nazvanie', 255);
            $table->string('token', 64)->unique();
            $table->text('kommentariy')->nullable();
            $table->boolean('aktivna')->default(true);
            $table->timestamp('sozdano_at')->useCurrent();
            $table->timestamp('obnovleno_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('podborki_obekty', function (Blueprint $table) {
            $table->id();
            $table->foreignId('podborka_id')->constrained('podborki')->cascadeOnDelete();
            $table->foreignId('nedvizhimost_id')->constrained('nedvizhimost')->cascadeOnDelete();
            $table->unsignedSmallInteger('poryadok')->default(0);
            $table->unique(['podborka_id', 'nedvizhimost_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('podborki_obekty');
        Schema::dropIfExists('podborki');
        Schema::dropIfExists('pokazy_nedvizhimosti');
        Schema::dropIfExists('zadachi_rieltora');
        Schema::dropIfExists('klienty_rieltora');
    }
};
