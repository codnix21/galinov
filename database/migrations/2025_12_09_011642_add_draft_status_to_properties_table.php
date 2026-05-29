<?php

/**
 * Статус объявления draft (черновик) по умолчанию вместо active.
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
        DB::statement("ALTER TABLE properties MODIFY COLUMN status ENUM('draft', 'active', 'sold', 'rented', 'inactive') NOT NULL DEFAULT 'draft'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE properties MODIFY COLUMN status ENUM('active', 'sold', 'rented', 'inactive') NOT NULL DEFAULT 'active'");
    }
};
