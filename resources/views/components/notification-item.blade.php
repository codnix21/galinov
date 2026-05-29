@props(['data' => [], 'time' => null, 'class' => ''])

@php
    $iconKey = $data['icon'] ?? 'info';
    $style = \App\Support\NotificationDisplay::forIcon($iconKey);
@endphp
<div class="flex gap-3 {{ $class ?? '' }}">
    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border {{ $style['border'] }} {{ $style['bg'] }} {{ $style['text'] }} text-sm font-bold" title="{{ $style['label'] }}">
        {{ $style['icon'] }}
    </div>
    <div class="min-w-0 flex-1">
        <div class="text-sm font-semibold text-slate-900">{{ $data['title'] ?? 'Уведомление' }}</div>
        @if(!empty($data['message']))
            <div class="mt-0.5 text-xs text-slate-600 line-clamp-2">{{ $data['message'] }}</div>
        @endif
        @if(isset($time))
            <div class="mt-1 text-[10px] text-slate-400">{{ $time }}</div>
        @endif
    </div>
</div>
