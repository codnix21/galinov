<?php

namespace Database\Seeders;

use App\Models\Contract;
use App\Models\ContractReview;
use App\Models\ContractStatus;
use App\Models\ContractTemplate;
use App\Models\Property;
use App\Models\PropertyInquiry;
use App\Models\PropertySelectionRequest;
use App\Models\PropertyStatus;
use App\Models\RentPayment;
use App\Models\ResponseTemplate;
use App\Models\Role;
use App\Models\SavedSearch;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\RentScheduleService;
use App\Support\InquirySla;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Наполнение для расширенного функционала платформы (CRM, 152-ФЗ, аналитика, шаблоны и т.д.).
 *
 * php artisan db:seed --class=PlatformFeaturesDemoSeeder
 */
class PlatformFeaturesDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (Property::count() === 0) {
            $this->command?->warn('Сначала: php artisan migrate:fresh --seed');

            return;
        }

        $stats = DB::transaction(function () {
            return [
                'settings' => $this->seedSystemSettings(),
                'templates' => $this->seedResponseTemplates(),
                'contract_tpl' => $this->seedContractTemplates(),
                'reviews' => $this->seedContractReviews(),
                'rent' => $this->seedRentSchedules(),
                'cadastral' => $this->seedCadastralDuplicates(),
                'saved' => $this->seedSavedSearches(),
                'leads' => $this->seedLeadAssignments(),
            ];
        });

        $this->command?->info(sprintf(
            'Платформенные фичи: настройки (%d), шаблоны ответов (%d), шаблоны договоров (%d), отзывы (%d), графики аренды (%d), дубликаты кадастра (%d), сохранённые поиски (%d), назначения лидов (%d).',
            $stats['settings'],
            $stats['templates'],
            $stats['contract_tpl'],
            $stats['reviews'],
            $stats['rent'],
            $stats['cadastral'],
            $stats['saved'],
            $stats['leads'],
        ));
    }

    private function seedSystemSettings(): int
    {
        $rows = [
            InquirySla::SETTING_KEY => '24',
            'contact_email' => 'office@agency.local',
            'agency_name' => 'Дом на ладони',
            'report_email_enabled' => '0',
            'report_email_recipients' => 'demo.admin@agency.local',
        ];

        foreach ($rows as $key => $value) {
            SystemSetting::set($key, $value);
        }

        return count($rows);
    }

    private function seedResponseTemplates(): int
    {
        if (ResponseTemplate::count() > 0) {
            return 0;
        }

        $realtor = User::query()->whereHas('roleRelation', fn ($q) => $q->where('kod', 'realtor'))->first();
        $created = 0;

        $common = [
            ['kod' => 'inquiry_greeting', 'nazvanie' => 'Приветствие по заявке', 'kontekst' => 'inquiry', 'tekst' => "Здравствуйте! Получили вашу заявку по объекту. Свяжемся с вами в течение рабочего дня.\n\nС уважением, агентство «Дом на ладони»."],
            ['kod' => 'selection_followup', 'nazvanie' => 'Ответ на подбор', 'kontekst' => 'selection', 'tekst' => "Добрый день! Подобрали для вас несколько вариантов по вашим критериям. Готовы организовать показ в удобное время."],
            ['kod' => 'info_docs', 'nazvanie' => 'Запрос документов', 'kontekst' => 'info', 'tekst' => 'Для ответа на ваш вопрос уточним данные по выписке ЕГРН и приложим скан при наличии согласия продавца.'],
        ];

        foreach ($common as $row) {
            ResponseTemplate::create([...$row, 'rieltor_id' => null]);
            $created++;
        }

        if ($realtor) {
            ResponseTemplate::create([
                'rieltor_id' => $realtor->id,
                'kod' => 'personal_call',
                'nazvanie' => 'Личный: перезвон',
                'kontekst' => 'inquiry',
                'tekst' => 'Добрый день! Это ваш риэлтор. Перезвоню в течение часа по заявке на объект.',
            ]);
            $created++;
        }

        return $created;
    }

    private function seedContractTemplates(): int
    {
        return ContractTemplate::count();
    }

    private function seedContractReviews(): int
    {
        if (ContractReview::count() > 0) {
            return 0;
        }

        $activeId = ContractStatus::idFor('active');
        if (! $activeId) {
            return 0;
        }

        $created = 0;
        Contract::query()
            ->where('status_dogovora_id', $activeId)
            ->where('tip', 'sale')
            ->limit(5)
            ->get()
            ->each(function (Contract $c) use (&$created) {
                foreach ([$c->vladelets_id, $c->pokupatel_id] as $userId) {
                    if (! $userId) {
                        continue;
                    }
                    ContractReview::firstOrCreate(
                        ['dogovor_id' => $c->id, 'polzovatel_id' => $userId],
                        ['ocenka' => rand(4, 5), 'tekst' => 'Сделка прошла удобно, документы в системе.'],
                    );
                    $created++;
                }
            });

        return $created;
    }

    private function seedRentSchedules(): int
    {
        $activeId = ContractStatus::idFor('active');
        if (! $activeId) {
            return 0;
        }

        $created = 0;
        Contract::query()
            ->where('status_dogovora_id', $activeId)
            ->where('tip', 'rent')
            ->limit(8)
            ->get()
            ->each(function (Contract $c) use (&$created) {
                if (RentPayment::where('dogovor_id', $c->id)->exists()) {
                    return;
                }
                $n = RentScheduleService::generateForContract($c);
                if ($n > 0) {
                    RentPayment::where('dogovor_id', $c->id)->orderBy('poryadok')->limit(2)->get()
                        ->each(fn (RentPayment $p) => RentScheduleService::markPaid($p));
                    $created++;
                }
            });

        return $created;
    }

    private function seedCadastralDuplicates(): int
    {
        $activeId = PropertyStatus::idFor('active');
        $pendingId = PropertyStatus::idFor('pending_review');
        $props = Property::query()
            ->when($activeId, fn ($q) => $q->where('status_obyavleniya_id', $activeId))
            ->whereNotNull('kadastrovy_nomer')
            ->limit(2)
            ->get();

        if ($props->count() < 2) {
            $props = Property::query()->limit(2)->get();
        }

        if ($props->count() < 2) {
            return 0;
        }

        $duplicateNumber = '77:01:0001001:9999';
        $updated = 0;
        foreach ($props as $p) {
            $p->update(['kadastrovy_nomer' => $duplicateNumber]);
            if ($pendingId && $updated === 1) {
                $p->update(['status_obyavleniya_id' => $pendingId]);
            }
            $updated++;
        }

        return $updated;
    }

    private function seedSavedSearches(): int
    {
        if (SavedSearch::count() > 0) {
            return 0;
        }

        $clients = User::query()->whereHas('roleRelation', fn ($q) => $q->where('kod', 'client'))->limit(10)->get();
        $created = 0;

        foreach ($clients as $i => $client) {
            SavedSearch::create([
                'polzovatel_id' => $client->id,
                'nazvanie' => ['Квартиры до 15 млн', 'Дома с участком', 'Аренда центр'][$i % 3],
                'filtry' => [
                    ['type' => 'apartment', 'operation' => 'sale', 'max_price' => 15000000],
                    ['type' => 'house', 'operation' => 'sale', 'garazh' => 1],
                    ['type' => 'apartment', 'operation' => 'rent', 'max_price' => 90000],
                ][$i % 3],
                'uvedomleniya' => $i % 2 === 0,
            ]);
            $created++;
        }

        return $created;
    }

    private function seedLeadAssignments(): int
    {
        $realtor = User::query()->whereHas('roleRelation', fn ($q) => $q->where('kod', 'realtor'))->first();
        if (! $realtor) {
            return 0;
        }

        PropertyInquiry::query()->whereNull('naznachen_rieltor_id')->limit(5)
            ->update(['naznachen_rieltor_id' => $realtor->id]);
        PropertySelectionRequest::query()->whereNull('naznachen_rieltor_id')->limit(3)
            ->update(['naznachen_rieltor_id' => $realtor->id]);

        return PropertyInquiry::where('naznachen_rieltor_id', $realtor->id)->count()
            + PropertySelectionRequest::where('naznachen_rieltor_id', $realtor->id)->count();
    }
}
