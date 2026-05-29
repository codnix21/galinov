{{-- Только просмотр реквизитов: $lines list{label,value}, optional $title --}}
@if(!empty($lines))
    <dl class="mt-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm space-y-1.5">
        @if(!empty($title))
            <dt class="font-semibold text-slate-800 mb-1">{{ $title }}</dt>
        @endif
        @foreach($lines as $line)
            <div class="flex flex-wrap gap-x-2 gap-y-0.5">
                <dt class="text-slate-500 shrink-0">{{ $line['label'] }}:</dt>
                <dd class="text-slate-900 font-medium break-words">{{ $line['value'] }}</dd>
            </div>
        @endforeach
    </dl>
@endif
