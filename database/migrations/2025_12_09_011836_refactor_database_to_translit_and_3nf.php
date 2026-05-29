<?php

/**
 * Крупный рефакторинг БД: английские имена → транслит (polzovateli, nedvizhimost, dogovory…),
 * разбиение ФИО, переименование колонок и пересоздание внешних ключей.
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
        // Проверяем, какие таблицы существуют и работаем соответственно
        $hasUsers = Schema::hasTable('users');
        $hasPolzovateli = Schema::hasTable('polzovateli');
        
        if ($hasUsers && !$hasPolzovateli) {
            // Шаг 1: Добавляем новые поля для ФИО в таблицу users
            if (!Schema::hasColumn('users', 'familia')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->string('familia')->nullable()->after('name');
                    $table->string('imya')->nullable()->after('familia');
                    $table->string('otchestvo')->nullable()->after('imya');
                });
            }

            // Шаг 2: Разделяем name на familia, imya, otchestvo
            if (Schema::hasColumn('users', 'name')) {
                DB::statement("
                    UPDATE users 
                    SET 
                        familia = SUBSTRING_INDEX(name, ' ', 1),
                        imya = CASE 
                            WHEN LENGTH(name) - LENGTH(REPLACE(name, ' ', '')) >= 1 
                            THEN SUBSTRING_INDEX(SUBSTRING_INDEX(name, ' ', 2), ' ', -1)
                            ELSE NULL
                        END,
                        otchestvo = CASE 
                            WHEN LENGTH(name) - LENGTH(REPLACE(name, ' ', '')) >= 2 
                            THEN SUBSTRING_INDEX(name, ' ', -1)
                            ELSE NULL
                        END
                    WHERE name IS NOT NULL
                ");
            }

            // Шаг 3: Переименовываем таблицы
            if (Schema::hasTable('users')) Schema::rename('users', 'polzovateli');
            if (Schema::hasTable('properties')) Schema::rename('properties', 'nedvizhimost');
            if (Schema::hasTable('contracts')) Schema::rename('contracts', 'dogovory');
            if (Schema::hasTable('property_images')) Schema::rename('property_images', 'izobrazheniya_nedvizhimosti');
            if (Schema::hasTable('favorites')) Schema::rename('favorites', 'izbrannoe');
        }
        
        // Если таблицы уже переименованы, пропускаем шаги 1-3

        // Шаг 4: Переименовываем поля в таблице polzovateli (только если они еще не переименованы)
        if (Schema::hasTable('polzovateli')) {
            if (Schema::hasColumn('polzovateli', 'name') && !Schema::hasColumn('polzovateli', 'name_old')) {
                DB::statement("ALTER TABLE polzovateli CHANGE COLUMN name name_old VARCHAR(255) NULL");
            }
            if (Schema::hasColumn('polzovateli', 'email') && !Schema::hasColumn('polzovateli', 'email_polzovatela')) {
                DB::statement("ALTER TABLE polzovateli CHANGE COLUMN email email_polzovatela VARCHAR(255) NOT NULL");
            }
            if (Schema::hasColumn('polzovateli', 'password') && !Schema::hasColumn('polzovateli', 'parol')) {
                DB::statement("ALTER TABLE polzovateli CHANGE COLUMN password parol VARCHAR(255) NOT NULL");
            }
            if (Schema::hasColumn('polzovateli', 'role') && !Schema::hasColumn('polzovateli', 'rol')) {
                DB::statement("ALTER TABLE polzovateli CHANGE COLUMN role rol VARCHAR(255) NOT NULL");
            }
            if (Schema::hasColumn('polzovateli', 'phone') && !Schema::hasColumn('polzovateli', 'telefon')) {
                DB::statement("ALTER TABLE polzovateli CHANGE COLUMN phone telefon VARCHAR(255) NULL");
            }
            if (Schema::hasColumn('polzovateli', 'bio') && !Schema::hasColumn('polzovateli', 'biografiya')) {
                DB::statement("ALTER TABLE polzovateli CHANGE COLUMN bio biografiya TEXT NULL");
            }
            if (Schema::hasColumn('polzovateli', 'avatar') && !Schema::hasColumn('polzovateli', 'avatar_polzovatela')) {
                DB::statement("ALTER TABLE polzovateli CHANGE COLUMN avatar avatar_polzovatela VARCHAR(255) NULL");
            }
            if (Schema::hasColumn('polzovateli', 'email_verified_at') && !Schema::hasColumn('polzovateli', 'email_podtverzhden_at')) {
                DB::statement("ALTER TABLE polzovateli CHANGE COLUMN email_verified_at email_podtverzhden_at TIMESTAMP NULL");
            }
            if (Schema::hasColumn('polzovateli', 'created_at') && !Schema::hasColumn('polzovateli', 'sozdano_at')) {
                DB::statement("ALTER TABLE polzovateli CHANGE COLUMN created_at sozdano_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP");
            }
            if (Schema::hasColumn('polzovateli', 'updated_at') && !Schema::hasColumn('polzovateli', 'obnovleno_at')) {
                DB::statement("ALTER TABLE polzovateli CHANGE COLUMN updated_at obnovleno_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
            }
        }

        // Шаг 5: Переименовываем поля в таблице nedvizhimost (только если они еще не переименованы)
        if (Schema::hasTable('nedvizhimost')) {
            if (Schema::hasColumn('nedvizhimost', 'title') && !Schema::hasColumn('nedvizhimost', 'nazvanie')) {
                DB::statement("ALTER TABLE nedvizhimost CHANGE COLUMN title nazvanie VARCHAR(255) NOT NULL");
            }
            if (Schema::hasColumn('nedvizhimost', 'description') && !Schema::hasColumn('nedvizhimost', 'opisanie')) {
                DB::statement("ALTER TABLE nedvizhimost CHANGE COLUMN description opisanie TEXT NOT NULL");
            }
            if (Schema::hasColumn('nedvizhimost', 'type') && !Schema::hasColumn('nedvizhimost', 'tip')) {
                DB::statement("ALTER TABLE nedvizhimost CHANGE COLUMN type tip ENUM('apartment', 'house', 'commercial', 'land') NOT NULL");
            }
            if (Schema::hasColumn('nedvizhimost', 'operation') && !Schema::hasColumn('nedvizhimost', 'operatsiya')) {
                DB::statement("ALTER TABLE nedvizhimost CHANGE COLUMN operation operatsiya ENUM('sale', 'rent') NOT NULL");
            }
            if (Schema::hasColumn('nedvizhimost', 'price') && !Schema::hasColumn('nedvizhimost', 'tsena')) {
                DB::statement("ALTER TABLE nedvizhimost CHANGE COLUMN price tsena DECIMAL(12, 2) NOT NULL");
            }
            if (Schema::hasColumn('nedvizhimost', 'city') && !Schema::hasColumn('nedvizhimost', 'gorod')) {
                DB::statement("ALTER TABLE nedvizhimost CHANGE COLUMN city gorod VARCHAR(255) NULL");
            }
            if (Schema::hasColumn('nedvizhimost', 'street_address') && !Schema::hasColumn('nedvizhimost', 'adres_ulitsy')) {
                DB::statement("ALTER TABLE nedvizhimost CHANGE COLUMN street_address adres_ulitsy VARCHAR(255) NULL");
            }
            if (Schema::hasColumn('nedvizhimost', 'area') && !Schema::hasColumn('nedvizhimost', 'ploshchad')) {
                DB::statement("ALTER TABLE nedvizhimost CHANGE COLUMN area ploshchad INTEGER NULL");
            }
            if (Schema::hasColumn('nedvizhimost', 'rooms') && !Schema::hasColumn('nedvizhimost', 'komnaty')) {
                DB::statement("ALTER TABLE nedvizhimost CHANGE COLUMN rooms komnaty INTEGER NULL");
            }
            if (Schema::hasColumn('nedvizhimost', 'floor') && !Schema::hasColumn('nedvizhimost', 'etazh')) {
                DB::statement("ALTER TABLE nedvizhimost CHANGE COLUMN floor etazh INTEGER NULL");
            }
            if (Schema::hasColumn('nedvizhimost', 'total_floors') && !Schema::hasColumn('nedvizhimost', 'vsego_etazhey')) {
                DB::statement("ALTER TABLE nedvizhimost CHANGE COLUMN total_floors vsego_etazhey INTEGER NULL");
            }
            if (Schema::hasColumn('nedvizhimost', 'user_id') && !Schema::hasColumn('nedvizhimost', 'polzovatel_id')) {
                DB::statement("ALTER TABLE nedvizhimost CHANGE COLUMN user_id polzovatel_id BIGINT UNSIGNED NOT NULL");
            }
            if (Schema::hasColumn('nedvizhimost', 'status') && !Schema::hasColumn('nedvizhimost', 'status_obyavleniya')) {
                DB::statement("ALTER TABLE nedvizhimost CHANGE COLUMN status status_obyavleniya ENUM('draft', 'active', 'sold', 'rented', 'inactive') NOT NULL DEFAULT 'draft'");
            }
            if (Schema::hasColumn('nedvizhimost', 'created_at') && !Schema::hasColumn('nedvizhimost', 'sozdano_at')) {
                DB::statement("ALTER TABLE nedvizhimost CHANGE COLUMN created_at sozdano_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP");
            }
            if (Schema::hasColumn('nedvizhimost', 'updated_at') && !Schema::hasColumn('nedvizhimost', 'obnovleno_at')) {
                DB::statement("ALTER TABLE nedvizhimost CHANGE COLUMN updated_at obnovleno_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
            }
        }

        // Шаг 6: Обновляем внешний ключ в nedvizhimost
        // Сначала удаляем старый ключ (он мог называться properties_user_id_foreign)
        // Ищем по старому имени поля user_id, так как оно уже переименовано в polzovatel_id
        $fkName = DB::selectOne("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'nedvizhimost' 
            AND COLUMN_NAME = 'polzovatel_id' 
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        
        if ($fkName) {
            try {
                DB::statement("ALTER TABLE nedvizhimost DROP FOREIGN KEY {$fkName->CONSTRAINT_NAME}");
            } catch (\Exception $e) {
                // Игнорируем ошибку, если ключ уже удален
            }
        }
        
        $fkNedvizhimostPolzovatel = DB::selectOne("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'nedvizhimost' 
            AND CONSTRAINT_NAME = 'nedvizhimost_polzovatel_id_foreign'
        ");
        if (!$fkNedvizhimostPolzovatel) {
            DB::statement("ALTER TABLE nedvizhimost ADD CONSTRAINT nedvizhimost_polzovatel_id_foreign FOREIGN KEY (polzovatel_id) REFERENCES polzovateli(id) ON DELETE CASCADE");
        }

        // Шаг 7: Удаляем старые внешние ключи в dogovory ПЕРЕД переименованием полей
        // Получаем реальные имена внешних ключей по старым именам полей
        $fkProperty = DB::selectOne("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'dogovory' 
            AND COLUMN_NAME = 'property_id' 
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        if ($fkProperty) {
            try {
                DB::statement("ALTER TABLE dogovory DROP FOREIGN KEY {$fkProperty->CONSTRAINT_NAME}");
            } catch (\Exception $e) {
                // Игнорируем ошибку, если ключ уже удален
            }
        }
        
        $fkClient = DB::selectOne("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'dogovory' 
            AND COLUMN_NAME = 'client_id' 
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        if ($fkClient) {
            try {
                DB::statement("ALTER TABLE dogovory DROP FOREIGN KEY {$fkClient->CONSTRAINT_NAME}");
            } catch (\Exception $e) {
                // Игнорируем ошибку, если ключ уже удален
            }
        }
        
        $fkRealtor = DB::selectOne("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'dogovory' 
            AND COLUMN_NAME = 'realtor_id' 
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        if ($fkRealtor) {
            try {
                DB::statement("ALTER TABLE dogovory DROP FOREIGN KEY {$fkRealtor->CONSTRAINT_NAME}");
            } catch (\Exception $e) {
                // Игнорируем ошибку, если ключ уже удален
            }
        }

        // Шаг 8: Переименовываем поля в таблице dogovory (только если они еще не переименованы)
        if (Schema::hasTable('dogovory')) {
            if (Schema::hasColumn('dogovory', 'property_id') && !Schema::hasColumn('dogovory', 'nedvizhimost_id')) {
                DB::statement("ALTER TABLE dogovory CHANGE COLUMN property_id nedvizhimost_id BIGINT UNSIGNED NOT NULL");
            }
            if (Schema::hasColumn('dogovory', 'client_id') && !Schema::hasColumn('dogovory', 'klient_id')) {
                DB::statement("ALTER TABLE dogovory CHANGE COLUMN client_id klient_id BIGINT UNSIGNED NOT NULL");
            }
            if (Schema::hasColumn('dogovory', 'realtor_id') && !Schema::hasColumn('dogovory', 'rieltor_id')) {
                DB::statement("ALTER TABLE dogovory CHANGE COLUMN realtor_id rieltor_id BIGINT UNSIGNED NOT NULL");
            }
            if (Schema::hasColumn('dogovory', 'type') && !Schema::hasColumn('dogovory', 'tip')) {
                DB::statement("ALTER TABLE dogovory CHANGE COLUMN type tip ENUM('sale') NOT NULL");
            }
            if (Schema::hasColumn('dogovory', 'price') && !Schema::hasColumn('dogovory', 'tsena')) {
                DB::statement("ALTER TABLE dogovory CHANGE COLUMN price tsena DECIMAL(12, 2) NOT NULL");
            }
            if (Schema::hasColumn('dogovory', 'start_date') && !Schema::hasColumn('dogovory', 'data_nachala')) {
                DB::statement("ALTER TABLE dogovory CHANGE COLUMN start_date data_nachala DATE NOT NULL");
            }
            if (Schema::hasColumn('dogovory', 'status') && !Schema::hasColumn('dogovory', 'status_dogovora')) {
                DB::statement("ALTER TABLE dogovory CHANGE COLUMN status status_dogovora ENUM('draft', 'pending', 'active', 'completed', 'cancelled') NOT NULL DEFAULT 'draft'");
            }
            if (Schema::hasColumn('dogovory', 'notes') && !Schema::hasColumn('dogovory', 'primechaniya')) {
                DB::statement("ALTER TABLE dogovory CHANGE COLUMN notes primechaniya TEXT NULL");
            }
            if (Schema::hasColumn('dogovory', 'created_at') && !Schema::hasColumn('dogovory', 'sozdano_at')) {
                DB::statement("ALTER TABLE dogovory CHANGE COLUMN created_at sozdano_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP");
            }
            if (Schema::hasColumn('dogovory', 'updated_at') && !Schema::hasColumn('dogovory', 'obnovleno_at')) {
                DB::statement("ALTER TABLE dogovory CHANGE COLUMN updated_at obnovleno_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
            }
        }

        // Шаг 9: Создаем новые внешние ключи в dogovory (только если они еще не существуют)
        $fkNedvizhimost = DB::selectOne("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'dogovory' 
            AND CONSTRAINT_NAME = 'dogovory_nedvizhimost_id_foreign'
        ");
        if (!$fkNedvizhimost) {
            DB::statement("ALTER TABLE dogovory ADD CONSTRAINT dogovory_nedvizhimost_id_foreign FOREIGN KEY (nedvizhimost_id) REFERENCES nedvizhimost(id) ON DELETE CASCADE");
        }
        
        $fkKlient = DB::selectOne("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'dogovory' 
            AND CONSTRAINT_NAME = 'dogovory_klient_id_foreign'
        ");
        if (!$fkKlient) {
            DB::statement("ALTER TABLE dogovory ADD CONSTRAINT dogovory_klient_id_foreign FOREIGN KEY (klient_id) REFERENCES polzovateli(id) ON DELETE CASCADE");
        }
        
        $fkRieltor = DB::selectOne("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'dogovory' 
            AND CONSTRAINT_NAME = 'dogovory_rieltor_id_foreign'
        ");
        if (!$fkRieltor) {
            DB::statement("ALTER TABLE dogovory ADD CONSTRAINT dogovory_rieltor_id_foreign FOREIGN KEY (rieltor_id) REFERENCES polzovateli(id) ON DELETE CASCADE");
        }

        // Шаг 10: Удаляем старый внешний ключ в izobrazheniya_nedvizhimosti ПЕРЕД переименованием полей
        $fkImage = DB::selectOne("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'izobrazheniya_nedvizhimosti' 
            AND COLUMN_NAME = 'property_id' 
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        if ($fkImage) {
            try {
                DB::statement("ALTER TABLE izobrazheniya_nedvizhimosti DROP FOREIGN KEY {$fkImage->CONSTRAINT_NAME}");
            } catch (\Exception $e) {
                // Игнорируем ошибку, если ключ уже удален
            }
        }

        // Шаг 11: Переименовываем поля в таблице izobrazheniya_nedvizhimosti
        // Шаг 9: Переименовываем поля в таблице izobrazheniya_nedvizhimosti (только если они еще не переименованы)
        if (Schema::hasTable('izobrazheniya_nedvizhimosti')) {
            if (Schema::hasColumn('izobrazheniya_nedvizhimosti', 'property_id') && !Schema::hasColumn('izobrazheniya_nedvizhimosti', 'nedvizhimost_id')) {
                DB::statement("ALTER TABLE izobrazheniya_nedvizhimosti CHANGE COLUMN property_id nedvizhimost_id BIGINT UNSIGNED NOT NULL");
            }
            if (Schema::hasColumn('izobrazheniya_nedvizhimosti', 'image_path') && !Schema::hasColumn('izobrazheniya_nedvizhimosti', 'put_k_izobrazheniyu')) {
                DB::statement("ALTER TABLE izobrazheniya_nedvizhimosti CHANGE COLUMN image_path put_k_izobrazheniyu VARCHAR(255) NOT NULL");
            }
            if (Schema::hasColumn('izobrazheniya_nedvizhimosti', 'order') && !Schema::hasColumn('izobrazheniya_nedvizhimosti', 'poryadok')) {
                DB::statement("ALTER TABLE izobrazheniya_nedvizhimosti CHANGE COLUMN `order` poryadok INTEGER NOT NULL DEFAULT 0");
            }
            if (Schema::hasColumn('izobrazheniya_nedvizhimosti', 'created_at') && !Schema::hasColumn('izobrazheniya_nedvizhimosti', 'sozdano_at')) {
                DB::statement("ALTER TABLE izobrazheniya_nedvizhimosti CHANGE COLUMN created_at sozdano_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP");
            }
            if (Schema::hasColumn('izobrazheniya_nedvizhimosti', 'updated_at') && !Schema::hasColumn('izobrazheniya_nedvizhimosti', 'obnovleno_at')) {
                DB::statement("ALTER TABLE izobrazheniya_nedvizhimosti CHANGE COLUMN updated_at obnovleno_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
            }
        }

        // Шаг 12: Создаем новый внешний ключ в izobrazheniya_nedvizhimosti
        $fkImageNedvizhimost = DB::selectOne("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'izobrazheniya_nedvizhimosti' 
            AND CONSTRAINT_NAME = 'izobrazheniya_nedvizhimosti_nedvizhimost_id_foreign'
        ");
        if (!$fkImageNedvizhimost) {
            DB::statement("ALTER TABLE izobrazheniya_nedvizhimosti ADD CONSTRAINT izobrazheniya_nedvizhimosti_nedvizhimost_id_foreign FOREIGN KEY (nedvizhimost_id) REFERENCES nedvizhimost(id) ON DELETE CASCADE");
        }

        // Шаг 13: Удаляем старые внешние ключи в izbrannoe ПЕРЕД переименованием полей
        $fkFavUser = DB::selectOne("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'izbrannoe' 
            AND COLUMN_NAME = 'user_id' 
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        if ($fkFavUser) {
            try {
                DB::statement("ALTER TABLE izbrannoe DROP FOREIGN KEY {$fkFavUser->CONSTRAINT_NAME}");
            } catch (\Exception $e) {
                // Игнорируем ошибку, если ключ уже удален
            }
        }
        
        $fkFavProperty = DB::selectOne("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'izbrannoe' 
            AND COLUMN_NAME = 'property_id' 
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        if ($fkFavProperty) {
            try {
                DB::statement("ALTER TABLE izbrannoe DROP FOREIGN KEY {$fkFavProperty->CONSTRAINT_NAME}");
            } catch (\Exception $e) {
                // Игнорируем ошибку, если ключ уже удален
            }
        }

        // Шаг 14: Переименовываем поля в таблице izbrannoe (только если они еще не переименованы)
        if (Schema::hasTable('izbrannoe')) {
            if (Schema::hasColumn('izbrannoe', 'user_id') && !Schema::hasColumn('izbrannoe', 'polzovatel_id')) {
                DB::statement("ALTER TABLE izbrannoe CHANGE COLUMN user_id polzovatel_id BIGINT UNSIGNED NOT NULL");
            }
            if (Schema::hasColumn('izbrannoe', 'property_id') && !Schema::hasColumn('izbrannoe', 'nedvizhimost_id')) {
                DB::statement("ALTER TABLE izbrannoe CHANGE COLUMN property_id nedvizhimost_id BIGINT UNSIGNED NOT NULL");
            }
            if (Schema::hasColumn('izbrannoe', 'created_at') && !Schema::hasColumn('izbrannoe', 'sozdano_at')) {
                DB::statement("ALTER TABLE izbrannoe CHANGE COLUMN created_at sozdano_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP");
            }
            if (Schema::hasColumn('izbrannoe', 'updated_at') && !Schema::hasColumn('izbrannoe', 'obnovleno_at')) {
                DB::statement("ALTER TABLE izbrannoe CHANGE COLUMN updated_at obnovleno_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
            }
        }

        // Шаг 15: Создаем новые внешние ключи в izbrannoe (только если они еще не существуют)
        $fkFavPolzovatel = DB::selectOne("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'izbrannoe' 
            AND CONSTRAINT_NAME = 'izbrannoe_polzovatel_id_foreign'
        ");
        if (!$fkFavPolzovatel) {
            DB::statement("ALTER TABLE izbrannoe ADD CONSTRAINT izbrannoe_polzovatel_id_foreign FOREIGN KEY (polzovatel_id) REFERENCES polzovateli(id) ON DELETE CASCADE");
        }
        
        $fkFavNedvizhimost = DB::selectOne("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'izbrannoe' 
            AND CONSTRAINT_NAME = 'izbrannoe_nedvizhimost_id_foreign'
        ");
        if (!$fkFavNedvizhimost) {
            DB::statement("ALTER TABLE izbrannoe ADD CONSTRAINT izbrannoe_nedvizhimost_id_foreign FOREIGN KEY (nedvizhimost_id) REFERENCES nedvizhimost(id) ON DELETE CASCADE");
        }

        // Шаг 16: Обновляем уникальный индекс в izbrannoe
        // Шаг 13: Обновляем уникальный индекс в izbrannoe
        try {
            DB::statement("ALTER TABLE izbrannoe DROP INDEX izbrannoe_user_id_property_id_unique");
        } catch (\Exception $e) {
            // Индекс уже удален или не существует
        }
        try {
            DB::statement("ALTER TABLE izbrannoe DROP INDEX favorites_user_id_property_id_unique");
        } catch (\Exception $e) {
            // Индекс уже удален или не существует
        }
        // Проверяем, существует ли уже новый индекс
        $indexExists = DB::selectOne("
            SELECT COUNT(*) as count
            FROM information_schema.STATISTICS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'izbrannoe' 
            AND INDEX_NAME = 'izbrannoe_polzovatel_id_nedvizhimost_id_unique'
        ");
        if (!$indexExists || $indexExists->count == 0) {
            DB::statement("ALTER TABLE izbrannoe ADD UNIQUE KEY izbrannoe_polzovatel_id_nedvizhimost_id_unique (polzovatel_id, nedvizhimost_id)");
        }

        // Шаг 17: Обновляем внешний ключ в sessions таблице (оставляем user_id, но обновляем FK)
        if (Schema::hasTable('sessions')) {
            // Удаляем старый внешний ключ, если он существует
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
            
            // Добавляем колонку user_id, если её нет
            if (!Schema::hasColumn('sessions', 'user_id')) {
                Schema::table('sessions', function (Blueprint $table) {
                    $table->foreignId('user_id')->nullable()->index()->after('id');
                });
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
        // Откат изменений в обратном порядке
        DB::statement("ALTER TABLE sessions CHANGE COLUMN polzovatel_id user_id BIGINT UNSIGNED NULL");

        // izbrannoe
        DB::statement("ALTER TABLE izbrannoe DROP INDEX izbrannoe_polzovatel_id_nedvizhimost_id_unique");
        DB::statement("ALTER TABLE izbrannoe ADD UNIQUE KEY izbrannoe_user_id_property_id_unique (polzovatel_id, nedvizhimost_id)");
        DB::statement("ALTER TABLE izbrannoe DROP FOREIGN KEY izbrannoe_polzovatel_id_foreign");
        DB::statement("ALTER TABLE izbrannoe DROP FOREIGN KEY izbrannoe_nedvizhimost_id_foreign");
        DB::statement("ALTER TABLE izbrannoe ADD CONSTRAINT izbrannoe_user_id_foreign FOREIGN KEY (polzovatel_id) REFERENCES polzovateli(id) ON DELETE CASCADE");
        DB::statement("ALTER TABLE izbrannoe ADD CONSTRAINT izbrannoe_property_id_foreign FOREIGN KEY (nedvizhimost_id) REFERENCES nedvizhimost(id) ON DELETE CASCADE");
        DB::statement("ALTER TABLE izbrannoe CHANGE COLUMN polzovatel_id user_id BIGINT UNSIGNED NOT NULL");
        DB::statement("ALTER TABLE izbrannoe CHANGE COLUMN nedvizhimost_id property_id BIGINT UNSIGNED NOT NULL");
        DB::statement("ALTER TABLE izbrannoe CHANGE COLUMN sozdano_at created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP");
        DB::statement("ALTER TABLE izbrannoe CHANGE COLUMN obnovleno_at updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");

        // izobrazheniya_nedvizhimosti
        DB::statement("ALTER TABLE izobrazheniya_nedvizhimosti DROP FOREIGN KEY izobrazheniya_nedvizhimosti_nedvizhimost_id_foreign");
        DB::statement("ALTER TABLE izobrazheniya_nedvizhimosti ADD CONSTRAINT izobrazheniya_nedvizhimosti_property_id_foreign FOREIGN KEY (nedvizhimost_id) REFERENCES nedvizhimost(id) ON DELETE CASCADE");
        DB::statement("ALTER TABLE izobrazheniya_nedvizhimosti CHANGE COLUMN nedvizhimost_id property_id BIGINT UNSIGNED NOT NULL");
        DB::statement("ALTER TABLE izobrazheniya_nedvizhimosti CHANGE COLUMN put_k_izobrazheniyu image_path VARCHAR(255) NOT NULL");
        DB::statement("ALTER TABLE izobrazheniya_nedvizhimosti CHANGE COLUMN poryadok `order` INTEGER NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE izobrazheniya_nedvizhimosti CHANGE COLUMN sozdano_at created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP");
        DB::statement("ALTER TABLE izobrazheniya_nedvizhimosti CHANGE COLUMN obnovleno_at updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");

        // dogovory
        DB::statement("ALTER TABLE dogovory DROP FOREIGN KEY dogovory_nedvizhimost_id_foreign");
        DB::statement("ALTER TABLE dogovory DROP FOREIGN KEY dogovory_klient_id_foreign");
        DB::statement("ALTER TABLE dogovory DROP FOREIGN KEY dogovory_rieltor_id_foreign");
        DB::statement("ALTER TABLE dogovory ADD CONSTRAINT dogovory_property_id_foreign FOREIGN KEY (nedvizhimost_id) REFERENCES nedvizhimost(id) ON DELETE CASCADE");
        DB::statement("ALTER TABLE dogovory ADD CONSTRAINT dogovory_client_id_foreign FOREIGN KEY (klient_id) REFERENCES polzovateli(id) ON DELETE CASCADE");
        DB::statement("ALTER TABLE dogovory ADD CONSTRAINT dogovory_realtor_id_foreign FOREIGN KEY (rieltor_id) REFERENCES polzovateli(id) ON DELETE CASCADE");
        DB::statement("ALTER TABLE dogovory CHANGE COLUMN nedvizhimost_id property_id BIGINT UNSIGNED NOT NULL");
        DB::statement("ALTER TABLE dogovory CHANGE COLUMN klient_id client_id BIGINT UNSIGNED NOT NULL");
        DB::statement("ALTER TABLE dogovory CHANGE COLUMN rieltor_id realtor_id BIGINT UNSIGNED NOT NULL");
        DB::statement("ALTER TABLE dogovory CHANGE COLUMN tip type ENUM('sale') NOT NULL");
        DB::statement("ALTER TABLE dogovory CHANGE COLUMN tsena price DECIMAL(12, 2) NOT NULL");
        DB::statement("ALTER TABLE dogovory CHANGE COLUMN data_nachala start_date DATE NOT NULL");
        DB::statement("ALTER TABLE dogovory CHANGE COLUMN status_dogovora status ENUM('draft', 'pending', 'active', 'completed', 'cancelled') NOT NULL DEFAULT 'draft'");
        DB::statement("ALTER TABLE dogovory CHANGE COLUMN primechaniya notes TEXT NULL");
        DB::statement("ALTER TABLE dogovory CHANGE COLUMN sozdano_at created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP");
        DB::statement("ALTER TABLE dogovory CHANGE COLUMN obnovleno_at updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");

        // nedvizhimost
        DB::statement("ALTER TABLE nedvizhimost DROP FOREIGN KEY nedvizhimost_polzovatel_id_foreign");
        DB::statement("ALTER TABLE nedvizhimost ADD CONSTRAINT nedvizhimost_user_id_foreign FOREIGN KEY (polzovatel_id) REFERENCES polzovateli(id) ON DELETE CASCADE");
        DB::statement("ALTER TABLE nedvizhimost CHANGE COLUMN nazvanie title VARCHAR(255) NOT NULL");
        DB::statement("ALTER TABLE nedvizhimost CHANGE COLUMN opisanie description TEXT NOT NULL");
        DB::statement("ALTER TABLE nedvizhimost CHANGE COLUMN tip type ENUM('apartment', 'house', 'commercial', 'land') NOT NULL");
        DB::statement("ALTER TABLE nedvizhimost CHANGE COLUMN operatsiya operation ENUM('sale', 'rent') NOT NULL");
        DB::statement("ALTER TABLE nedvizhimost CHANGE COLUMN tsena price DECIMAL(12, 2) NOT NULL");
        DB::statement("ALTER TABLE nedvizhimost CHANGE COLUMN gorod city VARCHAR(255) NULL");
        DB::statement("ALTER TABLE nedvizhimost CHANGE COLUMN adres_ulitsy street_address VARCHAR(255) NULL");
        DB::statement("ALTER TABLE nedvizhimost CHANGE COLUMN ploshchad area INTEGER NULL");
        DB::statement("ALTER TABLE nedvizhimost CHANGE COLUMN komnaty rooms INTEGER NULL");
        DB::statement("ALTER TABLE nedvizhimost CHANGE COLUMN etazh floor INTEGER NULL");
        DB::statement("ALTER TABLE nedvizhimost CHANGE COLUMN vsego_etazhey total_floors INTEGER NULL");
        DB::statement("ALTER TABLE nedvizhimost CHANGE COLUMN polzovatel_id user_id BIGINT UNSIGNED NOT NULL");
        DB::statement("ALTER TABLE nedvizhimost CHANGE COLUMN status_obyavleniya status ENUM('draft', 'active', 'sold', 'rented', 'inactive') NOT NULL DEFAULT 'draft'");
        DB::statement("ALTER TABLE nedvizhimost CHANGE COLUMN sozdano_at created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP");
        DB::statement("ALTER TABLE nedvizhimost CHANGE COLUMN obnovleno_at updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");

        // polzovateli
        DB::statement("ALTER TABLE polzovateli CHANGE COLUMN email_polzovatela email VARCHAR(255) NOT NULL");
        DB::statement("ALTER TABLE polzovateli CHANGE COLUMN parol password VARCHAR(255) NOT NULL");
        DB::statement("ALTER TABLE polzovateli CHANGE COLUMN rol role VARCHAR(255) NOT NULL");
        DB::statement("ALTER TABLE polzovateli CHANGE COLUMN telefon phone VARCHAR(255) NULL");
        DB::statement("ALTER TABLE polzovateli CHANGE COLUMN biografiya bio TEXT NULL");
        DB::statement("ALTER TABLE polzovateli CHANGE COLUMN avatar_polzovatela avatar VARCHAR(255) NULL");
        DB::statement("ALTER TABLE polzovateli CHANGE COLUMN email_podtverzhden_at email_verified_at TIMESTAMP NULL");
        DB::statement("ALTER TABLE polzovateli CHANGE COLUMN sozdano_at created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP");
        DB::statement("ALTER TABLE polzovateli CHANGE COLUMN obnovleno_at updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");

        // Объединяем ФИО обратно в name
        DB::statement("
            UPDATE polzovateli 
            SET name_old = CONCAT_WS(' ', familia, imya, otchestvo)
            WHERE familia IS NOT NULL OR imya IS NOT NULL
        ");
        DB::statement("ALTER TABLE polzovateli CHANGE COLUMN name_old name VARCHAR(255) NOT NULL");

        Schema::table('polzovateli', function (Blueprint $table) {
            $table->dropColumn(['familia', 'imya', 'otchestvo']);
        });

        // Переименовываем таблицы обратно
        Schema::rename('polzovateli', 'users');
        Schema::rename('nedvizhimost', 'properties');
        Schema::rename('dogovory', 'contracts');
        Schema::rename('izobrazheniya_nedvizhimosti', 'property_images');
        Schema::rename('izbrannoe', 'favorites');
    }
};
