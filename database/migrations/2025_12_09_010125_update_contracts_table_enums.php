<?php

/**
 * Договоры: только продажа (sale); в статус добавлен pending — ожидает подтверждения.
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
        // Обновляем enum для типа договора (только sale)
        DB::statement("ALTER TABLE contracts MODIFY COLUMN type ENUM('sale') NOT NULL");
        
        // Обновляем enum для статуса (добавляем pending)
        DB::statement("ALTER TABLE contracts MODIFY COLUMN status ENUM('draft', 'pending', 'active', 'completed', 'cancelled') NOT NULL DEFAULT 'draft'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Возвращаем обратно
        DB::statement("ALTER TABLE contracts MODIFY COLUMN type ENUM('sale', 'rent') NOT NULL");
        DB::statement("ALTER TABLE contracts MODIFY COLUMN status ENUM('draft', 'active', 'completed', 'cancelled') NOT NULL DEFAULT 'draft'");
    }
};
