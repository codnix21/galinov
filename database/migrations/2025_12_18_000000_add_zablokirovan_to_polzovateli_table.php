<?php

/**
 * Флаг блокировки пользователя — вход на сайт запрещён.
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
        Schema::table('polzovateli', function (Blueprint $table) {
            $table->boolean('zablokirovan')->default(false)->after('rol_id'); // true — аккаунт заблокирован
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('polzovateli', function (Blueprint $table) {
            $table->dropColumn('zablokirovan');
        });
    }
};





