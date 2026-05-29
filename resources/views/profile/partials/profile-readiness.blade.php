@php
    $profileDocs = $profileDocs ?? null;
    if (!$profileDocs && Auth::check()) {
        $profileDocs = \App\Support\UserProfileDocuments::summary(Auth::user());
    }
@endphp
@if($profileDocs)
    <div class="card p-5 mb-6 {{ $profileDocs['ready'] ? 'border-green-200 bg-green-50/50' : 'border-amber-200 bg-amber-50/40' }}">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="font-semibold text-slate-900">Готовность к размещению</p>
                <p class="text-sm text-slate-600 mt-1">
                    @if($profileDocs['ready'])
                        Паспорт в профиле проверен — можно проходить чек-лист документов на объявление.
                    @else
                        Заполните данные паспорта и загрузите скан — после проверки он засчитается в объявлении без повторной загрузки.
                    @endif
                </p>
            </div>
            @if($profileDocs['ready'])
                <span class="badge bg-green-100 text-green-800 shrink-0">Готов к размещению</span>
            @else
                <span class="badge bg-amber-100 text-amber-900 shrink-0">Нужен паспорт</span>
            @endif
        </div>
        <ul class="mt-4 grid sm:grid-cols-2 gap-2 text-sm">
            <li class="flex justify-between gap-2 rounded-lg border border-white/80 bg-white/60 px-3 py-2">
                <span>Паспорт</span>
                <span class="font-medium {{ $profileDocs['passport'] === 'verified' ? 'text-green-700' : ($profileDocs['passport'] === 'rejected' ? 'text-red-700' : 'text-amber-700') }}">
                    {{ \App\Support\UserProfileDocuments::statusText($profileDocs['passport']) }}
                </span>
            </li>
            <li class="flex justify-between gap-2 rounded-lg border border-white/80 bg-white/60 px-3 py-2">
                <span>ИНН / СНИЛС</span>
                <span class="font-medium {{ $profileDocs['inn'] === 'verified' ? 'text-green-700' : 'text-slate-500' }}">
                    {{ $profileDocs['inn'] === 'verified' ? 'Проверен' : 'Необязательно' }}
                </span>
            </li>
        </ul>
        @if(!$profileDocs['ready'])
            <a href="{{ route('profile.documents.index') }}" class="btn-primary inline-block mt-4 text-sm">Загрузить паспорт →</a>
        @endif
    </div>
@endif
