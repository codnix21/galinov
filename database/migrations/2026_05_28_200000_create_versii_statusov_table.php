<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('versii_statusov', function (Blueprint $table) {
            $table->id();
            $table->string('tip_sushchnosti', 32);
            $table->unsignedBigInteger('sushchnost_id');
            $table->unsignedInteger('nomer_versii');
            $table->string('status_kod', 50)->nullable();
            $table->string('status_nazvanie', 100)->nullable();
            $table->foreignId('polzovatel_id')->nullable()->constrained('polzovateli')->nullOnDelete();
            $table->text('kommentariy')->nullable();
            $table->timestamp('sozdano_at')->useCurrent();

            $table->index(['tip_sushchnosti', 'sushchnost_id']);
            $table->unique(['tip_sushchnosti', 'sushchnost_id', 'nomer_versii'], 'versii_status_ver_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('versii_statusov');
    }
};
