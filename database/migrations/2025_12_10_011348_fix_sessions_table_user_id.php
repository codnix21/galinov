<?php

/**
 * Сессии: колонка user_id и внешний ключ на таблицу polzovateli.
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
        if (Schema::hasTable('sessions')) {
            // Проверяем, существует ли колонка user_id
            if (!Schema::hasColumn('sessions', 'user_id')) {
                Schema::table('sessions', function (Blueprint $table) {
                    $table->unsignedBigInteger('user_id')->nullable()->after('id');
                });
                // Добавляем индекс отдельно
                DB::statement("ALTER TABLE sessions ADD INDEX sessions_user_id_index (user_id)");
            }
            
            // Удаляем старые внешние ключи, если они существуют
            $fkSessions = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'sessions' 
                AND COLUMN_NAME = 'user_id' 
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ");
            foreach ($fkSessions as $fk) {
                try {
                    DB::statement("ALTER TABLE sessions DROP FOREIGN KEY {$fk->CONSTRAINT_NAME}");
                } catch (\Exception $e) {
                    // Игнорируем ошибку, если ключ уже удален
                }
            }
            
            // Создаем новый внешний ключ, ссылающийся на polzovateli
            $fkExists = DB::selectOne("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'sessions' 
                AND CONSTRAINT_NAME = 'sessions_user_id_foreign'
            ");
            if (!$fkExists) {
                DB::statement("ALTER TABLE sessions ADD CONSTRAINT sessions_user_id_foreign FOREIGN KEY (user_id) REFERENCES polzovateli(id) ON DELETE CASCADE");
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('sessions')) {
            // Удаляем внешний ключ
            try {
                DB::statement("ALTER TABLE sessions DROP FOREIGN KEY sessions_user_id_foreign");
            } catch (\Exception $e) {
                // Игнорируем ошибку
            }
        }
    }
};
