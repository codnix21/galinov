<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\Property;
use App\Models\PropertyCollection;
use App\Models\PropertyInquiry;
use App\Models\PropertyInfoRequest;
use App\Models\PropertySelectionRequest;
use App\Models\PropertyShowing;
use App\Models\RealtorClient;
use App\Models\RealtorTask;
use App\Models\User;
use App\Notifications\SystemNotification;
use App\Support\ContractApproval;
use Illuminate\Support\Collection;

/**
 * In-app и email-уведомления по событиям CRM.
 */
class AppNotifier
{
    /** Напоминание по расписанию. */
    public static function reminder(User $user, string $title, string $message, string $url): void
    {
        self::deliver($user, $title, $message, $url, 'info');
    }

    private static function deliver(User $user, string $title, string $message, string $url, string $icon = 'info'): void
    {
        $user->notify(new SystemNotification($title, $message, $url, $icon));
    }

    public static function propertySubmittedForModeration(Property $property): void
    {
        $property->loadMissing(['user', 'cityRelation']);
        $ownerId = (int) $property->polzovatel_id;
        $title = 'Новое объявление на модерации';
        $city = $property->gorod ?? '—';
        $message = sprintf('«%s» (%s) ожидает проверки.', $property->nazvanie, $city);
        $url = route('moderation.index');

        self::notifyStaffExcept($ownerId, $title, $message, $url, 'moderation');
    }

    public static function propertyModerationApproved(Property $property): void
    {
        $owner = $property->user;
        if (!$owner) {
            return;
        }

        self::deliver(
            $owner,
            'Объявление опубликовано',
            sprintf('«%s» прошло модерацию и доступно в каталоге.', $property->nazvanie),
            route('properties.show', $property),
            'success',
        );
    }

    public static function propertyModerationRejected(Property $property): void
    {
        $owner = $property->user;
        if (!$owner) {
            return;
        }

        $reason = $property->prichina_otkaza_mod
            ? ' Причина: '.mb_substr($property->prichina_otkaza_mod, 0, 120)
            : '';

        self::deliver(
            $owner,
            'Объявление отклонено',
            sprintf('«%s» возвращено в черновик.%s', $property->nazvanie, $reason),
            route('properties.edit', $property),
            'warning',
        );
    }

    public static function contractCreated(Contract $contract): void
    {
        $contract->loadMissing(['property']);
        $title = 'Договор ожидает подтверждения';
        $propTitle = $contract->property?->nazvanie ?? 'объект #'.$contract->nedvizhimost_id;
        $summary = ContractApproval::pendingSummary($contract);

        foreach (self::contractPartiesNeedingApproval($contract) as $user) {
            self::deliver(
                $user,
                $title,
                sprintf('Договор по «%s». Ожидается: %s.', $propTitle, $summary),
                route('contracts.show', $contract),
                'contract',
            );
        }
    }

    public static function contractFullyApproved(Contract $contract, ?int $exceptUserId = null): void
    {
        $contract->loadMissing(['property', 'owner', 'buyer', 'realtor']);
        $propTitle = $contract->property?->nazvanie ?? 'объект #'.$contract->nedvizhimost_id;

        $parties = collect([$contract->owner, $contract->buyer, $contract->realtor])
            ->filter()
            ->unique('id')
            ->when($exceptUserId !== null, fn ($c) => $c->where('id', '!=', $exceptUserId));

        foreach ($parties as $user) {
            self::deliver(
                $user,
                'Договор активирован',
                sprintf('Все стороны подтвердили сделку по «%s».', $propTitle),
                route('contracts.show', $contract),
                'success',
            );
        }
    }

    /** @return Collection<int, User> */
    private static function contractPartiesNeedingApproval(Contract $contract): Collection
    {
        $users = collect();

        if (ContractApproval::needsOwnerApproval($contract) && !ContractApproval::isOwnerApproved($contract)) {
            $owner = User::find(ContractApproval::ownerId($contract));
            if ($owner) {
                $users->push($owner);
            }
        }

        if (ContractApproval::needsBuyerApproval($contract) && !ContractApproval::isBuyerApproved($contract)) {
            $buyer = User::find(ContractApproval::buyerId($contract));
            if ($buyer) {
                $users->push($buyer);
            }
        }

        if (ContractApproval::needsRealtorApproval($contract) && !ContractApproval::isRealtorApproved($contract)) {
            $realtor = User::find(ContractApproval::realtorId($contract));
            if ($realtor) {
                $users->push($realtor);
            }
        }

        return $users->unique('id')->values();
    }

