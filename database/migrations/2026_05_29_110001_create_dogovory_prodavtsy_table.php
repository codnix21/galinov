<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dogovory_prodavtsy', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dogovor_id')->constrained('dogovory')->cascadeOnDelete();
            $table->foreignId('polzovatel_id')->constrained('polzovateli')->cascadeOnDelete();
            $table->decimal('dolya_procent', 5, 2)->nullable();
            $table->unsignedSmallInteger('poryadok')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dogovory_prodavtsy');
    }
};
