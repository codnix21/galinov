@php
    $versions = $versions ?? [];
@endphp
@if(count($versions) > 0)
<div class="card p-6 mt-6">
    <h2 class="text-lg font-bold mb-4">История версий статуса</h2>
    <ol class="space-y-3">
        @foreach($versions as $v)
            <li class="flex gap-3 text-sm border-l-2 border-brand-200 pl-3">
                <span class="font-mono text-brand-700 shrink-0">v{{ $v->nomer_versii }}</span>
                <div>
                    <span class="font-medium">{{ $v->status_nazvanie ?? $v->status_kod ?? '—' }}</span>
                    @if($v->status_kod)<span class="text-gray-500">({{ $v->status_kod }})</span>@endif
                    <div class="text-xs text-gray-500 mt-0.5">
                        {{ $v->sozdano_at?->format('d.m.Y H:i') }}
                        @if($v->user) · {{ trim($v->user->name) }} @endif
                        @if($v->kommentariy) · {{ $v->kommentariy }} @endif
                    </div>
                </div>
            </li>
        @endforeach
    </ol>
</div>
@endif
