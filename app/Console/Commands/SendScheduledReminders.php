<?php

namespace App\Console\Commands;

use App\Services\ReminderService;
use Illuminate\Console\Command;

class SendScheduledReminders extends Command
{
    protected $signature = 'app:send-reminders';

    protected $description = 'Отправить напоминания по договорам, задачам и показам';

    public function handle(ReminderService $reminders): int
    {
        $count = $reminders->run();
        $this->info("Отправлено напоминаний (событий): {$count}");

        return self::SUCCESS;
    }
}
