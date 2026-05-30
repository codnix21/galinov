<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Contract;
use App\Models\ContractStatus;
use App\Models\PaymentStatus;
use App\Models\Favorite;
use App\Models\Property;
use App\Models\PropertyStatus;
use App\Models\PropertyImage;
use App\Models\PropertyInquiry;
use App\Models\PropertyOwner;
use App\Models\PropertySelectionRequest;
use App\Models\PropertyInfoRequest;
use App\Models\ResponseTemplate;
use App\Models\ContractSeller;
use App\Models\Role;
use App\Models\User;
use App\Models\UserDocument;
use App\Models\UserPersonalData;
use App\Models\ZhurnalIzmeneniy;
use App\Support\DemoImageFactory;
use App\Support\PropertyDocumentRules;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

/**
 * Тестовые данные, близкие к реальным: агентство, риэлторы, клиенты, объявления, договоры.
 *
 * Запуск: php artisan db:seed --class=DemoDataSeeder
 * Повторный запуск пропускается, если уже есть demo.admin@agency.local и фото.
 * Пароль всех учётных записей сидера: Password123!
 */
class DemoDataSeeder extends Seeder
{
    private const DEMO_PASSWORD = 'Password123!';

    private const MARKER_EMAIL = 'demo.admin@agency.local';

    public function run(): void
    {
        DemoImageFactory::assertMediaPackInstalled();

        Storage::disk('public')->makeDirectory('properties');
        Storage::disk('public')->makeDirectory('avatars');

        $demoExists = User::where('email_polzovatela', self::MARKER_EMAIL)->exists()
            && Property::count() >= 80;

        if ($demoExists && PropertyImage::count() >= 200 && !env('DEMO_RESEED_MEDIA', false)) {
            $this->command?->warn('Данные сидера уже загружены (' . Property::count() . ' объявлений, ' . PropertyImage::count() . ' фото).');
            if (!env('DEMO_SKIP_FULL_ENRICH', false)) {
                $this->call(ComprehensiveDemoSeeder::class);
            } else {
                $this->seedTzFeatureDataIfNeeded();
            }
            $this->command?->warn('Перезалить фото (Docker на сервере):');
            $this->command?->warn('  docker compose exec -e DEMO_RESEED_MEDIA=1 app php artisan db:seed --class=DemoDataSeeder');
            $this->command?->warn('Полностью с нуля (удалит все данные БД):');
            $this->command?->warn('  docker compose exec app php artisan migrate:fresh --seed --force');

            return;
        }

        if ($demoExists) {
            $this->command?->info('Обновляем фото и аватары (файлы из database/seeders/media/)…');
            $this->enrichExistingDemo();

            return;
        }

        $roles = $this->loadRoles();
        $statuses = $this->loadPropertyStatuses();
        $contractStatuses = $this->loadContractStatuses();
        $cities = $this->seedCities();

        DB::transaction(function () use ($roles, $statuses, $contractStatuses, $cities) {
            $admin = $this->createUser($roles['admin'], [
                'email_polzovatela' => self::MARKER_EMAIL,
                'familia' => 'Соколов',
                'imya' => 'Андрей',
                'otchestvo' => 'Викторович',
                'telefon' => '+7 (495) 123-45-67',
                'biografiya' => 'Руководитель агентства «Дом на ладони». Более 12 лет на рынке недвижимости Москвы и области.',
            ]);

            $realtors = $this->seedRealtors($roles['realtor']);
            $clients = $this->seedClients($roles['client']);

            $properties = $this->seedProperties($realtors, $cities, $statuses);
            $this->seedPropertyImages($properties);
            $this->seedUserAvatars($admin, $realtors, $clients);
            $this->seedPersonalData($clients);
            $this->seedProfileDocuments($clients);
            $this->seedPropertyDocuments($properties);
            $this->seedInquiries($clients, $properties);
            $this->seedContracts($properties, $realtors, $clients, $contractStatuses);
            $this->seedFavorites($clients, $properties);
            $this->seedRealtorCrm($realtors, $clients, $properties);
            $this->seedAuditSamples($properties, $admin);
            $this->call(ComprehensiveDemoSeeder::class);

            $imageCount = PropertyImage::count();

            $this->command?->info(sprintf(
                'Готово: 1 админ, %d риэлторов, %d клиентов, %d объявлений, %d фото. Пароль: %s',
                count($realtors),
                count($clients),
                count($properties),
                $imageCount,
                self::DEMO_PASSWORD
            ));
            $this->command?->info('Админ: ' . $admin->email_polzovatela);
            $this->command?->info('Риэлтор (пример): ' . $realtors[0]->email_polzovatela);
            $this->command?->info('Клиент (пример): ' . $clients[0]->email_polzovatela);
        });
    }

