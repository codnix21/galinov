<?php

/**
 * Роль пользователя: admin, realtor, client, guest (позже заменится на rol_id).
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
        Schema::table('users', function (Blueprint $table) {
            // admin — админ, realtor — риэлтор, client — клиент, guest — гость
            $table->enum('role', ['admin', 'realtor', 'client', 'guest'])->default('guest')->after('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};















