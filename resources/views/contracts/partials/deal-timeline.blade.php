@php
    /** @var list<array{key: string, label: string, done: bool, at: ?\Illuminate\Support\Carbon, hint: ?string, url: ?string}> $steps */
    $steps = $steps ?? [];
    $progress = $progress ?? \App\Support\ContractDealTimeline::progressPercent($steps);
@endphp
<div class="deal-timeline">
    <div class="flex items-center justify-between gap-3 mb-3">
        <span class="text-sm font-medium text-slate-700">Прогресс сделки</span>
        <span class="text-sm font-bold text-brand-700">{{ $progress }}%</span>
    </div>
    <div class="h-2 bg-slate-200 rounded-full mb-4 overflow-hidden">
        <div class="h-full bg-brand-600 rounded-full transition-all" style="width: {{ $progress }}%"></div>
    </div>
    <ol class="space-y-3">
        @foreach($steps as $step)
            <li class="flex gap-3 text-sm {{ $step['done'] ? 'text-slate-800' : 'text-slate-500' }}">
                <span class="shrink-0 w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold {{ $step['done'] ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-500' }}">
                    {{ $step['done'] ? '✓' : '○' }}
                </span>
                <div class="min-w-0 flex-1">
                    @if(!empty($step['url']) && $step['done'])
                        <a href="{{ $step['url'] }}" class="font-medium hover:underline">{{ $step['label'] }}</a>
                    @else
                        <span class="font-medium">{{ $step['label'] }}</span>
                    @endif
                    @if($step['at'])
                        <span class="text-xs text-slate-500 block">{{ $step['at']->format('d.m.Y H:i') }}</span>
                    @endif
                    @if($step['hint'])
                        <span class="text-xs text-slate-500 block">{{ $step['hint'] }}</span>
                    @endif
                </div>
            </li>
        @endforeach
    </ol>
</div>
