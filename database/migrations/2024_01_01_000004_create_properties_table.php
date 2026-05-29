<?php

/**
 * Таблица объявлений о недвижимости (properties → позже nedvizhimost).
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
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->enum('type', ['apartment', 'house', 'commercial', 'land']); // квартира, дом, коммерческая, земля
            $table->enum('operation', ['sale', 'rent']); // продажа, аренда
            $table->decimal('price', 12, 2);
            $table->string('address');
            $table->integer('area')->nullable(); // площадь в м²
            $table->integer('rooms')->nullable(); // количество комнат
            $table->integer('floor')->nullable(); // этаж
            $table->integer('total_floors')->nullable(); // всего этажей
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // владелец/риэлтор
            // active — в продаже, sold/rented — сделка, inactive — снято с публикации
            $table->enum('status', ['active', 'sold', 'rented', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};