    private function enrichExistingDemo(): void
    {
        PropertyImage::query()->delete();
        Storage::disk('public')->deleteDirectory('demo-cache');

        $admin = User::where('email_polzovatela', self::MARKER_EMAIL)->firstOrFail();
        $realtorRoleId = Role::where('kod', 'realtor')->value('id');
        $realtors = User::where('rol_id', $realtorRoleId)
            ->where('email_polzovatela', '!=', self::MARKER_EMAIL)
            ->get()
            ->all();
        $clients = User::where('email_polzovatela', 'like', 'demo.client.%')
            ->orWhere('email_polzovatela', 'roma@mail.ru')
            ->get()
            ->all();
        $properties = Property::query()->orderBy('id')->get()->all();

        $this->seedPropertyImages($properties);
        $this->seedUserAvatars($admin, $realtors, $clients);
        $this->seedPersonalData($clients);
        $this->seedProfileDocuments($clients);
        $this->seedPropertyDocuments($properties);
        $this->seedInquiries($clients, $properties);
        $this->seedAuditSamples($properties, $admin);
        $filled = $this->backfillPropertyCharacteristics();

        $this->command?->info('Фото: ' . PropertyImage::count() . ', характеристики обновлены: ' . $filled . ', пароль: ' . self::DEMO_PASSWORD);
    }

    /** @return array<string, Role> */
    private function loadRoles(): array
    {
        $map = [];
        foreach (['admin', 'realtor', 'client'] as $kod) {
            $map[$kod] = Role::where('kod', $kod)->firstOrFail();
        }

        return $map;
    }

    /** @return array<string, int> */
    private function loadPropertyStatuses(): array
    {
        $map = [];
        foreach (PropertyStatus::query()->get(['id', 'kod']) as $row) {
            $map[$row->kod] = (int) $row->id;
        }

        return $map;
    }

    /** @return array<string, int> */
    private function loadContractStatuses(): array
    {
        $map = [];
        foreach (ContractStatus::query()->get(['id', 'kod']) as $row) {
            $map[$row->kod] = (int) $row->id;
        }

        return $map;
    }

    /** @return array<string, array{id: int, lat: float, lon: float, streets: string[]}> */
    private function seedCities(): array
    {
        $defs = [
            'Москва' => ['lat' => 55.7558, 'lon' => 37.6173, 'streets' => ['ул. Тверская', 'ул. Арбат', 'Ленинский проспект', 'ул. Пятницкая', 'Кутузовский проспект', 'ул. Маросейка']],
            'Санкт-Петербург' => ['lat' => 59.9343, 'lon' => 30.3351, 'streets' => ['Невский проспект', 'ул. Рубинштейна', 'Московский проспект', 'ул. Садовая', 'Каменноостровский проспект']],
            'Казань' => ['lat' => 55.7961, 'lon' => 49.1064, 'streets' => ['ул. Баумана', 'ул. Петербургская', 'проспект Победы', 'ул. Декабристов']],
            'Новосибирск' => ['lat' => 55.0084, 'lon' => 82.9357, 'streets' => ['ул. Ленина', 'Красный проспект', 'ул. Фрунзе', 'ул. Советская']],
            'Екатеринбург' => ['lat' => 56.8389, 'lon' => 60.6057, 'streets' => ['ул. Малышева', 'проспект Ленина', 'ул. 8 Марта', 'ул. Радищева']],
            'Нижний Новгород' => ['lat' => 56.2965, 'lon' => 43.9361, 'streets' => ['ул. Большая Покровская', 'ул. Рождественская', 'проспект Гагарина']],
            'Краснодар' => ['lat' => 45.0355, 'lon' => 38.9753, 'streets' => ['ул. Красная', 'ул. Северная', 'ул. Ставропольская']],
        ];

        $out = [];
        foreach ($defs as $name => $meta) {
            $city = City::firstOrCreate(['nazvanie' => $name]);
            $out[$name] = [
                'id' => $city->id,
                'lat' => $meta['lat'],
                'lon' => $meta['lon'],
                'streets' => $meta['streets'],
            ];
        }

        City::forgetNazvanieCache();

        return $out;
    }

    private function createUser(Role $role, array $data): User
    {
        return User::create(array_merge([
            'parol' => Hash::make(self::DEMO_PASSWORD),
            'rol_id' => $role->id,
            'zablokirovan' => false,
        ], $data));
    }

    /** @return list<User> */
    private function seedRealtors(Role $role): array
    {
        $people = [
            ['email' => 't@mail.ru', 'familia' => 'Иванова', 'imya' => 'Елена', 'otchestvo' => 'Сергеевна', 'telefon' => '+7 (916) 234-56-78', 'bio' => 'Специализация: новостройки и вторичка в ЦАО.'],
            ['email' => 'demo.realtor.01@agency.local', 'familia' => 'Петров', 'imya' => 'Дмитрий', 'otchestvo' => 'Александрович', 'telefon' => '+7 (903) 111-22-33', 'bio' => 'Эксперт по загородной недвижимости Подмосковья.'],
            ['email' => 'demo.realtor.02@agency.local', 'familia' => 'Козлова', 'imya' => 'Марина', 'otchestvo' => 'Игоревна', 'telefon' => '+7 (925) 444-55-66', 'bio' => 'Аренда жилой и коммерческой недвижимости.'],
            ['email' => 'demo.realtor.03@agency.local', 'familia' => 'Волков', 'imya' => 'Алексей', 'otchestvo' => 'Николаевич', 'telefon' => '+7 (495) 777-88-99', 'bio' => 'Сделки с элитным жильём и апартаментами.'],
            ['email' => 'demo.realtor.04@agency.local', 'familia' => 'Морозова', 'imya' => 'Ольга', 'otchestvo' => 'Павловна', 'telefon' => '+7 (812) 300-40-50', 'bio' => 'Работаю в Санкт-Петербурге и Ленобласти.'],
            ['email' => 'demo.realtor.05@agency.local', 'familia' => 'Новиков', 'imya' => 'Игорь', 'otchestvo' => 'Владимирович', 'telefon' => '+7 (343) 200-30-40', 'bio' => 'Коммерческая недвижимость Урала и Поволжья.'],
            ['email' => 'demo.realtor.06@agency.local', 'familia' => 'Фёдорова', 'imya' => 'Анна', 'otchestvo' => 'Юрьевна', 'telefon' => '+7 (861) 555-66-77', 'bio' => 'Юг России: квартиры у моря и таунхаусы.'],
        ];

        $users = [];
        foreach ($people as $p) {
            $users[] = User::updateOrCreate(
                ['email_polzovatela' => $p['email']],
                [
                    'familia' => $p['familia'],
                    'imya' => $p['imya'],
                    'otchestvo' => $p['otchestvo'],
                    'telefon' => $p['telefon'],
                    'biografiya' => $p['bio'],
                    'parol' => Hash::make(self::DEMO_PASSWORD),
                    'rol_id' => $role->id,
                    'zablokirovan' => false,
                ]
            );
        }

        return $users;
    }

