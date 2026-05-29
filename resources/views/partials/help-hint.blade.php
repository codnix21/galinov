{{-- Подсказка со знаком вопроса. --}}
@php
    $title = $title ?? null;
    $points = $points ?? [];
@endphp
@if($title || count($points))
<div class="mb-6 rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
    @if($title)
        <p class="font-semibold text-slate-900 mb-2">{{ $title }}</p>
    @endif
    @if(count($points))
        <ul class="list-disc pl-5 space-y-1.5">
            @foreach($points as $point)
                <li>{{ $point }}</li>
            @endforeach
        </ul>
    @endif
</div>
@endif
