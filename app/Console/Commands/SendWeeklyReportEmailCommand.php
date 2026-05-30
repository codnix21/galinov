<?php

namespace App\Console\Commands;

use App\Mail\WeeklyReportMail;
use App\Models\Contract;
use App\Models\Property;
use App\Models\PropertyInquiry;
use App\Models\PropertyStatus;
use App\Models\SystemSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendWeeklyReportEmailCommand extends Command
{
    protected $signature = 'app:send-weekly-report-email';

    protected $description = 'Отправка еженедельного отчёта на email из настроек';

    public function handle(): int
    {
        if (SystemSetting::get('report_email_enabled', '0') !== '1') {
            $this->info('Отчёты на email отключены в настройках.');

            return self::SUCCESS;
        }

        $recipients = array_filter(array_map('trim', explode(',', SystemSetting::get('report_email_recipients', '') ?? '')));
        if ($recipients === []) {
            $this->warn('Не указаны получатели report_email_recipients.');

            return self::FAILURE;
        }

        $from = Carbon::now()->subWeek()->startOfDay();
        $to = Carbon::now()->endOfDay();
        $activePid = PropertyStatus::idFor('active');

        $summary = [
            'properties_total' => Property::count(),
            'properties_active' => $activePid ? Property::where('status_obyavleniya_id', $activePid)->count() : 0,
            'properties_sold' => Property::whereHas('statusRelation', fn ($q) => $q->where('kod', 'sold'))->count(),
            'contracts_period' => Contract::whereBetween('sozdano_at', [$from, $to])->count(),
            'contracts_active' => Contract::whereHas('statusRelation', fn ($q) => $q->where('kod', 'active'))->count(),
            'inquiries_total' => PropertyInquiry::whereBetween('sozdano_at', [$from, $to])->count(),
            'inquiries_processed' => PropertyInquiry::whereBetween('sozdano_at', [$from, $to])->whereStatusKod('processed')->count(),
            'users_total' => User::count(),
        ];

        $label = $from->format('d.m.Y').' — '.$to->format('d.m.Y');

        foreach ($recipients as $email) {
            Mail::to($email)->send(new WeeklyReportMail($summary, $label));
        }

        $this->info('Отчёт отправлен: '.count($recipients).' получателей.');

        return self::SUCCESS;
    }
}