    public static function clientAssigned(User $client, RealtorClient $assignment): void
    {
        $assignment->loadMissing('realtor');
        $realtorName = trim($assignment->realtor?->name ?? 'Риэлтор');

        self::deliver(
            $client,
            'Вас закрепил риэлтор',
            sprintf('%s ведёт вашу заявку в агентстве.', $realtorName),
            route('cabinet.index'),
            'info',
        );
    }

    public static function taskAssigned(RealtorTask $task): void
    {
        $task->loadMissing('client');
        if (!$task->client) {
            return;
        }

        self::deliver(
            $task->client,
            'Новая задача от риэлтора',
            $task->nazvanie,
            route('cabinet.index'),
            'info',
        );
    }

    public static function showingScheduled(PropertyShowing $showing): void
    {
        $showing->loadMissing(['client', 'property']);
        if (!$showing->client) {
            return;
        }

        $when = $showing->naznacheno_na?->format('d.m.Y H:i') ?? '';
        $prop = $showing->property?->nazvanie ?? 'объект';

        self::deliver(
            $showing->client,
            'Запланирован показ',
            sprintf('%s — %s', $prop, $when),
            route('properties.show', $showing->nedvizhimost_id),
            'info',
        );
    }

    public static function collectionShared(PropertyCollection $collection): void
    {
        $collection->loadMissing('client');
        if (!$collection->client) {
            return;
        }

        self::deliver(
            $collection->client,
            'Подборка объектов',
            sprintf('Риэлтор подготовил подборку «%s».', $collection->nazvanie),
            $collection->publicUrl(),
            'info',
        );
    }

    public static function contractEcpSignedByBuyer(Contract $contract): void
    {
        $contract->loadMissing(['property', 'owner', 'realtor', 'buyer']);
        $buyerName = trim($contract->buyer?->name ?? 'Покупатель');
        $prop = $contract->property?->nazvanie ?? 'объект #'.$contract->nedvizhimost_id;
        $title = 'Покупатель подписал договор УКЭП';
        $message = sprintf('%s подписал договор по «%s».', $buyerName, $prop);
        $url = route('contracts.show', $contract);

        foreach (collect([$contract->owner, $contract->realtor])->filter() as $party) {
            self::deliver($party, $title, $message, $url, 'success');
        }
    }

    public static function propertyInquiry(PropertyInquiry $inquiry): void
    {
        $inquiry->loadMissing(['property.user', 'user']);
        $propTitle = $inquiry->property?->nazvanie ?? 'объект #'.$inquiry->nedvizhimost_id;
        $staffTitle = 'Новая заявка по объекту';
        $staffMessage = sprintf('«%s» — %s', $propTitle, $inquiry->imya);
        $staffUrl = route('realtor.inquiries.index');

        self::notifyStaffExcept(0, $staffTitle, $staffMessage, $staffUrl, 'info');

        $owner = $inquiry->property?->user;
        if ($owner && !$owner->isStaff()) {
            self::deliver(
                $owner,
                'Заявка на ваш объект',
                sprintf('«%s» — %s', $propTitle, $inquiry->imya),
                route('properties.show', $inquiry->nedvizhimost_id),
                'info',
            );
        }

        if ($inquiry->user) {
            self::deliver(
                $inquiry->user,
                'Заявка отправлена',
                sprintf('По объекту «%s» риэлтор свяжется с вами.', $propTitle),
                route('properties.show', $inquiry->nedvizhimost_id),
                'info',
            );
        }
    }

    public static function propertySelectionRequest(PropertySelectionRequest $request): void
    {
        $request->loadMissing('user');
        $summary = $request->filtersSummary();
        $staffTitle = 'Заявка на подбор недвижимости';
        $staffMessage = sprintf('%s — %s', $request->imya, $summary);
        $staffUrl = route('realtor.selection-requests.index');

        self::notifyStaffExcept(0, $staffTitle, $staffMessage, $staffUrl, 'info');

        if ($request->user) {
            self::deliver(
                $request->user,
                'Заявка принята',
                'Риэлтор подберёт объекты по вашим критериям и свяжется с вами.',
                route('properties.index', $request->filtry ?? []),
                'info',
            );
        }
    }

    public static function propertyInfoRequestCreated(PropertyInfoRequest $infoRequest): void
    {
        $infoRequest->loadMissing(['property', 'client']);
        $propTitle = $infoRequest->property?->nazvanie ?? 'объект #'.$infoRequest->nedvizhimost_id;
        $tipLabel = $infoRequest->tipLabel();

        self::notifyStaffExcept(
            0,
            'Запрос доп. информации',
            sprintf('«%s» — %s', $propTitle, $tipLabel),
            route('realtor.info-requests.index'),
            'info',
        );

        if ($infoRequest->client) {
            self::deliver(
                $infoRequest->client,
                'Запрос отправлен',
                sprintf('По объекту «%s» риэлтор ответит в истории запроса.', $propTitle),
                route('properties.show', $infoRequest->nedvizhimost_id).'#dop-informaciya',
                'info',
            );
        }
    }

