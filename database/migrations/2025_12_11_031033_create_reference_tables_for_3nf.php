<?php

/**
 * Справочники 3НФ: роли, города, статусы объявлений и договоров; FK rol_id, gorod_id, status_*_id.
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
        // Шаг 1: Создаем таблицу ролей
        if (!Schema::hasTable('roli')) {
            Schema::create('roli', function (Blueprint $table) {
                $table->id();
                $table->string('kod', 50)->unique(); // admin, realtor, client, guest
                $table->string('nazvanie', 100); // Администратор, Риэлтор, Клиент, Гость
                $table->timestamp('sozdano_at')->useCurrent();
                $table->timestamp('obnovleno_at')->useCurrent()->useCurrentOnUpdate();
            });
        }

        // Шаг 2: Создаем таблицу городов
        if (!Schema::hasTable('goroda')) {
            Schema::create('goroda', function (Blueprint $table) {
                $table->id();
                $table->string('nazvanie', 255)->unique();
                $table->timestamp('sozdano_at')->useCurrent();
                $table->timestamp('obnovleno_at')->useCurrent()->useCurrentOnUpdate();
            });
        }

        // Шаг 3: Создаем таблицу статусов объявлений
        if (!Schema::hasTable('statusy_obyavleniy')) {
            Schema::create('statusy_obyavleniy', function (Blueprint $table) {
                $table->id();
                $table->string('kod', 50)->unique(); // draft, active, sold, rented, inactive
                $table->string('nazvanie', 100); // Черновик, Активно, Продано, Сдано, Неактивно
                $table->timestamp('sozdano_at')->useCurrent();
                $table->timestamp('obnovleno_at')->useCurrent()->useCurrentOnUpdate();
            });
        }

        // Шаг 4: Создаем таблицу статусов договоров
        if (!Schema::hasTable('statusy_dogovorov')) {
            Schema::create('statusy_dogovorov', function (Blueprint $table) {
                $table->id();
                $table->string('kod', 50)->unique(); // draft, pending, active, completed, cancelled
                $table->string('nazvanie', 100); // Черновик, Ожидает подтверждения, Активен, Завершен, Отменен
                $table->timestamp('sozdano_at')->useCurrent();
                $table->timestamp('obnovleno_at')->useCurrent()->useCurrentOnUpdate();
            });
        }

        // Шаг 5: Заполняем таблицу ролей
        $roles = [
            ['kod' => 'admin', 'nazvanie' => 'Администратор'],
            ['kod' => 'realtor', 'nazvanie' => 'Риэлтор'],
            ['kod' => 'client', 'nazvanie' => 'Клиент'],
            ['kod' => 'guest', 'nazvanie' => 'Гость'],
        ];
        foreach ($roles as $role) {
            DB::table('roli')->insertOrIgnore([
                'kod' => $role['kod'],
                'nazvanie' => $role['nazvanie'],
                'sozdano_at' => now(),
                'obnovleno_at' => now(),
            ]);
        }

        // Шаг 6: Заполняем таблицу статусов объявлений
        $propertyStatuses = [
            ['kod' => 'draft', 'nazvanie' => 'Черновик'],
            ['kod' => 'active', 'nazvanie' => 'Активно'],
            ['kod' => 'sold', 'nazvanie' => 'Продано'],
            ['kod' => 'rented', 'nazvanie' => 'Сдано'],
            ['kod' => 'inactive', 'nazvanie' => 'Неактивно'],
        ];
        foreach ($propertyStatuses as $status) {
            DB::table('statusy_obyavleniy')->insertOrIgnore([
                'kod' => $status['kod'],
                'nazvanie' => $status['nazvanie'],
                'sozdano_at' => now(),
                'obnovleno_at' => now(),
            ]);
        }

        // Шаг 7: Заполняем таблицу статусов договоров
        $contractStatuses = [
            ['kod' => 'draft', 'nazvanie' => 'Черновик'],
            ['kod' => 'pending', 'nazvanie' => 'Ожидает подтверждения'],
            ['kod' => 'active', 'nazvanie' => 'Активен'],
            ['kod' => 'completed', 'nazvanie' => 'Завершен'],
            ['kod' => 'cancelled', 'nazvanie' => 'Отменен'],
        ];
        foreach ($contractStatuses as $status) {
            DB::table('statusy_dogovorov')->insertOrIgnore([
                'kod' => $status['kod'],
                'nazvanie' => $status['nazvanie'],
                'sozdano_at' => now(),
                'obnovleno_at' => now(),
            ]);
        }

        // Шаг 8: Добавляем колонку rol_id в таблицу polzovateli
        if (Schema::hasTable('polzovateli') && !Schema::hasColumn('polzovateli', 'rol_id')) {
            Schema::table('polzovateli', function (Blueprint $table) {
                $table->unsignedBigInteger('rol_id')->nullable()->after('rol');
            });

            // Переносим данные из rol в rol_id
            $roleMap = [];
            $roles = DB::table('roli')->get();
            foreach ($roles as $role) {
                $roleMap[$role->kod] = $role->id;
            }

            $users = DB::table('polzovateli')->whereNotNull('rol')->get();
            foreach ($users as $user) {
                if (isset($roleMap[$user->rol])) {
                    DB::table('polzovateli')
                        ->where('id', $user->id)
                        ->update(['rol_id' => $roleMap[$user->rol]]);
                }
            }

            // Делаем колонку обязательной
            DB::statement('ALTER TABLE polzovateli MODIFY COLUMN rol_id BIGINT UNSIGNED NOT NULL');

            // Добавляем внешний ключ
            Schema::table('polzovateli', function (Blueprint $table) {
                $table->foreign('rol_id')->references('id')->on('roli')->onDelete('restrict');
            });
        }

        // Шаг 9: Добавляем колонку gorod_id в таблицу nedvizhimost
        if (Schema::hasTable('nedvizhimost') && !Schema::hasColumn('nedvizhimost', 'gorod_id')) {
            Schema::table('nedvizhimost', function (Blueprint $table) {
                $table->unsignedBigInteger('gorod_id')->nullable()->after('gorod');
            });

            // Переносим данные из gorod в gorod_id
            $properties = DB::table('nedvizhimost')->whereNotNull('gorod')->get();
            foreach ($properties as $property) {
                $city = DB::table('goroda')->where('nazvanie', $property->gorod)->first();
                if (!$city) {
                    // Создаем город, если его нет
                    $cityId = DB::table('goroda')->insertGetId([
                        'nazvanie' => $property->gorod,
                        'sozdano_at' => now(),
                        'obnovleno_at' => now(),
                    ]);
                } else {
                    $cityId = $city->id;
                }
                DB::table('nedvizhimost')
                    ->where('id', $property->id)
                    ->update(['gorod_id' => $cityId]);
            }

            // Добавляем внешний ключ
            Schema::table('nedvizhimost', function (Blueprint $table) {
                $table->foreign('gorod_id')->references('id')->on('goroda')->onDelete('set null');
            });
        }

        // Шаг 10: Добавляем колонку status_obyavleniya_id в таблицу nedvizhimost
        if (Schema::hasTable('nedvizhimost') && !Schema::hasColumn('nedvizhimost', 'status_obyavleniya_id')) {
            Schema::table('nedvizhimost', function (Blueprint $table) {
                $table->unsignedBigInteger('status_obyavleniya_id')->nullable()->after('status_obyavleniya');
            });

            // Переносим данные из status_obyavleniya в status_obyavleniya_id
            $statusMap = [];
            $statuses = DB::table('statusy_obyavleniy')->get();
            foreach ($statuses as $status) {
                $statusMap[$status->kod] = $status->id;
            }

            $properties = DB::table('nedvizhimost')->whereNotNull('status_obyavleniya')->get();
            foreach ($properties as $property) {
                if (isset($statusMap[$property->status_obyavleniya])) {
                    DB::table('nedvizhimost')
                        ->where('id', $property->id)
                        ->update(['status_obyavleniya_id' => $statusMap[$property->status_obyavleniya]]);
                }
            }

            // Устанавливаем значение по умолчанию для NULL
            $defaultStatusId = DB::table('statusy_obyavleniy')->where('kod', 'draft')->value('id');
            if ($defaultStatusId) {
                DB::table('nedvizhimost')
                    ->whereNull('status_obyavleniya_id')
                    ->update(['status_obyavleniya_id' => $defaultStatusId]);
            }

            // Делаем колонку обязательной
            DB::statement('ALTER TABLE nedvizhimost MODIFY COLUMN status_obyavleniya_id BIGINT UNSIGNED NOT NULL');

            // Добавляем внешний ключ
            Schema::table('nedvizhimost', function (Blueprint $table) {
                $table->foreign('status_obyavleniya_id')->references('id')->on('statusy_obyavleniy')->onDelete('restrict');
            });
        }

        // Шаг 11: Добавляем колонку status_dogovora_id в таблицу dogovory
        if (Schema::hasTable('dogovory') && !Schema::hasColumn('dogovory', 'status_dogovora_id')) {
            Schema::table('dogovory', function (Blueprint $table) {
                $table->unsignedBigInteger('status_dogovora_id')->nullable()->after('status_dogovora');
            });

            // Переносим данные из status_dogovora в status_dogovora_id
            $statusMap = [];
            $statuses = DB::table('statusy_dogovorov')->get();
            foreach ($statuses as $status) {
                $statusMap[$status->kod] = $status->id;
            }

            $contracts = DB::table('dogovory')->whereNotNull('status_dogovora')->get();
            foreach ($contracts as $contract) {
                if (isset($statusMap[$contract->status_dogovora])) {
                    DB::table('dogovory')
                        ->where('id', $contract->id)
                        ->update(['status_dogovora_id' => $statusMap[$contract->status_dogovora]]);
                }
            }

            // Устанавливаем значение по умолчанию для NULL
            $defaultStatusId = DB::table('statusy_dogovorov')->where('kod', 'draft')->value('id');
            if ($defaultStatusId) {
                DB::table('dogovory')
                    ->whereNull('status_dogovora_id')
                    ->update(['status_dogovora_id' => $defaultStatusId]);
            }

            // Делаем колонку обязательной
            DB::statement('ALTER TABLE dogovory MODIFY COLUMN status_dogovora_id BIGINT UNSIGNED NOT NULL');

            // Добавляем внешний ключ
            Schema::table('dogovory', function (Blueprint $table) {
                $table->foreign('status_dogovora_id')->references('id')->on('statusy_dogovorov')->onDelete('restrict');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Удаляем внешние ключи и колонки в обратном порядке
        if (Schema::hasTable('dogovory') && Schema::hasColumn('dogovory', 'status_dogovora_id')) {
            Schema::table('dogovory', function (Blueprint $table) {
                $table->dropForeign(['status_dogovora_id']);
            });
            
            // Переносим данные обратно
            $contracts = DB::table('dogovory')->whereNotNull('status_dogovora_id')->get();
            $statuses = DB::table('statusy_dogovorov')->pluck('kod', 'id');
            foreach ($contracts as $contract) {
                if (isset($statuses[$contract->status_dogovora_id])) {
                    DB::table('dogovory')
                        ->where('id', $contract->id)
                        ->update(['status_dogovora' => $statuses[$contract->status_dogovora_id]]);
                }
            }
            
            Schema::table('dogovory', function (Blueprint $table) {
                $table->dropColumn('status_dogovora_id');
            });
        }

        if (Schema::hasTable('nedvizhimost') && Schema::hasColumn('nedvizhimost', 'status_obyavleniya_id')) {
            Schema::table('nedvizhimost', function (Blueprint $table) {
                $table->dropForeign(['status_obyavleniya_id']);
            });
            
            // Переносим данные обратно
            $properties = DB::table('nedvizhimost')->whereNotNull('status_obyavleniya_id')->get();
            $statuses = DB::table('statusy_obyavleniy')->pluck('kod', 'id');
            foreach ($properties as $property) {
                if (isset($statuses[$property->status_obyavleniya_id])) {
                    DB::table('nedvizhimost')
                        ->where('id', $property->id)
                        ->update(['status_obyavleniya' => $statuses[$property->status_obyavleniya_id]]);
                }
            }
            
            Schema::table('nedvizhimost', function (Blueprint $table) {
                $table->dropColumn('status_obyavleniya_id');
            });
        }

        if (Schema::hasTable('nedvizhimost') && Schema::hasColumn('nedvizhimost', 'gorod_id')) {
            Schema::table('nedvizhimost', function (Blueprint $table) {
                $table->dropForeign(['gorod_id']);
            });
            
            // Переносим данные обратно
            $properties = DB::table('nedvizhimost')->whereNotNull('gorod_id')->get();
            $cities = DB::table('goroda')->pluck('nazvanie', 'id');
            foreach ($properties as $property) {
                if (isset($cities[$property->gorod_id])) {
                    DB::table('nedvizhimost')
                        ->where('id', $property->id)
                        ->update(['gorod' => $cities[$property->gorod_id]]);
                }
            }
            
            Schema::table('nedvizhimost', function (Blueprint $table) {
                $table->dropColumn('gorod_id');
            });
        }

        if (Schema::hasTable('polzovateli') && Schema::hasColumn('polzovateli', 'rol_id')) {
            Schema::table('polzovateli', function (Blueprint $table) {
                $table->dropForeign(['rol_id']);
            });
            
            // Переносим данные обратно
            $users = DB::table('polzovateli')->whereNotNull('rol_id')->get();
            $roles = DB::table('roli')->pluck('kod', 'id');
            foreach ($users as $user) {
                if (isset($roles[$user->rol_id])) {
                    DB::table('polzovateli')
                        ->where('id', $user->id)
                        ->update(['rol' => $roles[$user->rol_id]]);
                }
            }
            
            Schema::table('polzovateli', function (Blueprint $table) {
                $table->dropColumn('rol_id');
            });
        }

        // Удаляем справочные таблицы
        Schema::dropIfExists('statusy_dogovorov');
        Schema::dropIfExists('statusy_obyavleniy');
        Schema::dropIfExists('goroda');
        Schema::dropIfExists('roli');
    }
};
