<?php

/**
 * Договоры купли-продажи / аренды между клиентом, риэлтором и объектом.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->onDelete('cascade'); // объект
            $table->foreignId('client_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('realtor_id')->constrained('users')->onDelete('cascade');
            $table->enum('type', ['sale', 'rent']); // продажа или аренда
            $table->decimal('price', 12, 2);
            $table->date('start_date');
            $table->date('end_date')->nullable(); // дата окончания аренды
            // draft — черновик, active — действует, completed — завершён, cancelled — отменён
            $table->enum('status', ['draft', 'active', 'completed', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
