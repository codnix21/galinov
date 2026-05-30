<?php

namespace App\Console\Commands;

use App\Mail\WeeklyReportMail;
use App\Models\Property;
use App\Models\User;
use App\Notifications\SystemNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestNotificationsCommand extends Command
{
    protected $signature = 'app:test-notifications
                            {email? : Адрес для тестового письма (по умолчанию MAIL_FROM_ADDRESS)}
                            {--user= : ID пользователя — тест уведомления (колокольчик + email)}';

    protected $description = 'Проверка SMTP и email-уведомлений CRM';

    public function handle(): int
    {
        $this->info('Почта (отчёт): '.$this->testWeeklyMail());

        $userId = $this->option('user');
        if ($userId !== null) {
            $this->info('Уведомление CRM: '.$this->testSystemNotification((int) $userId));
        } else {
            $this->line('Подсказка: --user=ID — тест полного уведомления (БД + письмо с кнопкой).');
        }

        return self::SUCCESS;
    }

    private function testWeeklyMail(): string
    {
        $mailer = (string) config('mail.default');
        if ($mailer === 'log' || $mailer === 'array') {
            return "mailer={$mailer} — задайте MAIL_MAILER=smtp в .env";
        }

        $to = $this->argument('email') ?: config('mail.from.address');
        if (! is_string($to) || $to === '') {
            return 'не указан email получателя';
        }

        try {
            Mail::to($to)->send(new WeeklyReportMail([
                'properties_total' => Property::count(),
                'properties_active' => 0,
                'properties_sold' => 0,
                'contracts_period' => 0,
                'contracts_active' => 0,
                'inquiries_total' => 0,
                'inquiries_processed' => 0,
                'users_total' => User::count(),
            ], 'Тестовая отправка '.now()->format('d.m.Y H:i')));

            $host = config('mail.mailers.smtp.host')
                ?: parse_url((string) config('mail.mailers.smtp.url'), PHP_URL_HOST);

            return "письмо-отчёт отправлено на {$to} ({$mailer} @ {$host})";
        } catch (\Throwable $e) {
            return 'ошибка: '.$e->getMessage();
        }
    }

    private function testSystemNotification(int $userId): string
    {
        $user = User::find($userId);
        if (! $user) {
            return 'пользователь не найден';
        }

        $email = $user->email_polzovatela ?? '';
        if ($email === '') {
            return 'у пользователя нет email';
        }

        try {
            $user->notify(new SystemNotification(
                'Тест уведомления CRM',
                'Это проверка email-уведомления: модерация, договоры, заявки и напоминания приходят в таком виде.',
                route('cabinet.index'),
                'info',
            ));

            return "отправлено на {$email} и в колокольчик (id={$userId})";
        } catch (\Throwable $e) {
            return 'ошибка: '.$e->getMessage();
        }
    }
}