    /** @return list<User> */
    private function seedClients(Role $role): array
    {
        $familyNames = ['Смирнов', 'Кузнецов', 'Попов', 'Васильев', 'Соколов', 'Михайлов', 'Фролов', 'Андреев', 'Никитин', 'Орлов', 'Захаров', 'Борисов', 'Яковлев', 'Григорьев', 'Романов', 'Семёнов', 'Егоров', 'Павлов', 'Козлов', 'Степанов', 'Николаев', 'Орлова', 'Алексеева', 'Ершова', 'Макарова', 'Зайцева', 'Соловьёва', 'Баранова', 'Киселёва', 'Медведева', 'Белова', 'Тарасова', 'Комарова', 'Виноградова', 'Богданова'];
        $firstMale = ['Александр', 'Максим', 'Иван', 'Артём', 'Дмитрий', 'Михаил', 'Кирилл', 'Андрей', 'Сергей', 'Павел', 'Роман', 'Никита', 'Егор', 'Владимир', 'Тимофей'];
        $firstFemale = ['Анна', 'Мария', 'Елена', 'Ольга', 'Наталья', 'Татьяна', 'Ирина', 'Светлана', 'Юлия', 'Екатерина', 'Дарья', 'Алина', 'Виктория', 'Полина', 'Ксения'];
        $patronymicsMale = ['Александрович', 'Сергеевич', 'Дмитриевич', 'Андреевич', 'Иванович', 'Петрович', 'Николаевич', 'Владимирович'];
        $patronymicsFemale = ['Александровна', 'Сергеевна', 'Дмитриевна', 'Андреевна', 'Ивановна', 'Петровна', 'Николаевна', 'Владимировна'];

        $users = [];
        for ($i = 1; $i <= 50; $i++) {
            $female = $i % 3 !== 0;
            $familia = $familyNames[($i - 1) % count($familyNames)];
            if ($female && !str_ends_with($familia, 'а') && !str_ends_with($familia, 'я')) {
                $familia = match (true) {
                    str_ends_with($familia, 'ов') => substr($familia, 0, -2) . 'ова',
                    str_ends_with($familia, 'ев') => substr($familia, 0, -2) . 'ева',
                    str_ends_with($familia, 'ин') => substr($familia, 0, -2) . 'ина',
                    str_ends_with($familia, 'ий') => substr($familia, 0, -2) . 'ая',
                    default => $familia . 'а',
                };
            }

            $imya = $female
                ? $firstFemale[($i - 1) % count($firstFemale)]
                : $firstMale[($i - 1) % count($firstMale)];
            $otchestvo = $female
                ? $patronymicsFemale[($i - 1) % count($patronymicsFemale)]
                : $patronymicsMale[($i - 1) % count($patronymicsMale)];

            $email = $i === 1 ? 'roma@mail.ru' : sprintf('demo.client.%02d@mail.ru', $i);

            $users[] = User::updateOrCreate(
                ['email_polzovatela' => $email],
                [
                    'familia' => $familia,
                    'imya' => $imya,
                    'otchestvo' => $otchestvo,
                    'telefon' => sprintf('+7 (9%02d) %03d-%02d-%02d', 10 + ($i % 80), 100 + $i * 7 % 900, $i % 100, 10 + $i % 89),
                    'biografiya' => $i % 4 === 0 ? 'Ищу квартиру для семьи с детьми.' : null,
                    'parol' => Hash::make(self::DEMO_PASSWORD),
                    'rol_id' => $role->id,
                    'zablokirovan' => $i === 34,
                ]
            );
        }

        return $users;
    }

