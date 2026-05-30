<?php

namespace App\Console\Commands;

use App\Mail\WeeklyReportMail;
use App\Models\User;
use App\Services\TelegramService;
use App\Support\TelegramHttp;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestNotificationsCommand extends Command
{
    protected $signature = 'app:test-notifications
                            {email? : Адрес для тестового письма (по умолчанию MAIL_FROM_ADDRESS)}
                            {--telegram-user= : ID записи в polzovateli (не chat_id)}
                            {--telegram-chat= : Telegram chat_id для прямой отправки}';

    protected $description = 'Проверка SMTP и Telegram Bot API';

    public function handle(TelegramService $telegram): int
    {
        $this->info('Почта: '.$this->testMail());

        try {
            $this->info('Telegram: '.$this->testTelegram($telegram));
        } catch (\Throwable $e) {
            $this->warn('Telegram: '.$e->getMessage());
        }

        $proxy = config('services.telegram.proxy');
        if ($proxy) {
            $this->line('Прокси Telegram: '.$proxy);
        } else {
            $this->line('Прокси Telegram не задан (TELEGRAM_PROXY в .env).');
        }

        return self::SUCCESS;
    }

    private function testMail(): string
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
                'properties_total' => 0,
                'properties_active' => 0,
                'properties_sold' => 0,
                'contracts_period' => 0,
                'contracts_active' => 0,
                'inquiries_total' => 0,
                'inquiries_processed' => 0,
                'users_total' => 0,
            ], 'Тестовая отправка '.now()->format('d.m.Y H:i')));

            $host = config('mail.mailers.smtp.host') ?: parse_url((string) config('mail.mailers.smtp.url'), PHP_URL_HOST);

            return "письмо отправлено на {$to} ({$mailer} @ {$host})";
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            if (str_contains($msg, 'tlsv1 alert internal error') || str_contains($msg, 'verify failed')) {
                $msg .= ' — с домашнего ПК SMTP часто недоступен; проверьте на VPS (agn.irk138.ru): php artisan app:test-notifications';
            }

            return 'ошибка: '.$msg;
        }
    }

    private function testTelegram(TelegramService $telegram): string
    {
        if (! $telegram->isConfigured()) {
            return 'TELEGRAM_BOT_TOKEN не задан';
        }

        $me = TelegramHttp::client()->get(TelegramHttp::apiUrl('getMe'));
        if (! $me->successful()) {
            return 'getMe failed: '.$me->body();
        }

        $bot = $me->json('result.username') ?? '?';

        $chatId = $this->option('telegram-chat');
        if ($chatId !== null && $chatId !== '') {
            $ok = $telegram->sendMessage((string) $chatId, 'Тест CRM: уведомления работают.');

            return $ok
                ? "@{$bot} — сообщение отправлено в chat_id={$chatId}"
                : 'sendMessage не удался (см. laravel.log)';
        }

        $userId = $this->option('telegram-user');
        if ($userId === null) {
            return "@{$bot} — бот доступен (--telegram-chat=CHAT_ID или --telegram-user=ID в БД)";
        }

        $user = User::find((int) $userId);
        if (! $user) {
            return 'пользователь с id='.$userId.' не найден';
        }

        if (! $user->telegram_chat_id) {
            if ((string) $userId === (string) $userId && strlen((string) $userId) > 8) {
                return 'похоже, вы передали chat_id — используйте --telegram-chat='.$userId;
            }

            return 'у пользователя '.$userId.' нет telegram_chat_id (подключите бота в профиле)';
        }

        $ok = $telegram->notifyUser($user, 'Тест', 'Проверка уведомлений CRM', config('app.url'));

        return $ok ? "сообщение отправлено chat_id={$user->telegram_chat_id}" : 'sendMessage не удался (см. laravel.log)';
    }
}
