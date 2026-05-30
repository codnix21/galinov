<?php

namespace App\Services;

use App\Models\User;
use App\Support\TelegramHttp;
use Illuminate\Support\Facades\Log;

/**
 * Отправка сообщений в Telegram Bot API.
 */
class TelegramService
{
    public function isConfigured(): bool
    {
        return (string) config('services.telegram.bot_token') !== '';
    }

    public function botUsername(): ?string
    {
        $name = config('services.telegram.bot_username');

        return $name ? ltrim((string) $name, '@') : null;
    }

    public function sendMessage(?string $chatId, string $text): bool
    {
        if (!$this->isConfigured() || $chatId === null || $chatId === '') {
            return false;
        }

        $response = TelegramHttp::client()->post(TelegramHttp::apiUrl('sendMessage'), [
            'chat_id' => $chatId,
            'text' => mb_substr($text, 0, 4000),
            'disable_web_page_preview' => false,
        ]);

        if (!$response->successful()) {
            Log::warning('Telegram send failed', [
                'chat_id' => $chatId,
                'body' => $response->body(),
            ]);

            return false;
        }

        return true;
    }

    public function notifyUser(User $user, string $title, string $message, string $url): bool
    {
        $chatId = $user->telegram_chat_id ?? null;
        if ($chatId === null || $chatId === '') {
            return false;
        }

        $text = "🔔 {$title}\n\n{$message}";
        if ($url !== '') {
            $text .= "\n\n{$url}";
        }

        return $this->sendMessage($chatId, $text);
    }
}