    /**
     * @param  list<User>  $realtors
     * @param  array<string, array{id: int, lat: float, lon: float, streets: string[]}>  $cities
     * @param  array<string, int>  $statuses
     * @return list<Property>
     */
    private function seedProperties(array $realtors, array $cities, array $statuses): array
    {
        $cityNames = array_keys($cities);
        $statusWeights = [
            'active' => 58,
            'draft' => 6,
            'pending_review' => 8,
            'sold' => 10,
            'rented' => 8,
            'inactive' => 10,
        ];

        $rejectReasons = [
            'В описании указаны контактные данные — перенесите их в личные сообщения.',
            'Фотографии низкого качества, загрузите снимки при дневном свете.',
            'Цена существенно отличается от рыночной по району — уточните стоимость.',
            'Укажите точный адрес: дом и корпус в поле улицы.',
        ];
        $statusPool = $this->weightedPool($statusWeights);

        $templates = [
            'apartment' => [
                ['sale', '{rooms}-комн. квартира, {area} м²', 'Светлая квартира с евроремонтом. Рядом метро, школа и парк. Документы готовы к сделке.'],
                ['sale', 'Студия {area} м² в новостройке', 'Сдача дома — {year}. Закрытая территория, консьерж, подземный паркинг.'],
                ['rent', 'Сдаётся {rooms}-комн. квартира, {area} м²', 'Долгосрочная аренда. Залог один месяц. Можно с детьми, без животных.'],
                ['sale', 'Пентхаус {area} м² с террасой', 'Панорамные окна, дизайнерская отделка. Два машиноместа в цене.'],
            ],
            'house' => [
                ['sale', 'Коттедж {area} м² на участке {plot} сот.', 'Газ, вода, электричество. Баня, гараж на 2 авто. 25 км от МКАД.'],
                ['rent', 'Дом {area} м² для отдыха', 'Посуточно/помесячно. Охраняемый посёлок, лес рядом.'],
            ],
            'commercial' => [
                ['sale', 'Офис {area} м² в БЦ класса B+', 'Открытая планировка, кондиционирование. Высота потолков 3,2 м.'],
                ['rent', 'Торговое помещение {area} м²', 'Первый этаж, витрины, высокий трафик. Подходит под кафе или салон.'],
            ],
            'land' => [
                ['sale', 'Участок {plot} сот. ИЖС', 'Ровный рельеф, подъезд круглый год. Коммуникации по границе.'],
            ],
        ];

        $properties = [];
        $n = 0;
        foreach ($realtors as $realtorIndex => $realtor) {
            $count = 18 + ($realtorIndex % 4);
            for ($j = 0; $j < $count; $j++) {
                $n++;
                $cityName = $cityNames[($realtorIndex + $j) % count($cityNames)];
                $city = $cities[$cityName];
                $type = match (true) {
                    $j % 10 < 6 => 'apartment',
                    $j % 10 < 8 => 'house',
                    $j % 10 < 9 => 'commercial',
                    default => 'land',
                };
                $tplSet = $templates[$type];
                $tpl = $tplSet[$j % count($tplSet)];
                [$operation, $titleTpl, $descTpl] = $tpl;

                $rooms = match ($type) {
                    'apartment' => rand(1, 4),
                    'house' => rand(3, 7),
                    default => null,
                };
                $area = match ($type) {
                    'apartment' => rand(28, 145),
                    'house' => rand(90, 320),
                    'commercial' => rand(35, 450),
                    'land' => null,
                };
                $plot = $type === 'land' || $type === 'house' ? rand(6, 25) : null;
                $title = str_replace(
                    ['{rooms}', '{area}', '{plot}', '{year}'],
                    [(string) $rooms, (string) ($area ?? $plot), (string) $plot, (string) (2024 + ($j % 3))],
                    $titleTpl
                );
                $desc = str_replace(
                    ['{rooms}', '{area}', '{plot}', '{year}'],
                    [(string) $rooms, (string) ($area ?? $plot), (string) $plot, (string) (2024 + ($j % 3))],
                    $descTpl
                );
                $desc = $this->enrichDescription($desc, $type, $operation, $cityName, $rooms, $area);

                $statusKod = $statusPool[array_rand($statusPool)];
                $statusId = $statuses[$statusKod] ?? $statuses['active'];

                $street = $city['streets'][$j % count($city['streets'])];
                $building = rand(1, 120);
                $price = $this->priceFor($type, $operation, $area, $cityName);

                $lat = $city['lat'] + (mt_rand(-300, 300) / 10000);
                $lon = $city['lon'] + (mt_rand(-300, 300) / 10000);

                $data = [
                    'nazvanie' => $title,
                    'opisanie' => $desc . ' Объект №' . $n . '. Риэлтор: ' . trim($realtor->familia . ' ' . $realtor->imya) . '.',
                    'tip' => $type,
                    'operatsiya' => $operation,
                    'tsena' => $price,
                    'gorod_id' => $city['id'],
                    'adres_ulitsy' => $street . ', д. ' . $building,
                    'geo_shirota' => round($lat, 6),
                    'geo_dolgota' => round($lon, 6),
                    'ploshchad' => $area,
                    'komnaty' => $rooms,
                    'etazh' => match ($type) {
                        'apartment' => rand(1, 24),
                        'house' => rand(1, 2),
                        'commercial' => rand(1, 5),
                        default => null,
                    },
                    'vsego_etazhey' => match ($type) {
                        'apartment' => rand(5, 25),
                        'house' => rand(1, 3),
                        'commercial' => rand(3, 25),
                        default => null,
                    },
                    'polzovatel_id' => $realtor->id,
                    'status_obyavleniya_id' => $statusId,
                ];

                if ($statusKod === 'inactive' && $j % 2 === 0) {
                    $data['prichina_otkaza_mod'] = $rejectReasons[$j % count($rejectReasons)];
                }

                $properties[] = Property::create($data);
            }
        }

        PropertyStatus::forgetKodIdCache();

        return $properties;
    }

