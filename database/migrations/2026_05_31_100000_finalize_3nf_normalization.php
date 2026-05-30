<?php

/**
 * Финальная нормализация 3НФ:
 * - справочники статусов (документы, заявки, оплата);
 * - удаление дублирующих полей (klient_id, ФИО продавцов, ФИО УКЭП, status_nazvanie в аудите).
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->seedReferenceTables();
        $this->normalizeDocumentStatuses();
        $this->normalizeRequestStatuses();
        $this->normalizePaymentStatus();
        $this->dropContractBuyerDuplicate();
        $this->dropContractSellerFio();
        $this->dropContractEcpFio();
        $this->dropStatusVersionLabel();
    }

    public function down(): void
    {
        $this->restoreStatusVersionLabel();
        $this->restoreContractEcpFio();
        $this->restoreContractSellerFio();
        $this->restoreContractBuyerDuplicate();
        $this->restorePaymentStatus();
        $this->restoreRequestStatuses();
        $this->restoreDocumentStatuses();
        $this->dropReferenceTables();
    }

    private function seedReferenceTables(): void
    {
        if (!Schema::hasTable('statusy_dokumentov')) {
            Schema::create('statusy_dokumentov', function (Blueprint $table) {
                $table->id();
                $table->string('kod', 50)->unique();
                $table->string('nazvanie', 100);
            });
        }

        foreach ([
            ['kod' => 'pending', 'nazvanie' => 'На модерации'],
            ['kod' => 'checking', 'nazvanie' => 'Автопроверка'],
            ['kod' => 'verified', 'nazvanie' => 'Проверен'],
            ['kod' => 'rejected', 'nazvanie' => 'Отклонён'],
        ] as $row) {
            DB::table('statusy_dokumentov')->insertOrIgnore($row);
        }

        if (!Schema::hasTable('statusy_zayavok')) {
            Schema::create('statusy_zayavok', function (Blueprint $table) {
                $table->id();
                $table->string('gruppa', 32);
                $table->string('kod', 50);
                $table->string('nazvanie', 100);
                $table->unique(['gruppa', 'kod']);
            });
        }

        $requestStatuses = [
            ['gruppa' => 'inquiry', 'kod' => 'new', 'nazvanie' => 'Новая'],
            ['gruppa' => 'inquiry', 'kod' => 'processed', 'nazvanie' => 'Обработана'],
            ['gruppa' => 'inquiry', 'kod' => 'closed', 'nazvanie' => 'Закрыта'],
            ['gruppa' => 'selection', 'kod' => 'new', 'nazvanie' => 'Новая'],
            ['gruppa' => 'selection', 'kod' => 'processed', 'nazvanie' => 'В работе'],
            ['gruppa' => 'crm', 'kod' => 'new', 'nazvanie' => 'Новый'],
            ['gruppa' => 'crm', 'kod' => 'in_progress', 'nazvanie' => 'В работе'],
            ['gruppa' => 'crm', 'kod' => 'deal', 'nazvanie' => 'Сделка'],
            ['gruppa' => 'crm', 'kod' => 'lost', 'nazvanie' => 'Потерян'],
            ['gruppa' => 'info', 'kod' => 'open', 'nazvanie' => 'Ожидает ответа'],
            ['gruppa' => 'info', 'kod' => 'answered', 'nazvanie' => 'Получен ответ'],
            ['gruppa' => 'info', 'kod' => 'closed', 'nazvanie' => 'Закрыт'],
        ];
        foreach ($requestStatuses as $row) {
            DB::table('statusy_zayavok')->insertOrIgnore($row);
        }

        if (!Schema::hasTable('statusy_oplat')) {
            Schema::create('statusy_oplat', function (Blueprint $table) {
                $table->id();
                $table->string('kod', 50)->unique();
                $table->string('nazvanie', 100);
            });
        }

        foreach ([
            ['kod' => 'none', 'nazvanie' => 'Не оплачено'],
            ['kod' => 'pending', 'nazvanie' => 'Ожидает оплаты'],
            ['kod' => 'simulated_paid', 'nazvanie' => 'Оплачено (тестовый платёж)'],
            ['kod' => 'robokassa_paid', 'nazvanie' => 'Оплачено (Robokassa)'],
        ] as $row) {
            DB::table('statusy_oplat')->insertOrIgnore($row);
        }
    }

    private function normalizeDocumentStatuses(): void
    {
        if (!Schema::hasTable('dokumenty_proverki') || Schema::hasColumn('dokumenty_proverki', 'status_dokumenta_id')) {
            return;
        }

        Schema::table('dokumenty_proverki', function (Blueprint $table) {
            $table->unsignedBigInteger('status_dokumenta_id')->nullable()->after('put_fajla');
        });

        $map = DB::table('statusy_dokumentov')->pluck('id', 'kod')->all();
        $defaultId = $map['pending'] ?? reset($map);

        foreach (DB::table('dokumenty_proverki')->select('id', 'status')->get() as $row) {
            $id = $map[$row->status ?? ''] ?? $defaultId;
            DB::table('dokumenty_proverki')->where('id', $row->id)->update(['status_dokumenta_id' => $id]);
        }

        DB::statement('ALTER TABLE dokumenty_proverki MODIFY COLUMN status_dokumenta_id BIGINT UNSIGNED NOT NULL');

        Schema::table('dokumenty_proverki', function (Blueprint $table) {
            $table->foreign('status_dokumenta_id')->references('id')->on('statusy_dokumentov')->restrictOnDelete();
            $table->dropColumn('status');
        });
    }

    private function normalizeRequestStatuses(): void
    {
        $tables = [
            'zayavki_obekta' => ['gruppa' => 'inquiry', 'after' => 'kommentariy'],
            'zayavki_podbora' => ['gruppa' => 'selection', 'after' => 'filtry'],
            'klienty_rieltora' => ['gruppa' => 'crm', 'after' => 'klient_id'],
            'zaprosy_dop_informacii' => ['gruppa' => 'info', 'after' => 'tip'],
        ];

        foreach ($tables as $tableName => $meta) {
            $gruppa = $meta['gruppa'];
            if (!Schema::hasTable($tableName) || !Schema::hasColumn($tableName, 'status')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $blueprint) use ($meta) {
                $blueprint->unsignedBigInteger('status_zayavki_id')->nullable()->after($meta['after']);
            });

            $map = DB::table('statusy_zayavok')->where('gruppa', $gruppa)->pluck('id', 'kod')->all();
            $defaultId = $map['new'] ?? $map['open'] ?? reset($map);

            foreach (DB::table($tableName)->select('id', 'status')->get() as $row) {
                $kod = $row->status ?? '';
                if ($gruppa === 'info' && $kod === 'new') {
                    $kod = 'open';
                }
                $id = $map[$kod] ?? $defaultId;
                DB::table($tableName)->where('id', $row->id)->update(['status_zayavki_id' => $id]);
            }

            DB::statement("ALTER TABLE {$tableName} MODIFY COLUMN status_zayavki_id BIGINT UNSIGNED NOT NULL");

            Schema::table($tableName, function (Blueprint $blueprint) {
                $blueprint->foreign('status_zayavki_id')->references('id')->on('statusy_zayavok')->restrictOnDelete();
                $blueprint->dropColumn('status');
            });
        }
    }

    private function normalizePaymentStatus(): void
    {
        if (!Schema::hasTable('dogovory') || !Schema::hasColumn('dogovory', 'oplata_status')) {
            return;
        }

        if (!Schema::hasColumn('dogovory', 'oplata_status_id')) {
            Schema::table('dogovory', function (Blueprint $table) {
                $table->unsignedBigInteger('oplata_status_id')->nullable()->after('primechaniya');
            });
        }

        $map = DB::table('statusy_oplat')->pluck('id', 'kod')->all();
        $defaultId = $map['none'] ?? reset($map);

        foreach (DB::table('dogovory')->select('id', 'oplata_status')->get() as $row) {
            $id = $map[$row->oplata_status ?? 'none'] ?? $defaultId;
            DB::table('dogovory')->where('id', $row->id)->update(['oplata_status_id' => $id]);
        }

        DB::statement('ALTER TABLE dogovory MODIFY COLUMN oplata_status_id BIGINT UNSIGNED NOT NULL');

        Schema::table('dogovory', function (Blueprint $table) {
            $table->foreign('oplata_status_id')->references('id')->on('statusy_oplat')->restrictOnDelete();
            $table->dropColumn('oplata_status');
        });
    }

    private function dropContractBuyerDuplicate(): void
    {
        if (!Schema::hasTable('dogovory') || !Schema::hasColumn('dogovory', 'klient_id')) {
            return;
        }

        DB::table('dogovory')
            ->whereNull('pokupatel_id')
            ->whereNotNull('klient_id')
            ->update(['pokupatel_id' => DB::raw('klient_id')]);

        $this->dropForeignIfExists('dogovory', 'dogovory_klient_id_foreign');

        Schema::table('dogovory', function (Blueprint $table) {
            $table->dropColumn('klient_id');
        });
    }

    private function dropContractSellerFio(): void
    {
        if (!Schema::hasTable('dogovory_prodavtsy')) {
            return;
        }

        $this->dropColumnsIfExist('dogovory_prodavtsy', ['familiya', 'imya', 'otchestvo']);
    }

    private function dropContractEcpFio(): void
    {
        $this->dropColumnsIfExist('dogovory', [
            'ecp_podpis_vladelets_fio',
            'ecp_podpis_pokupatel_fio',
            'ecp_podpis_rieltor_fio',
        ]);
    }

    private function dropStatusVersionLabel(): void
    {
        $this->dropColumnsIfExist('versii_statusov', ['status_nazvanie']);
    }

    private function dropForeignIfExists(string $table, string $name): void
    {
        $exists = DB::selectOne(
            'SELECT 1 FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = ?',
            [$table, $name, 'FOREIGN KEY'],
        );
        if ($exists) {
            DB::statement("ALTER TABLE {$table} DROP FOREIGN KEY {$name}");
        }
    }

    private function dropColumnsIfExist(string $table, array $columns): void
    {
        $toDrop = array_values(array_filter($columns, fn ($c) => Schema::hasColumn($table, $c)));
        if ($toDrop === []) {
            return;
        }
        Schema::table($table, function (Blueprint $blueprint) use ($toDrop) {
            $blueprint->dropColumn($toDrop);
        });
    }

    private function dropReferenceTables(): void
    {
        Schema::dropIfExists('statusy_oplat');
        Schema::dropIfExists('statusy_zayavok');
        Schema::dropIfExists('statusy_dokumentov');
    }

    private function restoreDocumentStatuses(): void
    {
        if (!Schema::hasTable('dokumenty_proverki')) {
            return;
        }
        if (!Schema::hasColumn('dokumenty_proverki', 'status')) {
            Schema::table('dokumenty_proverki', function (Blueprint $table) {
                $table->string('status', 32)->default('pending')->after('put_fajla');
            });
        }
        if (Schema::hasColumn('dokumenty_proverki', 'status_dokumenta_id')) {
            $map = DB::table('statusy_dokumentov')->pluck('kod', 'id')->all();
            foreach (DB::table('dokumenty_proverki')->get() as $row) {
                DB::table('dokumenty_proverki')->where('id', $row->id)->update([
                    'status' => $map[$row->status_dokumenta_id] ?? 'pending',
                ]);
            }
            Schema::table('dokumenty_proverki', function (Blueprint $table) {
                $table->dropForeign(['status_dokumenta_id']);
                $table->dropColumn('status_dokumenta_id');
            });
        }
    }

    private function restoreRequestStatuses(): void
    {
        foreach (['zayavki_obekta', 'zayavki_podbora', 'klienty_rieltora', 'zaprosy_dop_informacii'] as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }
            if (!Schema::hasColumn($table, 'status')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->string('status', 32)->default('new');
                });
            }
            if (Schema::hasColumn($table, 'status_zayavki_id')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropForeign(['status_zayavki_id']);
                    $t->dropColumn('status_zayavki_id');
                });
            }
        }
    }

    private function restorePaymentStatus(): void
    {
        if (!Schema::hasTable('dogovory')) {
            return;
        }
        if (!Schema::hasColumn('dogovory', 'oplata_status')) {
            Schema::table('dogovory', function (Blueprint $table) {
                $table->string('oplata_status', 32)->default('none');
            });
        }
        if (Schema::hasColumn('dogovory', 'oplata_status_id')) {
            Schema::table('dogovory', function (Blueprint $table) {
                $table->dropForeign(['oplata_status_id']);
                $table->dropColumn('oplata_status_id');
            });
        }
    }

    private function restoreContractBuyerDuplicate(): void
    {
        if (!Schema::hasTable('dogovory') || Schema::hasColumn('dogovory', 'klient_id')) {
            return;
        }
        Schema::table('dogovory', function (Blueprint $table) {
            $table->unsignedBigInteger('klient_id')->nullable()->after('pokupatel_id');
        });
        DB::table('dogovory')->update(['klient_id' => DB::raw('pokupatel_id')]);
    }

    private function restoreContractSellerFio(): void
    {
        if (!Schema::hasTable('dogovory_prodavtsy')) {
            return;
        }
        Schema::table('dogovory_prodavtsy', function (Blueprint $table) {
            if (!Schema::hasColumn('dogovory_prodavtsy', 'familiya')) {
                $table->string('familiya', 100)->nullable();
                $table->string('imya', 100)->nullable();
                $table->string('otchestvo', 100)->nullable();
            }
        });
    }

    private function restoreContractEcpFio(): void
    {
        Schema::table('dogovory', function (Blueprint $table) {
            foreach (['vladelets', 'pokupatel', 'rieltor'] as $party) {
                $col = 'ecp_podpis_'.$party.'_fio';
                if (!Schema::hasColumn('dogovory', $col)) {
                    $table->string($col, 255)->nullable();
                }
            }
        });
    }

    private function restoreStatusVersionLabel(): void
    {
        if (!Schema::hasTable('versii_statusov') || Schema::hasColumn('versii_statusov', 'status_nazvanie')) {
            return;
        }
        Schema::table('versii_statusov', function (Blueprint $table) {
            $table->string('status_nazvanie', 100)->nullable()->after('status_kod');
        });
    }
};
