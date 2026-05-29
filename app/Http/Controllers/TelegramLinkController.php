<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class TelegramLinkController extends Controller
{
    public function createLink(): RedirectResponse
    {
        $user = Auth::user();
        if (!$user || !$user->isStaff()) {
            abort(403);
        }

        $token = Str::random(40);
        Cache::put('telegram_link:'.$token, $user->getKey(), now()->addHour());

        $bot = config('services.telegram.bot_username');
        if (!$bot) {
            return back()->withErrors(['telegram' => 'Укажите TELEGRAM_BOT_USERNAME в .env']);
        }

        $deepLink = 'https://t.me/'.ltrim($bot, '@').'?start=link_'.$token;

        return back()->with('telegram_deep_link', $deepLink);
    }
}
