<?php

namespace Database\Seeders;

use App\Models\Contract;
use App\Models\ContractSeller;
use App\Models\Property;
use App\Models\PropertyInfoRequest;
use App\Models\PropertyInfoRequestMessage;
use App\Models\PropertyOwner;
use App\Models\PropertySelectionRequest;
use App\Models\PropertyStatus;
use App\Models\User;
use App\Services\PropertyOwnersService;
use App\Support\PropertyInfoRequestTypes;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Доп. наполнение: параметры домов, собственники, продавцы в договорах, заявки на подбор и доп. информацию.
 *
 * php artisan db:seed --class=ExtendedFeaturesSeeder
 */
class ExtendedFeaturesSeeder extends Seeder
{
    public function run(): void
    {
        if (Property::count() === 0) {
            $this->command?->warn('Сначала запустите DemoDataSeeder: php artisan db:seed --class=DemoDataSeeder');

            return;
        }

        [$housesUpdated, $ownersCreated, $sellersCreated, $selections, $infoRequests] = DB::transaction(function () {
            return [
                $this->seedHouseAttributes(),
                $this->seedPropertyOwners(),
                $this->seedContractSellers(),
                $this->seedSelectionRequests(),
                $this->seedInfoRequests(),
            ];
        });

        $this->command?->info(sprintf(
            'Доп. наполнение: дома обновлены (%d), собственников (%d), продавцов в договорах (%d), заявок на подбор (%d), запросов доп. инфо (%d).',
            $housesUpdated,
            $ownersCreated,
            $sellersCreated,
            $selections,
            $infoRequests,
        ));
    }

    public function seedHouseAttributes(): int
    {
        $tipDoma = ['kirpichny', 'panelny', 'derevyanny', 'monolitny', 'karkasny'];
        $count = 0;

        Property::query()->where('tip', 'house')->orderBy('id')->each(function (Property $p, int $i) use ($tipDoma, &$count) {
            $p->update([
                'tip_doma' => $tipDoma[$i % count($tipDoma)],
                'est_tsokol' => $i % 3 === 0,
                'ploshchad_uchastka' => round(8 + ($i % 22) + ($i % 7) * 0.5, 2),
                'garazh' => $i % 2 === 0,
                'parking' => $i % 4 !== 3,
                'internet' => true,
                'otoplenie' => $i % 5 !== 4,
                'kanalizatsiya' => $i % 3 !== 1,
                'vodosnabzhenie' => true,
                'gaz' => $i % 2 === 0,
                'banya' => $i % 4 === 0,
                'bassein' => $i % 8 === 0,
                'okhrana' => $i % 6 === 0,
                'zabor' => $i % 3 !== 2,
            ]);
            $count++;
        });

        return $count;
    }

    public function seedPropertyOwners(): int
    {
        if (PropertyOwner::count() > 20) {
            return 0;
        }

        $clients = User::query()
            ->whereHas('roleRelation', fn ($q) => $q->where('kod', 'client'))
            ->limit(30)
            ->get();

        if ($clients->count() < 3) {
            return 0;
        }

        $houses = Property::query()->where('tip', 'house')->orderBy('id')->limit(25)->get();
        $created = 0;

        foreach ($houses as $i => $house) {
            PropertyOwner::where('nedvizhimost_id', $house->id)->delete();

            if ($i % 5 === 0) {
                PropertyOwner::create([
                    'nedvizhimost_id' => $house->id,
                    'polzovatel_id' => $house->polzovatel_id,
                    'dolya_procent' => 100,
                    'osnovnoy' => true,
                    'poryadok' => 0,
                ]);
                $created++;

                continue;
            }

            $shares = match ($i % 4) {
                1 => [5.0, 5.0, 90.0],
                2 => [50.0, 50.0],
                3 => [33.33, 33.33, 33.34],
                default => [25.0, 75.0],
            };

            $ownerClients = $clients->where('id', '!=', $house->polzovatel_id)->values();
            if ($ownerClients->count() < count($shares)) {
                continue;
            }

            foreach ($shares as $j => $share) {
                PropertyOwner::create([
                    'nedvizhimost_id' => $house->id,
                    'polzovatel_id' => $ownerClients[$j]->id,
                    'dolya_procent' => $share,
                    'osnovnoy' => $j === 0,
                    'poryadok' => $j,
                ]);
                $created++;
            }

            $main = PropertyOwner::where('nedvizhimost_id', $house->id)->where('osnovnoy', true)->first();
            if ($main) {
                $house->update(['polzovatel_id' => $main->polzovatel_id]);
            }
        }

        Property::query()->where('tip', '!=', 'house')->orderBy('id')->limit(50)->each(function (Property $p) {
            if (PropertyOwner::where('nedvizhimost_id', $p->id)->exists()) {
                return;
            }
            PropertyOwnersService::ensureDefaultOwner($p);
        });

        return $created;
    }

