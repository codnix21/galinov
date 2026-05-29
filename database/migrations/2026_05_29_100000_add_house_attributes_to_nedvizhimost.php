<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const COLUMNS = [
        'tip_doma' => ['type' => 'string', 'length' => 32, 'after' => 'tip'],
        'est_tsokol' => ['type' => 'boolean', 'after' => 'vsego_etazhey'],
        'ploshchad_uchastka' => ['type' => 'decimal', 'precision' => 10, 'scale' => 2, 'comment' => 'сотки', 'after' => 'est_tsokol'],
        'garazh' => ['type' => 'boolean', 'after' => 'ploshchad_uchastka'],
        'parking' => ['type' => 'boolean', 'after' => 'garazh'],
        'internet' => ['type' => 'boolean', 'after' => 'parking'],
        'otoplenie' => ['type' => 'boolean', 'after' => 'internet'],
        'kanalizatsiya' => ['type' => 'boolean', 'after' => 'otoplenie'],
        'vodosnabzhenie' => ['type' => 'boolean', 'after' => 'kanalizatsiya'],
        'gaz' => ['type' => 'boolean', 'after' => 'vodosnabzhenie'],
        'banya' => ['type' => 'boolean', 'after' => 'gaz'],
        'bassein' => ['type' => 'boolean', 'after' => 'banya'],
        'okhrana' => ['type' => 'boolean', 'after' => 'bassein'],
        'zabor' => ['type' => 'boolean', 'after' => 'okhrana'],
    ];

    public function up(): void
    {
        $missing = array_filter(
            array_keys(self::COLUMNS),
            fn (string $col) => !Schema::hasColumn('nedvizhimost', $col)
        );

        if ($missing === []) {
            return;
        }

        Schema::table('nedvizhimost', function (Blueprint $table) use ($missing) {
            foreach ($missing as $name) {
                $def = self::COLUMNS[$name];
                $column = match ($def['type']) {
                    'string' => $table->string($name, $def['length'] ?? 255),
                    'decimal' => $table->decimal($name, $def['precision'], $def['scale']),
                    default => $table->boolean($name),
                };

                if (isset($def['comment'])) {
                    $column->comment($def['comment']);
                }

                $column->nullable();

                if (isset($def['after'])) {
                    $column->after($def['after']);
                }
            }
        });
    }

    public function down(): void
    {
        $present = array_filter(
            array_keys(self::COLUMNS),
            fn (string $col) => Schema::hasColumn('nedvizhimost', $col)
        );

        if ($present === []) {
            return;
        }

        Schema::table('nedvizhimost', function (Blueprint $table) use ($present) {
            $table->dropColumn($present);
        });
    }
};
