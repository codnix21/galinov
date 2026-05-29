<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sobstvenniki_nedvizhimosti', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nedvizhimost_id')->constrained('nedvizhimost')->cascadeOnDelete();
            $table->foreignId('polzovatel_id')->constrained('polzovateli')->cascadeOnDelete();
            $table->decimal('dolya_procent', 5, 2);
            $table->boolean('osnovnoy')->default(false);
            $table->unsignedSmallInteger('poryadok')->default(0);
            $table->timestamps();

            $table->unique(['nedvizhimost_id', 'polzovatel_id'], 'sobst_nedvizh_user_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sobstvenniki_nedvizhimosti');
    }
};
