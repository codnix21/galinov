@php
    $st = $st ?? ($property->status_obyavleniya ?? $property->status);
    $docsReady = (bool) ($docsReady ?? false);
    $profileVerifiedTips = $profileVerifiedTips ?? [];
    $passportFromProfile = in_array('passport', $profileVerifiedTips, true);
    $canPublishToModeration = (bool) ($canPublishToModeration ?? false);
    $canManage = (bool) ($canManage ?? true);

    $steps = [
        ['key' => 'draft', 'label' => 'Черновик'],
        ['key' => 'docs', 'label' => 'Документы'],
        ['key' => 'moderation', 'label' => 'Модерация'],
        ['key' => 'published', 'label' => 'В каталоге'],
    ];

    $current = match (true) {
        $st === 'active' => 'published',
        $st === 'pending_review' => 'moderation',
        $st === 'draft' && !$docsReady => 'docs',
        default => 'draft',
    };

    $wasRejected = $st === 'draft' && !empty($property->prichina_otkaza_mod);

    $hint = match (true) {
        $wasRejected => 'Исправьте замечания модератора: профиль (паспорт и данные), документы объекта, текст объявления — затем отправьте снова.',
        $current === 'docs' => $passportFromProfile
            ? 'Паспорт из профиля уже учтён. Загрузите остальные документы на объект.'
            : 'Загрузите документы на объект — без проверки отправка на модерацию недоступна.',
        $current === 'moderation' => 'Объявление на проверке. Если отклонят — исправьте документы и данные в профиле и отправьте снова.',
        $current === 'published' => 'Объявление в каталоге. Можно редактировать; изменения «Активно» снова уходят на модерацию.',
        default => $docsReady
            ? 'Все документы готовы — отправьте объявление на модерацию.'
            : 'Заполните объявление, затем загрузите документы и отправьте на модерацию.',
    };
@endphp

<div class="card p-5 mb-6 border-slate-200 bg-white">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
        <p class="font-semibold text-slate-900">Статус публикации</p>
        <div class="flex flex-wrap gap-2">
            @if($passportFromProfile)
                <span class="badge bg-green-100 text-green-800">Паспорт в профиле ✓</span>
            @endif
            <span class="badge">{{ $current === 'published' ? 'В каталоге' : ($current === 'moderation' ? 'На модерации' : ($current === 'docs' ? 'Нужны документы' : 'Черновик')) }}</span>
        </div>
    </div>

    <ol class="grid grid-cols-2 sm:grid-cols-4 gap-2">
        @foreach($steps as $s)
            @php
                $isCurrent = $s['key'] === $current;
                $stepIndex = array_search($s['key'], array_column($steps, 'key'), true);
                $currentIndex = array_search($current, array_column($steps, 'key'), true);
                $done = $stepIndex !== false && $currentIndex !== false && $stepIndex < $currentIndex;
            @endphp
            <li class="rounded-xl border px-2 py-2.5 text-center min-h-[3.25rem] min-w-0 flex items-center justify-center {{ $isCurrent ? 'border-brand-400 bg-brand-50 ring-1 ring-brand-400/30' : ($done ? 'border-green-200 bg-green-50/60' : 'border-slate-200 bg-slate-50') }}">
                <span class="block w-full font-medium text-xs leading-tight text-center {{ $isCurrent ? 'text-brand-900' : ($done ? 'text-green-800' : 'text-slate-700') }}">
                    {{ $s['label'] }}
                </span>
            </li>
        @endforeach
    </ol>

    <p class="text-sm text-slate-600 mt-3">{{ $hint }}</p>

    @if($canManage)
        <div class="mt-4 pt-4 border-t border-slate-100 grid grid-cols-1 gap-2">
            <a href="{{ route('profile.documents.index') }}" class="btn text-sm text-center w-full">
                Профиль: паспорт и данные
            </a>
            <a href="{{ route('properties.documents', $property) }}" class="btn text-sm text-center w-full">
                {{ $docsReady ? 'Документы объекта' : 'Открыть документы' }}
            </a>
            <a href="{{ route('properties.edit', $property) }}" class="btn text-sm text-center w-full">
                Редактировать объявление
            </a>
            @if($st === 'draft')
                <form method="POST" action="{{ route('properties.publish', $property) }}" class="w-full">
                    @csrf
                    <button type="submit" class="btn-primary w-full text-sm" @disabled(!$canPublishToModeration)>
                        {{ $wasRejected ? 'Отправить на модерацию снова' : 'Отправить на модерацию' }}
                    </button>
                </form>
            @endif
        </div>
        @if($st === 'draft' && !$canPublishToModeration)
            <p class="text-xs text-slate-500 mt-2">
                @if(!$docsReady)
                    Кнопка станет активной после прохождения всех шагов чек-листа документов.
                @else
                    Завершите заполнение черновика перед отправкой.
                @endif
            </p>
        @endif
    @endif
</div>
