<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\TelegramHttp;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Webhook Telegram-бота: привязка chat_id к аккаунту (/start link_TOKEN).
 */
class TelegramWebhookController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $secret = config('services.telegram.webhook_secret');
        if ($secret && $request->header('X-Telegram-Bot-Api-Secret-Token') !== $secret) {
            abort(403);
        }

        $update = $request->all();
        $message = $update['message'] ?? $update['edited_message'] ?? null;
        if (!is_array($message)) {
            return response('ok');
        }

        $text = trim((string) ($message['text'] ?? ''));
        $chatId = (string) ($message['chat']['id'] ?? '');

        if ($chatId !== '' && str_starts_with($text, '/start')) {
            $parts = explode(' ', $text, 2);
            $payload = $parts[1] ?? '';
            if (str_starts_with($payload, 'link_')) {
                $token = substr($payload, 5);
                $userId = Cache::get('telegram_link:'.$token);
                if ($userId) {
                    $user = User::find($userId);
                    if ($user) {
                        $user->update(['telegram_chat_id' => $chatId]);
                        Cache::forget('telegram_link:'.$token);
                        $this->reply($chatId, '✅ Telegram подключён к аккаунту '.$user->email_polzovatela);
                    }
                } else {
                    $this->reply($chatId, 'Ссылка устарела. Получите новую в профиле на сайте.');
                }

                return response('ok');
            }

            $this->reply($chatId, 'Агентство недвижимости. Для привязки аккаунта откройте ссылку из личного кабинета на сайте.');
        }

        return response('ok');
    }

    private function reply(string $chatId, string $text): void
    {
        if (! config('services.telegram.bot_token')) {
            return;
        }

        try {
            TelegramHttp::client()->post(TelegramHttp::apiUrl('sendMessage'), [
                'chat_id' => $chatId,
                'text' => $text,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Telegram webhook reply failed', ['error' => $e->getMessage()]);
        }
    }
}
