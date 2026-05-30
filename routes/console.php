<?php

// Консольные команды Artisan и расписание cron (schedule:work / crontab).

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Напоминания: договоры, аренда, задачи, показы — 09:00 и 18:00
Schedule::command('app:send-reminders')->twiceDaily(9, 18);
Schedule::command('app:notify-saved-searches')->twiceDaily(10, 16);
Schedule::command('app:send-weekly-report-email')->weeklyOn(1, '8:00');
