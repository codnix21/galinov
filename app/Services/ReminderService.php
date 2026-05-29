<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractStatus;
use App\Models\PropertyShowing;
use App\Models\ReminderLog;
use App\Models\RealtorTask;
use App\Models\User;
use App\Support\ContractApproval;
use Illuminate\Support\Carbon;

/**
 * Автоматические напоминания (договоры, аренда, задачи, показы).
 */
class ReminderService
{
    public function run(): int
    {
        $sent = 0;
        $sent += $this->remindPendingContracts();
        $sent += $this->remindRentEnding();
        $sent += $this->remindDueTasks();
        $sent += $this->remindUpcomingShowings();

        return $sent;
    }

    private function remindPendingContracts(): int
    {
        $pendingId = ContractStatus::idFor('pending');
        if ($pendingId === null) {
            return 0;
        }

        $sent = 0;
        $contracts = Contract::query()
            ->where('status_dogovora_id', $pendingId)
            ->where('sozdano_at', '<=', now()->subDays(3))
            ->with(['property', 'realtor', 'owner', 'buyer'])
            ->get();

        foreach ($contracts as $contract) {
            $tip = 'contract_pending_3d';
            if (ReminderLog::alreadySent($tip, 'contract', (int) $contract->id)) {
                continue;
            }

            $prop = $contract->property?->nazvanie ?? 'договор #'.$contract->id;
            $summary = ContractApproval::pendingSummary($contract);
            $title = 'Договор ждёт подтверждения 3+ дня';
            $message = sprintf('«%s». Ожидается: %s.', $prop, $summary);
            $url = route('contracts.show', $contract);

            foreach ($this->contractReminderRecipients($contract) as $user) {
                AppNotifier::reminder($user, $title, $message, $url);
            }

            ReminderLog::markSent($tip, 'contract', (int) $contract->id);
            $sent++;
        }

        return $sent;
    }

    private function remindRentEnding(): int
    {
        $activeId = ContractStatus::idFor('active');
        if ($activeId === null) {
            return 0;
        }

        $sent = 0;
        foreach ([7 => 'rent_end_7d', 1 => 'rent_end_1d'] as $days => $tip) {
            $target = now()->addDays($days)->toDateString();

            $contracts = Contract::query()
                ->where('status_dogovora_id', $activeId)
                ->where('tip', 'rent')
                ->whereDate('data_okonchaniya', $target)
                ->with(['property', 'owner', 'buyer', 'realtor'])
                ->get();

            foreach ($contracts as $contract) {
                if (ReminderLog::alreadySent($tip, 'contract', (int) $contract->id)) {
                    continue;
                }

                $prop = $contract->property?->nazvanie ?? 'объект';
                $title = $days === 1 ? 'Аренда заканчивается завтра' : 'Аренда заканчивается через 7 дней';
                $message = sprintf(
                    '«%s» — окончание %s.',
                    $prop,
                    $contract->data_okonchaniya?->format('d.m.Y') ?? ''
                );
                $url = route('contracts.show', $contract);

                foreach ($this->contractReminderRecipients($contract) as $user) {
                    AppNotifier::reminder($user, $title, $message, $url);
                }

                ReminderLog::markSent($tip, 'contract', (int) $contract->id);
                $sent++;
            }
        }

        return $sent;
    }

    private function remindDueTasks(): int
    {
        $sent = 0;
        $tasks = RealtorTask::query()
            ->whereNull('vypolneno_at')
            ->whereNotNull('srok_do')
            ->where('srok_do', '<=', now()->endOfDay())
            ->with(['realtor', 'client', 'property'])
            ->get();

        foreach ($tasks as $task) {
            $tip = 'task_due';
            if (ReminderLog::alreadySent($tip, 'task', (int) $task->id)) {
                continue;
            }

            $realtor = $task->realtor;
            if (!$realtor) {
                continue;
            }

            $title = $task->srok_do->isPast() ? 'Просроченная задача' : 'Задача на сегодня';
            $message = $task->nazvanie;
            if ($task->srok_do) {
                $message .= ' — до '.$task->srok_do->format('d.m.Y H:i');
            }

            AppNotifier::reminder($realtor, $title, $message, route('realtor.tasks.index'));

            ReminderLog::markSent($tip, 'task', (int) $task->id);
            $sent++;
        }

        return $sent;
    }

    private function remindUpcomingShowings(): int
    {
        $sent = 0;
        $showings = PropertyShowing::query()
            ->whereBetween('naznacheno_na', [now(), now()->addHours(24)])
            ->with(['realtor', 'client', 'property'])
            ->get();

        foreach ($showings as $showing) {
            $tip = 'showing_24h';
            if (ReminderLog::alreadySent($tip, 'showing', (int) $showing->id)) {
                continue;
            }

            $prop = $showing->property?->nazvanie ?? 'объект';
            $when = $showing->naznacheno_na->format('d.m.Y H:i');
            $title = 'Показ в ближайшие 24 часа';
            $message = sprintf('%s — %s', $prop, $when);
            $url = route('properties.show', $showing->nedvizhimost_id);

            if ($showing->realtor) {
                AppNotifier::reminder($showing->realtor, $title, $message, route('realtor.showings.index'));
            }
            if ($showing->client) {
                AppNotifier::reminder($showing->client, $title, $message, $url);
            }

            ReminderLog::markSent($tip, 'showing', (int) $showing->id);
            $sent++;
        }

        return $sent;
    }

    /** @return list<User> */
    private function contractReminderRecipients(Contract $contract): array
    {
        return collect([$contract->realtor, $contract->owner, $contract->buyer])
            ->filter()
            ->unique('id')
            ->values()
            ->all();
    }
}