    /**
     * Заполняет komnaty / etazh / vsego_etazhey у объявлений, где поля пустые.
     */
    public function backfillPropertyCharacteristics(): int
    {
        $updated = 0;

        Property::query()->orderBy('id')->chunkById(50, function ($properties) use (&$updated) {
            foreach ($properties as $property) {
                $dirty = false;
                $type = $property->tip ?? 'apartment';

                if ($property->komnaty === null) {
                    $rooms = match ($type) {
                        'apartment' => rand(1, 4),
                        'house' => rand(3, 7),
                        default => null,
                    };
                    if ($rooms !== null) {
                        $property->komnaty = $rooms;
                        $dirty = true;
                    }
                }

                if ($property->etazh === null) {
                    $floor = match ($type) {
                        'apartment' => rand(1, 24),
                        'house' => rand(1, 2),
                        'commercial' => rand(1, 5),
                        default => null,
                    };
                    if ($floor !== null) {
                        $property->etazh = $floor;
                        $dirty = true;
                    }
                }

                if ($property->vsego_etazhey === null && $property->etazh !== null) {
                    $property->vsego_etazhey = match ($type) {
                        'apartment' => max((int) $property->etazh, rand(5, 25)),
                        'house' => max((int) $property->etazh, rand(1, 3)),
                        'commercial' => max((int) $property->etazh, rand(3, 25)),
                        default => null,
                    };
                    if ($property->vsego_etazhey !== null) {
                        $dirty = true;
                    }
                }

                if ($dirty) {
                    $property->save();
                    $updated++;
                }
            }
        });

        return $updated;
    }

    private function priceFor(string $type, string $operation, ?int $area, string $city): float
    {
        $base = match ($city) {
            'Москва' => 12_000_000,
            'Санкт-Петербург' => 8_500_000,
            'Казань' => 5_500_000,
            'Краснодар' => 6_000_000,
            default => 4_000_000,
        };

        $mult = match ($type) {
            'apartment' => 1.0,
            'house' => 1.8,
            'commercial' => 1.3,
            'land' => 0.4,
        };

        $areaFactor = max(1, ($area ?? 10) / 50);
        $price = $base * $mult * $areaFactor * (0.85 + mt_rand(0, 30) / 100);

        if ($operation === 'rent') {
            $price = round($price / 200, -2);
        }

        return round($price, 2);
    }

    /**
     * @param  list<Property>  $properties
     * @param  list<User>  $realtors
     * @param  list<User>  $clients
     * @param  array<string, int>  $contractStatuses
     */
    /**
     * @param  list<Property>  $properties
     */
    private function seedPropertyImages(array $properties): void
    {
        $bar = $this->command?->getOutput()?->createProgressBar(count($properties));
        $bar?->start();

        foreach ($properties as $index => $property) {
            $statusKod = PropertyStatus::kodFor((int) $property->status_obyavleniya_id);
            $count = match ($statusKod) {
                'active' => 4 + ($index % 2),
                'sold', 'rented' => 3,
                'pending_review' => 2 + ($index % 2),
                'draft' => $index % 3 === 0 ? 1 : 0,
                default => 1,
            };

            if ($count === 0) {
                $bar?->advance();
                continue;
            }

            $type = $property->tip ?? 'apartment';

            for ($order = 0; $order < $count; $order++) {
                $path = DemoImageFactory::propertyPhoto($type, (int) $property->id, $order);

                PropertyImage::create([
                    'nedvizhimost_id' => $property->id,
                    'put_k_izobrazheniyu' => $path,
                    'poryadok' => $order,
                ]);
            }

            $bar?->advance();
        }

        $bar?->finish();
        $this->command?->newLine();
    }

    /**
     * @param  list<User>  $realtors
     * @param  list<User>  $clients
     */
    private function seedUserAvatars(User $admin, array $realtors, array $clients): void
    {
        $admin->update(['avatar_polzovatela' => DemoImageFactory::avatar(0)]);

        foreach ($realtors as $i => $realtor) {
            $realtor->update(['avatar_polzovatela' => DemoImageFactory::avatar($i + 1)]);
        }

        foreach (array_slice($clients, 0, 20) as $i => $client) {
            if ($i % 2 === 0) {
                $client->update(['avatar_polzovatela' => DemoImageFactory::avatar(($i % 6) + 2)]);
            }
        }
    }

    private function enrichDescription(
        string $base,
        string $type,
        string $operation,
        string $city,
        ?int $rooms,
        ?int $area,
    ): string {
        $extras = [
            'Инфраструктура: магазины и остановки в шаговой доступности.',
            'Документы проверены юристом агентства.',
            'Возможен торг после просмотра.',
            'Показ по предварительной записи, ключи у риэлтора.',
            'Ипотека от банков-партнёров, помощь с одобрением.',
        ];

        $typeLine = match ($type) {
            'apartment' => sprintf('Город %s. %s-комн., площадь %d м².', $city, (string) $rooms, (int) $area),
            'house' => sprintf('Город %s. Площадь дома %d м², участок ухожен.', $city, (int) ($area ?? 0)),
            'commercial' => sprintf('Город %s. Помещение %d м², отдельный вход.', $city, (int) ($area ?? 0)),
            default => sprintf('Город %s. Участок под ИЖС или инвестиции.', $city),
        };

        $opLine = $operation === 'rent'
            ? 'Условия аренды: коммунальные по счётчикам, залог обсуждается.'
            : 'Продажа от собственника через агентство, без скрытых комиссий.';

        return $base . "\n\n" . $typeLine . ' ' . $opLine . ' ' . $extras[array_rand($extras)];
    }

