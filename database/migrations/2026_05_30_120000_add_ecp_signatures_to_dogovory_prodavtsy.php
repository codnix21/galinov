<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('dogovory_prodavtsy')) {
            return;
        }

        Schema::table('dogovory_prodavtsy', function (Blueprint $table) {
            if (!Schema::hasColumn('dogovory_prodavtsy', 'ecp_podpis_at')) {
                $table->timestamp('ecp_podpis_at')->nullable()->after('poryadok');
            }
            if (!Schema::hasColumn('dogovory_prodavtsy', 'ecp_podpis_nomera')) {
                $table->string('ecp_podpis_nomera', 128)->nullable()->after('ecp_podpis_at');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('dogovory_prodavtsy')) {
            return;
        }

        Schema::table('dogovory_prodavtsy', function (Blueprint $table) {
            $cols = [];
            if (Schema::hasColumn('dogovory_prodavtsy', 'ecp_podpis_nomera')) {
                $cols[] = 'ecp_podpis_nomera';
            }
            if (Schema::hasColumn('dogovory_prodavtsy', 'ecp_podpis_at')) {
                $cols[] = 'ecp_podpis_at';
            }
            if ($cols !== []) {
                $table->dropColumn($cols);
            }
        });
    }
};
