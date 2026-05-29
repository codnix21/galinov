{{-- Колокольчик: native <details> — работает без Vite/сборки фронта --}}
@auth
<details class="notifications-bell relative shrink-0">
    <summary
        class="nav-link relative cursor-pointer select-none list-none whitespace-nowrap px-2 [&::-webkit-details-marker]:hidden"
        aria-label="Уведомления">
        🔔
        @if(($unreadNotificationsCount ?? 0) > 0)
            <span class="absolute -right-0.5 -top-0.5 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white">
                {{ $unreadNotificationsCount > 9 ? '9+' : $unreadNotificationsCount }}
            </span>
        @endif
    </summary>
    <div
        class="absolute right-0 top-full z-[60] mt-2 w-80 max-w-[calc(100vw-2rem)] rounded-2xl border border-slate-200 bg-white shadow-xl sm:w-96"
        role="menu"
        onclick="event.stopPropagation()">
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
            <span class="font-semibold text-slate-900">Уведомления</span>
            @if(($unreadNotificationsCount ?? 0) > 0)
                <form action="{{ route('notifications.read-all') }}" method="POST">
                    @csrf
                    <button type="submit" class="text-xs text-brand-700 hover:underline">Прочитать все</button>
                </form>
            @endif
        </div>
        <div class="max-h-80 overflow-y-auto">
            @forelse($headerNotifications ?? [] as $notification)
                @php
                    $data = $notification->data;
                    $isUnread = $notification->read_at === null;
                @endphp
                <a href="{{ route('notifications.read', $notification->id) }}"
                   class="block border-b border-slate-50 px-4 py-3 transition-colors hover:bg-slate-50 {{ $isUnread ? 'bg-brand-50/40' : '' }}"
                   role="menuitem">
                    <x-notification-item :data="$data" :time="$notification->created_at->diffForHumans()" />
                </a>
            @empty
                <p class="px-4 py-6 text-center text-sm text-slate-500">Нет уведомлений</p>
            @endforelse
        </div>
        <div class="border-t border-slate-100 px-4 py-2 text-center">
            <a href="{{ route('notifications.index') }}" class="text-sm text-brand-700 hover:underline">Все уведомления</a>
        </div>
    </div>
</details>
@once
@push('scripts')
<script>
(function () {
    if (window.__notificationsBellInit) return;
    window.__notificationsBellInit = true;

    document.addEventListener('click', function (e) {
        document.querySelectorAll('details.notifications-bell[open]').forEach(function (el) {
            if (!el.contains(e.target)) {
                el.removeAttribute('open');
            }
        });
    });

    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        document.querySelectorAll('details.notifications-bell[open]').forEach(function (el) {
            el.removeAttribute('open');
        });
    });
})();
</script>
@endpush
@endonce
@endauth