    private function seedContracts(array $properties, array $realtors, array $clients, array $contractStatuses): void
    {
        $activeProps = array_values(array_filter(
            $properties,
            fn (Property $p) => in_array(PropertyStatus::kodFor((int) $p->status_obyavleniya_id), ['active', 'sold', 'rented'], true)
        ));

        if ($activeProps === []) {
            return;
        }

        $statusSequence = ['pending', 'pending', 'active', 'active', 'completed', 'completed', 'completed', 'draft', 'cancelled'];
        $notes = [
            'Клиент согласовал условия по телефону.',
            'Предварительный задаток внесён на эскроу-счёт.',
            'Согласована дата выхода на сделку в Росреестре.',
            'Арендатор предоставил справки с работы.',
            null,
        ];

        for ($i = 0; $i < 55; $i++) {
            $property = $activeProps[$i % count($activeProps)];
            $realtor = $realtors[$i % count($realtors)];
            $client = $clients[$i % count($clients)];
            $ownerId = (int) $property->polzovatel_id;

            $statusKod = $statusSequence[$i % count($statusSequence)];
            $isRent = $property->operatsiya === 'rent';
            $createdByRealtor = $i % 4 !== 2;

            $approvedAt = in_array($statusKod, ['active', 'completed'], true)
                ? now()->subDays(rand(1, 30))
                : null;

            Contract::create([
                'nedvizhimost_id' => $property->id,
                'vladelets_id' => $ownerId,
                'pokupatel_id' => $client->id,
                'oplata_status_id' => PaymentStatus::idFor('none'),
                'rieltor_id' => $realtor->id,
                'sozdal_kak' => $createdByRealtor ? 'realtor' : 'client',
                'sozdal_storona' => $createdByRealtor ? null : ($i % 2 === 0 ? 'buyer' : 'owner'),
                'tip' => $property->operatsiya,
                'tsena' => $property->tsena,
                'data_nachala' => now()->subDays(rand(5, 180))->toDateString(),
                'data_okonchaniya' => $isRent ? now()->addMonths(rand(6, 24))->toDateString() : null,
                'status_dogovora_id' => $contractStatuses[$statusKod] ?? $contractStatuses['draft'],
                'primechaniya' => $notes[$i % count($notes)],
                'podtverzhden_vladelets_at' => $approvedAt,
                'podtverzhden_pokupatel_at' => $approvedAt && $i % 5 !== 0 ? $approvedAt->copy()->addHours(2) : null,
                'podtverzhden_rieltor_at' => $approvedAt && !$createdByRealtor ? $approvedAt->copy()->addHours(5) : ($createdByRealtor ? $approvedAt : null),
                'ozhidaet_podtverzhdeniya' => $statusKod === 'pending',
            ]);
        }
    }

    /**
     * Заполнить персональные данные для части клиентов.
     *
     * @param  list<User>  $clients
     */
    private function seedPersonalData(array $clients): void
    {
        foreach ($clients as $i => $client) {
            // примерно у половины клиентов будут заполнены данные паспорта
            if ($i % 2 !== 0) {
                continue;
            }

            UserPersonalData::updateOrCreate(
                ['polzovatel_id' => (int) $client->id],
                [
                    'pasport_seriya_nomer' => sprintf('%04d %06d', 1000 + ($i % 9000), 100000 + ($i * 791) % 900000),
                    'pasport_kem_vydan' => 'УМВД России по г. Москве',
                    'pasport_data_vydachi' => now()->subYears(5)->subDays($i % 365)->toDateString(),
                    'inn' => (string) (1000000000 + ($i * 37) % 9000000000),
                    'snils' => sprintf('%03d-%03d-%03d %02d', 100 + ($i % 900), 100 + (($i + 7) % 900), 100 + (($i + 13) % 900), 10 + ($i % 89)),
                ]
            );
        }
    }

    /**
     * Профильные документы: паспорт и ИНН/СНИЛС (не привязаны к объекту).
     *
     * @param  list<User>  $clients
     */
    private function seedProfileDocuments(array $clients): void
    {
        foreach (array_slice($clients, 0, 18) as $i => $client) {
            // Паспорт: у большинства verified, у части pending/rejected
            $passportStatus = match ($i % 6) {
                0 => 'pending',
                1 => 'rejected',
                default => 'verified',
            };

            $passportPath = DemoImageFactory::avatar(($i % 6) + 1);
            UserDocument::updateOrCreate(
                [
                    'polzovatel_id' => (int) $client->id,
                    'nedvizhimost_id' => null,
                    'tip' => 'passport',
                ],
                [
                    'nazvanie' => 'Паспорт (скан)',
                    'put_fajla' => $passportPath,
                    'status' => $passportStatus,
                    'kommentariy_mod' => $passportStatus === 'rejected' ? 'Снимок размытый — загрузите фото без бликов.' : null,
                    'provereno_at' => $passportStatus === 'verified' ? now()->subDays(rand(1, 20)) : null,
                ]
            );

            // ИНН/СНИЛС — реже
            if ($i % 3 === 0) {
                $innPath = DemoImageFactory::avatar(($i % 6) + 2);
                UserDocument::updateOrCreate(
                    [
                        'polzovatel_id' => (int) $client->id,
                        'nedvizhimost_id' => null,
                        'tip' => 'inn',
                    ],
                    [
                        'nazvanie' => 'ИНН / СНИЛС',
                        'put_fajla' => $innPath,
                        'status' => $i % 9 === 0 ? 'pending' : 'verified',
                        'provereno_at' => $i % 9 === 0 ? null : now()->subDays(rand(1, 30)),
                    ]
                );
            }
        }
    }

