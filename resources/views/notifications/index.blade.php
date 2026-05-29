@extends('layouts.app')

@section('title', 'Уведомления')

@section('content')
<div class="mb-8 flex flex-wrap items-center justify-between gap-4">
    <h1 class="text-4xl font-bold">Уведомления</h1>
    @if(auth()->user()->unreadNotifications()->count() > 0)
        <form action="{{ route('notifications.read-all') }}" method="POST">
            @csrf
            <button type="submit" class="btn">Прочитать все</button>
        </form>
    @endif
</div>

<div class="card divide-y divide-slate-100">
    @forelse($notifications as $notification)
        @php $data = $notification->data; @endphp
        <div class="p-4 flex gap-4 items-start {{ $notification->read_at ? '' : 'bg-brand-50/30' }}">
            <div class="flex-1">
                <x-notification-item :data="$data" :time="$notification->created_at->format('d.m.Y H:i')" />
            </div>
            <div class="shrink-0">
                @if($notification->read_at)
                    <a href="{{ $data['url'] ?? '#' }}" class="btn text-sm">Открыть</a>
                @else
                    <form action="{{ route('notifications.read', $notification->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn-primary text-sm">Прочитать</button>
                    </form>
                @endif
            </div>
        </div>
    @empty
        <p class="p-8 text-center text-gray-500">Уведомлений пока нет</p>
    @endforelse
</div>

<div class="mt-8">{{ $notifications->links() }}</div>
@endsection