    public function seedContractSellers(): int
    {
        $count = 0;
        Contract::query()->with('property.owners.user')->orderBy('id')->chunk(20, function ($contracts) use (&$count) {
            foreach ($contracts as $contract) {
                if (ContractSeller::where('dogovor_id', $contract->id)->exists()) {
                    continue;
                }
                PropertyOwnersService::copySellersToContract($contract, $contract->property);
                $count++;
            }
        });

        return $count;
    }

    public function seedSelectionRequests(): int
    {
        if (PropertySelectionRequest::count() > 10) {
            return 0;
        }

        $clients = User::query()->whereHas('roleRelation', fn ($q) => $q->where('kod', 'client'))->limit(15)->get();
        $filtersSamples = [
            ['type' => 'house', 'operation' => 'sale', 'min_price' => 15000000, 'max_price' => 35000000, 'garazh' => 1],
            ['type' => 'apartment', 'operation' => 'sale', 'city_id' => 1, 'min_rooms' => 2, 'max_rooms' => 3],
            ['type' => 'house', 'operation' => 'sale', 'tip_doma' => 'kirpichny', 'min_ploshchad_uchastka' => 10],
            ['type' => 'apartment', 'operation' => 'rent', 'max_price' => 80000],
        ];

        $created = 0;
        for ($i = 0; $i < 20; $i++) {
            $client = $clients[$i % max(1, $clients->count())];
            PropertySelectionRequest::create([
                'polzovatel_id' => $client->id,
                'imya' => trim($client->imya.' '.$client->familia),
                'telefon' => $client->telefon,
                'email' => $client->email_polzovatela,
                'kommentariy' => [
                    'Ищем вариант ближе к метро, готовы к ипотеке.',
                    'Нужен дом с гаражом и участком от 12 соток.',
                    'Семья из 4 человек, бюджет обсуждаем.',
                    'Срочно — переезд через 2 месяца.',
                ][$i % 4],
                'filtry' => $filtersSamples[$i % count($filtersSamples)],
                'status' => ['new', 'new', 'processed'][$i % 3],
                'istochnik' => $i % 3 === 0 ? 'form' : 'catalog',
            ]);
            $created++;
        }

        return $created;
    }

    public function seedInfoRequests(): int
    {
        if (PropertyInfoRequest::count() > 15) {
            return 0;
        }

        $activeId = PropertyStatus::idFor('active');
        $properties = Property::query()
            ->when($activeId, fn ($q) => $q->where('status_obyavleniya_id', $activeId))
            ->limit(30)
            ->get();

        $clients = User::query()->whereHas('roleRelation', fn ($q) => $q->where('kod', 'client'))->limit(12)->get();
        $staff = User::query()->whereHas('roleRelation', fn ($q) => $q->whereIn('kod', ['realtor', 'admin']))->first();

        $tips = PropertyInfoRequestTypes::keys();
        $clientTexts = [
            'Подскажите, сколько лет собственник владеет объектом?',
            'Есть ли обременения или ипотека?',
            'Нужно подтверждение документов перед сделкой.',
            'Требуется ли согласие супруга на продажу?',
        ];
        $staffReplies = [
            'Собственник владеет с 2018 года, выписка ЕГРН без обременений.',
            'Ипотека погашена, справка из банка приложена к документам.',
            'Документы проверены юристом агентства, всё в порядке.',
            'Согласие супруги получено, скан в личном кабинете продавца.',
        ];

        $created = 0;
        for ($i = 0; $i < min(25, $properties->count()); $i++) {
            $property = $properties[$i];
            $client = $clients[$i % max(1, $clients->count())];
            if ((int) $client->id === (int) $property->polzovatel_id) {
                continue;
            }

            $tip = $tips[$i % count($tips)];
            $status = $i % 3 === 0 ? 'open' : 'answered';

            $req = PropertyInfoRequest::create([
                'nedvizhimost_id' => $property->id,
                'polzovatel_id' => $client->id,
                'tip' => $tip,
                'status' => $status,
                'sozdano_at' => now()->subDays(rand(1, 14)),
            ]);

            PropertyInfoRequestMessage::create([
                'zapros_id' => $req->id,
                'polzovatel_id' => $client->id,
                'ot_kogo' => 'client',
                'tekst' => $clientTexts[$i % count($clientTexts)],
                'sozdano_at' => $req->sozdano_at,
            ]);

            if ($status === 'answered' && $staff) {
                PropertyInfoRequestMessage::create([
                    'zapros_id' => $req->id,
                    'polzovatel_id' => $staff->id,
                    'ot_kogo' => 'staff',
                    'tekst' => $staffReplies[$i % count($staffReplies)],
                    'sozdano_at' => $req->sozdano_at->copy()->addHours(rand(2, 48)),
                ]);
            }

            $created++;
        }

        return $created;
    }
}