    /**
     * Документы на объект: создаём часть verified/pending/rejected, чтобы чек-лист и отчёт были «живыми».
     *
     * @param  list<Property>  $properties
     */
    private function seedPropertyDocuments(array $properties): void
    {
        foreach (array_slice($properties, 0, 35) as $i => $property) {
            $required = PropertyDocumentRules::requiredForType(
                $property->tip ?? 'apartment',
                $property->operatsiya ?? 'sale',
            );

            // Для первых объектов — почти всё verified, дальше — часть pending/rejected
            foreach ($required as $stepIndex => $tip) {
                // паспорт чаще закрывается профилем; на объект его кладём редко
                if ($tip === 'passport' && $i % 5 !== 0) {
                    continue;
                }

                $status = match (true) {
                    $i < 10 => 'verified',
                    $i < 18 => ($stepIndex === 0 ? 'verified' : 'pending'),
                    default => ($stepIndex === 0 ? 'verified' : ($stepIndex % 3 === 0 ? 'rejected' : 'pending')),
                };

                // В демо используем имеющийся медиапак: копируем фото объекта как «документ»
                $path = DemoImageFactory::propertyPhoto($property->tip ?? 'apartment', (int) $property->id, $stepIndex);

                UserDocument::updateOrCreate(
                    [
                        'polzovatel_id' => (int) ($property->polzovatel_id ?? 0),
                        'nedvizhimost_id' => (int) $property->id,
                        'tip' => $tip,
                    ],
                    [
                        'nazvanie' => PropertyDocumentRules::allTipLabels()[$tip] ?? $tip,
                        'put_fajla' => $path,
                        'status' => $status,
                        'kommentariy_mod' => $status === 'rejected' ? 'Нужен документ целиком: видны не все страницы/печать.' : null,
                        'provereno_at' => $status === 'verified' ? now()->subDays(rand(1, 25)) : null,
                    ]
                );
            }
        }
    }

    /**
     * Заявки по объектам (Lean): чтобы в отчёте были реальные счётчики.
     *
     * @param  list<User>  $clients
     * @param  list<Property>  $properties
     */
    private function seedInquiries(array $clients, array $properties): void
    {
        // Если заявок уже много (ручное тестирование) — не плодим бесконечно
        if (PropertyInquiry::count() > 250) {
            return;
        }

        $active = array_values(array_filter(
            $properties,
            fn (Property $p) => ($p->status ?? '') === 'active'
        ));
        if ($active === []) {
            $active = array_slice($properties, 0, 20);
        }

        $statuses = ['new', 'new', 'processed', 'processed', 'closed'];
        $comments = [
            'Интересует торг и срок выхода на сделку.',
            'Можно ли посмотреть вечером после 19:00?',
            'Нужна ипотека, есть одобрение банка.',
            'Готов(а) к просмотру в выходные.',
            null,
        ];

        for ($i = 0; $i < 80; $i++) {
            $client = $clients[$i % count($clients)];
            $property = $active[$i % count($active)];

            PropertyInquiry::create([
                'nedvizhimost_id' => (int) $property->id,
                'polzovatel_id' => (int) $client->id,
                'imya' => trim($client->imya . ' ' . $client->familia),
                'telefon' => $client->telefon,
                'email' => $client->email_polzovatela,
                'kommentariy' => $comments[$i % count($comments)],
                'status' => $statuses[$i % count($statuses)],
            ]);
        }
    }

    /**
     * @param  list<Property>  $properties
     */
    private function seedAuditSamples(array $properties, User $admin): void
    {
        $samples = array_slice($properties, 0, min(12, count($properties)));

        foreach ($samples as $i => $property) {
            ZhurnalIzmeneniy::zapisat(
                $admin->id,
                Property::class,
                $property->id,
                'obnovleno',
                [
                    [
                        'polya' => 'tsena',
                        'bilo' => (string) round((float) $property->tsena * 1.05, 2),
                        'stalo' => (string) $property->tsena,
                    ],
                    [
                        'polya' => 'status_obyavleniya_id',
                        'bilo' => (string) (PropertyStatus::idFor('draft') ?? ''),
                        'stalo' => (string) ($property->status_obyavleniya_id ?? ''),
                    ],
                ],
                'Корректировка перед публикацией'
            );

            if ($i % 3 === 0) {
                ZhurnalIzmeneniy::zapisat(
                    $property->polzovatel_id,
                    Property::class,
                    $property->id,
                    'obnovleno',
                    [[
                        'polya' => 'opisanie',
                        'bilo' => 'Черновик описания',
                        'stalo' => mb_substr((string) $property->opisanie, 0, 120).'…',
                    ]],
                    null
                );
            }
        }
    }