    public static function propertyInfoRequestAnswered(PropertyInfoRequest $infoRequest): void
    {
        $infoRequest->loadMissing(['property', 'client']);
        if (!$infoRequest->client) {
            return;
        }

        $propTitle = $infoRequest->property?->nazvanie ?? 'объект #'.$infoRequest->nedvizhimost_id;
        self::deliver(
            $infoRequest->client,
            'Ответ риэлтора',
            sprintf('По вашему запросу по «%s» получен ответ.', $propTitle),
            route('properties.show', $infoRequest->nedvizhimost_id).'#dop-informaciya',
            'success',
        );
    }

    public static function propertyInquiryProcessed(PropertyInquiry $inquiry): void
    {
        $inquiry->loadMissing(['property', 'user']);
        if (!$inquiry->user) {
            return;
        }

        $propTitle = $inquiry->property?->nazvanie ?? 'объект #'.$inquiry->nedvizhimost_id;
        self::deliver(
            $inquiry->user,
            'Заявка обработана',
            sprintf('Ваша заявка по «%s» принята в работу.', $propTitle),
            route('properties.show', $inquiry->nedvizhimost_id),
            'success',
        );
    }

    public static function onlinePurchaseContractCreated(Contract $contract): void
    {
        if (!self::isOnlineAutoPurchase($contract)) {
            return;
        }

        $buyer = self::resolvedBuyer($contract);
        if (!$buyer) {
            return;
        }

        $contract->loadMissing('property');
        $propTitle = $contract->property?->nazvanie ?? 'объект #'.$contract->nedvizhimost_id;
        $amount = number_format((float) ($contract->tsena ?? 0), 0, ',', ' ').' ₽';

        self::deliver(
            $buyer,
            'Договор оформлен',
            sprintf('По объекту «%s» сформирован договор на %s. Перейдите к оплате.', $propTitle, $amount),
            route('purchase.payment', $contract),
            'contract',
        );
    }

    public static function onlinePurchasePaid(Contract $contract): void
    {
        if (!self::isOnlineAutoPurchase($contract)) {
            return;
        }

        $buyer = self::resolvedBuyer($contract);
        if (!$buyer) {
            return;
        }

        $contract->loadMissing('property');
        $propTitle = $contract->property?->nazvanie ?? 'объект #'.$contract->nedvizhimost_id;

        self::deliver(
            $buyer,
            'Оплата прошла',
            sprintf(
                'Платёж по «%s» принят. Продавец и риэлтор подписали договор автоматически — осталась ваша подпись УКЭП.',
                $propTitle,
            ),
            route('purchase.complete', $contract),
            'success',
        );
    }

    public static function onlinePurchaseBuyerEcpSigned(Contract $contract): void
    {
        if (!self::isOnlineAutoPurchase($contract)) {
            return;
        }

        $buyer = self::resolvedBuyer($contract);
        if (!$buyer) {
            return;
        }

        $contract->loadMissing('property');
        $propTitle = $contract->property?->nazvanie ?? 'объект #'.$contract->nedvizhimost_id;
        $url = route('purchase.complete', $contract);

        if (($contract->status ?? '') === 'active') {
            self::deliver(
                $buyer,
                'Онлайн-сделка завершена',
                sprintf('Договор по «%s» подписан всеми сторонами и активирован.', $propTitle),
                $url,
                'success',
            );

            return;
        }

        self::deliver(
            $buyer,
            'Подпись УКЭП сохранена',
            sprintf('Вы подписали договор по «%s». Ожидается активация сделки.', $propTitle),
            $url,
            'success',
        );
    }

    public static function isOnlineAutoPurchase(Contract $contract): bool
    {
        return ($contract->sozdal_kak ?? '') === 'client'
            && (bool) ($contract->avto_zapolnen ?? false);
    }

    private static function resolvedBuyer(Contract $contract): ?User
    {
        $buyerId = ContractApproval::buyerId($contract);

        return $buyerId ? User::find($buyerId) : null;
    }

    private static function notifyStaffExcept(
        int $exceptUserId,
        string $title,
        string $message,
        string $url,
        string $icon = 'info',
    ): void {
        User::query()
            ->where('id', '!=', $exceptUserId)
            ->whereHas('roleRelation', fn ($q) => $q->whereIn('kod', ['admin', 'realtor']))
            ->where(fn ($q) => $q->where('zablokirovan', false)->orWhereNull('zablokirovan'))
            ->each(fn (User $user) => self::deliver($user, $title, $message, $url, $icon));
    }
}
