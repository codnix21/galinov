<?php

/**
 * Адрес объявления: отдельно город и улица; поле address удаляется.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->string('city')->nullable()->after('address');
            $table->string('street_address')->nullable()->after('city');
        });
        
        // Копируем данные из address в street_address
        DB::statement('UPDATE properties SET street_address = address WHERE street_address IS NULL');
        
        // Удаляем старую колонку address
        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn('address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->string('address')->after('price');
        });
        
        // Копируем данные обратно
        DB::statement('UPDATE properties SET address = street_address WHERE address IS NULL');
        
        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn(['city', 'street_address']);
        });
    }
};
