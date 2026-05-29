<?php

namespace App\Support;

use App\Models\Contract;
use App\Models\ContractStatus;
use App\Models\Property;
use App\Models\PropertyInquiry;
use App\Models\PropertyStatus;
use App\Models\RealtorTask;
use App\Models\User;

/**
 * Бережливое производство (Lean): поток ценности и следующий шаг без лишних кликов.
 */
class LeanWorkflow
{
    /** @return list<array{step: int, title: string, desc: string}> */
    public static function valueStream(): array
    {
        return [
            ['step' => 1, 'title' => 'Спрос', 'desc' => 'Поиск, заявка, избранное, ипотека'],
            ['step' => 2, 'title' => 'Объект', 'desc' => 'Черновик → документы ЕГРН → модерация → каталог'],
            ['step' => 3, 'title' => 'Показ', 'desc' => 'CRM: клиент, показ, подборка'],
            ['step' => 4, 'title' => 'Сделка', 'desc' => 'Договор, согласование сторон, оплата (тест)'],
            ['step' => 5, 'title' => 'Закрытие', 'desc' => 'PDF, статус продано/сдано, отчёт'],
        ];
    }

    /** @return list<array{label: string, url: string, tone: string, hint: ?string}> */
    public static function nextActionsFor(User $user): array
    {
        $actions = [];

        if ($user->isAdmin()) {
            $pendingMod = PropertyStatus::idFor('pending_review');
            $modCount = $pendingMod
                ? Property::where('status_obyavleniya_id', $pendingMod)->count()
                : 0;
            if ($modCount > 0) {
                $actions[] = self::action('Модерация (' . $modCount . ')', route('moderation.index'), 'warn', 'Убрать очередь — меньше WIP');
            }
            $actions[] = self::action('Админ-панель', route('admin.dashboard'), 'primary', null);
            $actions[] = self::action('Отчёты агентства', route('admin.reports'), 'default', null);
        }

        if ($user->isRealtor()) {
            $pendingCid = ContractStatus::idFor('pending');
            if ($pendingCid) {
                $cnt = Contract::where('status_dogovora_id', $pendingCid)
                    ->where(fn ($q) => $q->where('ozhidaet_podtverzhdeniya', 'realtor')->orWhereNull('ozhidaet_podtverzhdeniya'))
                    ->count();
                if ($cnt > 0) {
                    $actions[] = self::action('Договоры на подпись (' . $cnt . ')', route('contracts.pending'), 'warn', null);
                }
            }
            $newInquiries = PropertyInquiry::whereStatusKod('new')->count();
            if ($newInquiries > 0) {
                $actions[] = self::action('Новые заявки (' . $newInquiries . ')', route('realtor.inquiries.index'), 'warn', 'Ответить в течение SLA');
            }
            $openTasks = RealtorTask::where('rieltor_id', $user->id)->whereNull('vypolneno_at')->count();
            if ($openTasks > 0) {
                $actions[] = self::action('Задачи CRM (' . $openTasks . ')', route('realtor.tasks.index'), 'default', null);
            }
            $actions[] = self::action('Рабочее место', route('realtor.dashboard'), 'primary', null);
        }

        if ($user->isClient() || (!$user->isStaff())) {
            $draftId = PropertyStatus::idFor('draft');
            if ($draftId) {
                $drafts = Property::where('polzovatel_id', $user->id)
                    ->where('status_obyavleniya_id', $draftId)
                    ->get();
                foreach ($drafts->take(2) as $draft) {
                    if (!PropertyDocumentRules::isReadyForPublication($draft)) {
                        $actions[] = self::action(
                            'Документы: ' . \Illuminate\Support\Str::limit($draft->nazvanie, 28),
                            route('properties.documents', $draft),
                            'warn',
                            'Без ЕГРН публикация недоступна',
                        );
                    } else {
                        $actions[] = self::action(
                            'На модерацию: ' . \Illuminate\Support\Str::limit($draft->nazvanie, 28),
                            route('properties.documents', $draft),
                            'primary',
                            null,
                        );
                    }
                }
            }

            $pendingCid = ContractStatus::idFor('pending');
            if ($pendingCid) {
                $party = fn ($q) => $q->where('pokupatel_id', $user->id)->orWhere('vladelets_id', $user->id);
                $cnt = Contract::where('status_dogovora_id', $pendingCid)->where($party)->count();
                if ($cnt > 0) {
                    $actions[] = self::action('Подтвердить договор (' . $cnt . ')', route('contracts.index'), 'warn', null);
                }
            }

            $actions[] = self::action('Каталог', route('properties.index'), 'default', null);
            $actions[] = self::action('Купить онлайн', route('properties.index', ['operation' => 'sale']), 'primary', null);
        }

        return array_slice($actions, 0, 6);
    }

    /** @return array{label: string, url: string, tone: string, hint: ?string} */
    private static function action(string $label, string $url, string $tone, ?string $hint): array
    {
        return compact('label', 'url', 'tone', 'hint');
    }

    /** Принципы Lean для справки */
    public static function principles(): array
    {
        return [
            ['name' => 'Ценность для клиента', 'text' => 'Только действия, ведущие к показу, сделке и документам.'],
            ['name' => 'Поток', 'text' => 'Черновик → проверка → каталог → договор — без лишних статусов.'],
            ['name' => 'Вытягивание', 'text' => 'Следующий шаг подсказан в кабинете, а не спрятан в меню.'],
            ['name' => 'Ограничение WIP', 'text' => 'Очереди модерации и договоров видны на дашборде.'],
            ['name' => 'Качество в источнике', 'text' => 'Документы и модерация до публикации, а не после жалоб.'],
        ];
    }
}
