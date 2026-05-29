@if(!empty($leanActions))
<section class="card p-5 sm:p-6 mb-8 border-brand-200/60 bg-gradient-to-br from-brand-50/80 to-white">
    <h2 class="text-lg font-bold text-slate-900 mb-1">Следующий шаг</h2>
    <p class="text-sm text-gray-600 mb-4">Одно понятное действие — без лишних переходов по меню.</p>
    <div class="flex flex-col sm:flex-row flex-wrap gap-2">
        @foreach($leanActions as $action)
            @php
                $cls = match($action['tone'] ?? 'default') {
                    'primary' => 'btn-primary',
                    'warn' => 'btn border-amber-300 bg-amber-50 text-amber-900 hover:bg-amber-100',
                    default => 'btn',
                };
            @endphp
            <a href="{{ $action['url'] }}" class="{{ $cls }} text-sm" @if(!empty($action['hint'])) title="{{ $action['hint'] }}" @endif>
                {{ $action['label'] }}
            </a>
        @endforeach
    </div>
</section>
@endif