    /**
     * @param  list<User>  $clients
     * @param  list<Property>  $properties
     */
    private function seedFavorites(array $clients, array $properties): void
    {
        $activeIds = [];
        foreach ($properties as $p) {
            if (PropertyStatus::kodFor((int) $p->status_obyavleniya_id) === 'active') {
                $activeIds[] = $p->id;
            }
        }

        if ($activeIds === []) {
            return;
        }

        $pairs = [];
        foreach (array_slice($clients, 0, 25) as $ci => $client) {
            $take = rand(2, 5);
            $keys = array_rand($activeIds, min($take, count($activeIds)));
            $keys = is_array($keys) ? $keys : [$keys];
            foreach ($keys as $key) {
                $pairs[$client->id . ':' . $activeIds[$key]] = [
                    'polzovatel_id' => $client->id,
                    'nedvizhimost_id' => $activeIds[$key],
                    'sozdano_at' => now()->subDays(rand(1, 60)),
                    'obnovleno_at' => now(),
                ];
            }
        }

        foreach ($pairs as $row) {
            Favorite::firstOrCreate(
                [
                    'polzovatel_id' => $row['polzovatel_id'],
                    'nedvizhimost_id' => $row['nedvizhimost_id'],
                ],
                [
                    'sozdano_at' => $row['sozdano_at'],
                    'obnovleno_at' => $row['obnovleno_at'],
                ]
            );
        }
    }

    /**
     * @param  list<User>  $realtors
     * @param  list<User>  $clients
     * @param  list<Property>  $properties
     */
    private function seedRealtorCrm(array $realtors, array $clients, array $properties): void
    {
        if ($realtors === [] || $clients === []) {
            return;
        }

        $realtor = $realtors[0];
        $active = array_values(array_filter($properties, fn ($p) => ($p->status ?? '') === 'active'));
        if ($active === []) {
            $active = array_slice($properties, 0, 5);
        }

        foreach (array_slice($clients, 0, 5) as $i => $client) {
            \App\Models\RealtorClient::firstOrCreate(
                ['rieltor_id' => $realtor->id, 'klient_id' => $client->id],
                [
                    'status' => ['new', 'in_progress', 'in_progress', 'deal', 'lost'][$i] ?? 'new',
                    'zametki' => 'Ищет ' . ($i % 2 === 0 ? 'квартиру 2–3 комнаты' : 'дом в Подмосковье'),
                ]
            );
        }

        \App\Models\RealtorTask::create([
            'rieltor_id' => $realtor->id,
            'klient_id' => $clients[0]->id,
            'nazvanie' => 'Перезвонить по ипотеке',
            'tip' => 'call',
            'srok_do' => now()->addDays(1),
        ]);

        if (isset($active[0], $clients[1])) {
            \App\Models\PropertyShowing::create([
                'rieltor_id' => $realtor->id,
                'klient_id' => $clients[1]->id,
                'nedvizhimost_id' => $active[0]->id,
                'naznacheno_na' => now()->addDays(2)->setTime(14, 0),
                'zametki' => 'Показ с ипотечным консультантом',
            ]);
        }

        $collection = \App\Models\PropertyCollection::create([
            'rieltor_id' => $realtor->id,
            'klient_id' => $clients[0]->id,
            'nazvanie' => 'Подборка для семьи Ивановых',
            'token' => \App\Models\PropertyCollection::generateToken(),
            'kommentariy' => '3 варианта в вашем бюджете — подборка риэлтора',
        ]);

        foreach (array_slice($active, 0, 3) as $order => $prop) {
            \App\Models\CollectionProperty::create([
                'podborka_id' => $collection->id,
                'nedvizhimost_id' => $prop->id,
                'poryadok' => $order,
            ]);
        }
    }

    /** Дополняет БД: дома, собственники, документы с реквизитами, УКЭП. */
    public function seedTzFeatureDataIfNeeded(): void
    {
        $clientCount = User::query()->whereHas('roleRelation', fn ($q) => $q->where('kod', 'client'))->count();
        $personalFilled = UserPersonalData::count();
        $docsWithJson = UserDocument::whereNotNull('dannye_json')->count();

        $needsFull = env('DEMO_FULL_ENRICH', false)
            || Property::where('tip', 'house')->whereNull('tip_doma')->exists()
            || (Property::count() > 0 && PropertyOwner::count() === 0)
            || (Contract::count() > 0 && ContractSeller::count() === 0)
            || PropertySelectionRequest::count() === 0
            || PropertyInfoRequest::count() === 0
            || ResponseTemplate::count() === 0
            || ($clientCount > 0 && $personalFilled < $clientCount)
            || $docsWithJson < max(10, (int) ($clientCount / 2));

        if ($needsFull) {
            $this->call(ComprehensiveDemoSeeder::class);
        }
    }

    /** @param  array<string, int>  $weights  @return list<string> */
    private function weightedPool(array $weights): array
    {
        $pool = [];
        foreach ($weights as $kod => $w) {
            for ($i = 0; $i < $w; $i++) {
                $pool[] = $kod;
            }
        }

        return $pool;
    }
}
