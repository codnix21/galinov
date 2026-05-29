<?php

/**
 * Резерв: расширение enum status_obyavleniya значением pending_review (MySQL/MariaDB).
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('nedvizhimost')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::statement("ALTER TABLE nedvizhimost MODIFY COLUMN status_obyavleniya ENUM('draft', 'active', 'sold', 'rented', 'inactive', 'pending_review') NOT NULL DEFAULT 'draft'");
    }

    public function down(): void
    {
        if (!Schema::hasTable('nedvizhimost') || !Schema::hasTable('statusy_obyavleniy')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        $draftId = DB::table('statusy_obyavleniy')->where('kod', 'draft')->value('id');
        if ($draftId) {
            DB::table('nedvizhimost')->where('status_obyavleniya', 'pending_review')->update([
                'status_obyavleniya' => 'draft',
                'status_obyavleniya_id' => $draftId,
            ]);
        }

        DB::statement("ALTER TABLE nedvizhimost MODIFY COLUMN status_obyavleniya ENUM('draft', 'active', 'sold', 'rented', 'inactive') NOT NULL DEFAULT 'draft'");
    }
};
