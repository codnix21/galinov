@if(Auth::user()->isStaff())
<div class="card p-8">
    <h2 class="text-2xl font-bold mb-2">Telegram-уведомления</h2>
    <p class="text-sm text-gray-600 mb-4">
        Дублируем важные события в Telegram: модерация, договоры, задачи, показы.
    </p>

    @if(session('telegram_deep_link'))
        <div class="mb-4 p-4 bg-brand-50 border border-brand-200 rounded-xl">
            <p class="text-sm font-medium mb-2">Откройте бота и нажмите «Запустить»:</p>
            <a href="{{ session('telegram_deep_link') }}" target="_blank" rel="noopener" class="btn-primary inline-block">
                Подключить в Telegram
            </a>
        </div>
    @endif

    @if(session('status') === 'telegram-updated')
        <p class="text-sm text-green-700 mb-4">Настройки Telegram сохранены.</p>
    @endif

    <form method="POST" action="{{ route('profile.telegram.update') }}" class="space-y-4">
        @csrf
        @method('patch')
        <div>
            <label class="form-label">Chat ID</label>
            <input type="text" name="telegram_chat_id" value="{{ old('telegram_chat_id', $user->telegram_chat_id) }}"
                class="form-input" placeholder="Например: 123456789">
            <p class="text-xs text-gray-500 mt-1">Узнать у @userinfobot или через кнопку ниже</p>
        </div>
        <button type="submit" class="btn-primary">Сохранить</button>
    </form>

    @if(config('services.telegram.bot_token'))
        <form method="POST" action="{{ route('profile.telegram.link') }}" class="mt-4 pt-4 border-t">
            @csrf
            <button type="submit" class="btn">Сгенерировать ссылку для бота</button>
        </form>
    @else
        <p class="text-sm text-amber-700 mt-4">Задайте TELEGRAM_BOT_TOKEN в .env.</p>
    @endif

    @if($user->telegram_chat_id)
        <p class="text-sm text-green-700 mt-4">Подключено (chat_id: {{ $user->telegram_chat_id }})</p>
    @endif
</div>
@endif
