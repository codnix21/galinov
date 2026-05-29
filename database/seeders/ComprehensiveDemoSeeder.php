<?php

namespace Database\Seeders;

use App\Models\Contract;
use App\Models\ContractSeller;
use App\Models\ContractStatus;
use App\Models\Property;
use App\Models\PropertyOwner;
use App\Models\PropertyStatus;
use App\Models\User;
use App\Models\UserDocument;
use App\Models\UserPersonalData;
use App\Services\ContractEcpService;
use App\Services\PropertyOwnersService;
use App\Support\DemoDocumentData;
use App\Support\DemoImageFactory;
use App\Support\DocumentDataFields;
use App\Support\PropertyDocumentRules;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Полное демо: паспорта, реквизиты документов, доли собственников, УКЭП, продавцы в договорах.
 *
 * php artisan db:seed --class=ComprehensiveDemoSeeder
 * Полный сброс: php artisan migrate:fresh --seed --force
 */
class ComprehensiveDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (Property::count() === 0) {
            $this->command?->warn('Сначала: php artisan db:seed --class=DemoDataSeeder');

            return;
        }

        $stats = DB::transaction(function () {
            $this->call(ExtendedFeaturesSeeder::class);

            return [
                'personal' => $this->seedAllPersonalData(),
                'profile_docs' => $this->seedAllProfileDocuments(),
                'property_docs' => $this->seedAllPropertyDocuments(),
                'owners' => $this->seedShowcaseOwners(),
                'contract_sellers' => $this->seedAllContractSellers(),
                'ecp' => $this->seedContractEcpSignatures(),
            ];
        });

        $this->command?->info(sprintf(
            'Полное демо: персональные данные (%d), профильные документы (%d), документы объектов (%d), собственники (%d), продавцы в договорах (%d), УКЭП (%d).',
            $stats['personal'],
            $stats['profile_docs'],
            $stats['property_docs'],
            $stats['owners'],
            $stats['contract_sellers'],
            $stats['ecp'],
        ));
        $this->command?->info('Пример: дом с 3 собственниками (5% + 5% + 90%) — ищите дом на модерации с несколькими владельцами.');
    }

    public function seedAllPersonalData(): int
    {
        $count = 0;
        $users = User::query()
            ->whereHas('roleRelation', fn ($q) => $q->whereIn('kod', ['client', 'realtor']))
            ->orderBy('id')
            ->get();

        foreach ($users as $i => $user) {
            $data = DemoDocumentData::personalDataForUser($user, $i);
            UserPersonalData::updateOrCreate(
                ['polzovatel_id' => (int) $user->id],
                $data,
            );
            $count++;
        }

        return $count;
    }

    public function seedAllProfileDocuments(): int
    {
        $count = 0;
        $clients = User::query()
            ->whereHas('roleRelation', fn ($q) => $q->where('kod', 'client'))
            ->orderBy('id')
            ->get();

        foreach ($clients as $i => $client) {
            $passportStatus = match ($i % 8) {
                0 => 'pending',
                1 => 'rejected',
                default => 'verified',
            };

            UserDocument::updateOrCreate(
                [
                    'polzovatel_id' => (int) $client->id,
                    'nedvizhimost_id' => null,
                    'tip' => 'passport',
                ],
                [
                    'nazvanie' => 'Паспорт (скан)',
                    'put_fajla' => DemoImageFactory::avatar(($i % 6) + 1),
                    'status' => $passportStatus,
                    'dannye_json' => null,
                    'kommentariy_mod' => $passportStatus === 'rejected'
                        ? 'Снимок размытый — загрузите фото без бликов.'
                        : DocumentDataFields::summaryForComment('passport', DemoDocumentData::personalDataForUser($client, $i)),
                    'provereno_at' => $passportStatus === 'verified' ? now()->subDays(rand(1, 30)) : null,
                    'vneshniy_id' => $passportStatus === 'verified' ? 'DEMO-PASS-'.$client->id : null,
                    'vneshniy_status' => $passportStatus === 'verified' ? 'verified' : null,
                    'vneshniy_provereno_at' => $passportStatus === 'verified' ? now()->subDays(rand(1, 20)) : null,
                ],
            );
            $count++;

            $innData = DemoDocumentData::sampleForTip('inn', new Property(['id' => $i]), $i);
            UserDocument::updateOrCreate(
                [
                    'polzovatel_id' => (int) $client->id,
                    'nedvizhimost_id' => null,
                    'tip' => 'inn',
                ],
                [
                    'nazvanie' => 'ИНН / СНИЛС',
                    'put_fajla' => DemoImageFactory::avatar(($i % 6) + 2),
                    'status' => $i % 10 === 0 ? 'pending' : 'verified',
                    'dannye_json' => $innData,
                    'kommentariy_mod' => DocumentDataFields::summaryForComment('inn', $innData),
                    'provereno_at' => $i % 10 === 0 ? null : now()->subDays(rand(1, 25)),
                ],
            );
            $count++;
        }

        return $count;
    }

    public function seedAllPropertyDocuments(): int
    {
        $count = 0;

        $properties = Property::query()->orderBy('id')->get();

        foreach ($properties as $i => $property) {
            $required = PropertyDocumentRules::requiredForType(
                $property->tip ?? 'apartment',
                $property->operatsiya ?? 'sale',
            );

            $egrnTip = PropertyDocumentRules::egrnTipForProperty($property);
            if ($egrnTip && in_array($egrnTip, $required, true)) {
                $property->update([
                    'kadastrovy_nomer' => DemoDocumentData::cadastralForProperty($property),
                ]);
            }

            foreach ($required as $stepIndex => $tip) {
                if ($tip === 'passport' && $i % 3 !== 0) {
                    continue;
                }

                $status = $i < 15 ? 'verified' : ($stepIndex === 0 ? 'verified' : ($stepIndex % 2 === 0 ? 'pending' : 'verified'));
                $dannye = DemoDocumentData::sampleForTip($tip, $property, $stepIndex);
                $path = DemoImageFactory::propertyPhoto($property->tip ?? 'apartment', (int) $property->id, $stepIndex);

                UserDocument::updateOrCreate(
                    [
                        'polzovatel_id' => (int) ($property->polzovatel_id ?? 0),
                        'nedvizhimost_id' => (int) $property->id,
                        'tip' => $tip,
                    ],
                    [
                        'tip_obekta' => $property->tip,
                        'nazvanie' => PropertyDocumentRules::allTipLabels()[$tip] ?? $tip,
                        'put_fajla' => $path,
                        'status' => $status,
                        'dannye_json' => $dannye,
                        'kommentariy_mod' => $status === 'rejected'
                            ? 'Нужен документ целиком.'
                            : ('[RosreestrDemo] '.DocumentDataFields::summaryForComment($tip, $dannye)),
                        'provereno_at' => $status === 'verified' ? now()->subDays(rand(1, 20)) : null,
                        'vneshniy_id' => $status === 'verified' ? 'DEMO-'.strtoupper($tip).'-'.$property->id : null,
                        'vneshniy_status' => $status === 'verified' ? 'verified' : null,
                        'vneshniy_provereno_at' => $status === 'verified' ? now()->subDays(rand(1, 15)) : null,
                    ],
                );
                $count++;
            }
        }

        return $count;
    }

    /**
     * Демонстрационные доли: 3 собственника 5+5+90, 2 по 50%, остальные дома — варианты.
     */
    public function seedShowcaseOwners(): int
    {
        $clients = User::query()
            ->whereHas('roleRelation', fn ($q) => $q->where('kod', 'client'))
            ->orderBy('id')
            ->limit(30)
            ->get();

        if ($clients->count() < 3) {
            return 0;
        }

        $created = 0;
        $houses = Property::query()->where('tip', 'house')->orderBy('id')->get();

        foreach ($houses as $i => $house) {
            PropertyOwner::where('nedvizhimost_id', $house->id)->delete();

            if ($i === 0) {
                $ids = [$clients[0]->id, $clients[1]->id, $clients[2]->id];
                $shares = [5.0, 5.0, 90.0];
                foreach ($shares as $j => $share) {
                    PropertyOwner::create([
                        'nedvizhimost_id' => $house->id,
                        'polzovatel_id' => $ids[$j],
                        'dolya_procent' => $share,
                        'osnovnoy' => $j === 2,
                        'poryadok' => $j,
                    ]);
                    $created++;
                }
                $house->update(['polzovatel_id' => $ids[2]]);
                $pendingId = PropertyStatus::idFor('pending_review');
                if ($pendingId) {
                    $house->update([
                        'status_obyavleniya_id' => $pendingId,
                        'status_obyavleniya' => 'pending_review',
                    ]);
                }
                continue;
            }

            if ($i === 1) {
                $a = $clients[3]->id;
                $b = $clients[4]->id;
                foreach ([[$a, 50.0, true], [$b, 50.0, false]] as $j => [$uid, $share, $main]) {
                    PropertyOwner::create([
                        'nedvizhimost_id' => $house->id,
                        'polzovatel_id' => $uid,
                        'dolya_procent' => $share,
                        'osnovnoy' => $main,
                        'poryadok' => $j,
                    ]);
                    $created++;
                }
                $house->update(['polzovatel_id' => $a]);
                continue;
            }

            PropertyOwner::create([
                'nedvizhimost_id' => $house->id,
                'polzovatel_id' => $house->polzovatel_id,
                'dolya_procent' => 100,
                'osnovnoy' => true,
                'poryadok' => 0,
            ]);
            $created++;
        }

        Property::query()->where('tip', '!=', 'house')->orderBy('id')->each(function (Property $p) {
            if (!PropertyOwner::where('nedvizhimost_id', $p->id)->exists()) {
                PropertyOwnersService::ensureDefaultOwner($p);
            }
        });

        return $created;
    }

    public function seedAllContractSellers(): int
    {
        $count = 0;
        Contract::query()->with('property.owners.user')->orderBy('id')->each(function (Contract $contract) use (&$count) {
            ContractSeller::where('dogovor_id', $contract->id)->delete();
            PropertyOwnersService::copySellersToContract($contract, $contract->property);
            $count++;
        });

        return $count;
    }

    public function seedContractEcpSignatures(): int
    {
        $activeKod = ContractStatus::where('kod', 'active')->value('id');
        $completedKod = ContractStatus::where('kod', 'completed')->value('id');
        $pendingKod = ContractStatus::where('kod', 'pending')->value('id');

        $ecp = app(ContractEcpService::class);
        $count = 0;

        Contract::query()
            ->with(['owner', 'buyer', 'realtor'])
            ->whereIn('status_dogovora_id', array_filter([$activeKod, $completedKod]))
            ->orderBy('id')
            ->each(function (Contract $contract) use ($ecp, &$count) {
                $ecp->autoSignOwnerAndRealtor($contract);
                if ($contract->buyer && !$contract->ecp_podpis_pokupatel_at) {
                    $ecp->signAsBuyer($contract, $contract->buyer);
                }
                $count++;
            });

        Contract::query()
            ->with(['owner', 'buyer', 'realtor'])
            ->when($pendingKod, fn ($q) => $q->where('status_dogovora_id', $pendingKod))
            ->orderBy('id')
            ->limit(15)
            ->each(function (Contract $contract) use ($ecp) {
                $ecp->autoSignOwnerAndRealtor($contract);
            });

        return $count;
    }
}
