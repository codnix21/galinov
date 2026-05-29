@extends('layouts.app')

@section('title', 'Документы на объект')

@section('content')
@php
    $progress = $totalRequired > 0 ? (int) round(($verifiedCount / $totalRequired) * 100) : 0;
@endphp

<div class="max-w-3xl mx-auto space-y-6">
    <div>
        <a href="{{ route('properties.show', $property) }}" class="inline-flex items-center gap-1 text-sm font-medium text-brand-700 hover:underline">
            ← Назад к объявлению
        </a>
    </div>

    <div class="card p-6 sm:p-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0 flex-1">
                <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 mb-2">Документы на объект</h1>
                <p class="text-lg font-semibold text-slate-800 break-words">{{ $property->nazvanie }}</p>
                <p class="text-sm text-slate-600 mt-1">
                    {{ $property->type_name }} · {{ $property->operation_name }}
                    @if($property->gorod)
                        · {{ $property->gorod }}
                    @endif
                </p>
            </div>
            <div class="shrink-0 text-right">
                <p class="text-2xl font-bold text-brand-700">{{ $verifiedCount }}/{{ $totalRequired }}</p>
                <p class="text-xs text-slate-500">проверено</p>
            </div>
        </div>

        <div class="mt-4 h-2 rounded-full bg-slate-100 overflow-hidden">
            <div class="h-full rounded-full bg-brand-600 transition-all duration-300" style="width: {{ $progress }}%"></div>
        </div>

        <p class="mt-4 text-sm text-slate-600">{{ $requirementsSummary }}</p>
    </div>

    @if(!empty($wasModerationRejected))
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
            <p class="font-semibold mb-1">Модератор вернул объявление на доработку</p>
            <p class="whitespace-pre-line">{{ $property->prichina_otkaza_mod }}</p>
            <p class="mt-2 text-red-800">
                Обновите <a href="{{ route('profile.documents.index') }}" class="underline font-medium">персональные данные и паспорт в профиле</a>,
                при необходимости замените документы ниже и снова нажмите «Отправить на модерацию».
            </p>
        </div>
    @endif

    @if($isOwner)
        <div class="rounded-xl border border-brand-200 bg-brand-50/90 px-4 py-3 text-sm text-brand-950">
            @if($canEditDocuments ?? false)
                В черновике можно <strong>заменить любой документ</strong> (кроме шага на проверке). После правок снова отправьте объявление на модерацию.
            @else
                Загружайте документы <strong>по шагам сверху вниз</strong>. Следующий шаг откроется после проверки предыдущего.
            @endif
        </div>
    @else
        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
            Просмотр для администратора. Загружает владелец: <strong>{{ $property->user?->name ?? '—' }}</strong>.
        </div>
    @endif

    @if(session('success'))
        <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-900">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($ready)
        <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-900">
            ✓ Все документы проверены — можно отправить объявление на модерацию.
        </div>
    @endif

    <div class="card p-6">
        <h2 class="text-lg font-bold mb-1">Чек-лист · {{ $property->operatsiya === 'rent' ? 'аренда' : 'продажа' }}</h2>
        @if($canEditDocuments ?? false)
            <p class="text-sm text-slate-600 mb-5">Режим правки: можно обновить отдельные документы и отправить объявление повторно.</p>
        @elseif($canUpload && $currentStepTip)
            <p class="text-sm text-slate-600 mb-5">Сейчас шаг {{ array_search($currentStepTip, $required, true) + 1 }} из {{ $totalRequired }}</p>
        @else
            <p class="text-sm text-slate-600 mb-5">Все шаги пройдены</p>
        @endif

        <ol class="space-y-4">
            @foreach($required as $index => $tip)
                @php
                    $stepNum = $index + 1;
                    $ok = in_array($tip, $docStatus['verified'], true);
                    $checking = ($documents->get($tip)?->first()?->status ?? '') === 'checking';
                    $rejected = in_array($tip, $docStatus['rejected'], true);
                    $isCurrent = $currentStepTip === $tip;
                    $canUploadStep = $canUpload && \App\Support\PropertyDocumentRules::canOwnerUploadStep($property, $tip);
                    $locked = $canUpload && !($canEditDocuments ?? false) && !$ok && !\App\Support\PropertyDocumentRules::isStepAvailable($property, $tip);
                    $isEgrn = \App\Support\PropertyDocumentRules::isEgrnStep($tip);
                    $prevLabel = \App\Support\PropertyDocumentRules::previousStepLabel($property, $tip);
                    $hasPropertyFile = (bool) ($documents->get($tip)?->first());
                    $okFromProfile = $tip === 'passport' && !empty($profilePassportVerified) && !$hasPropertyFile;
                    $showForm = $canUploadStep && !$checking && !$okFromProfile
                        && (($canEditDocuments ?? false) || (!$ok && $isCurrent));
                @endphp
                <li
                    id="step-{{ $tip }}"
                    class="rounded-xl border overflow-hidden {{ $ok ? 'border-green-200' : ($isCurrent ? 'border-brand-400 ring-2 ring-brand-500/20' : ($rejected ? 'border-red-200' : 'border-slate-200')) }} {{ $locked ? 'opacity-60' : '' }}"
                >
                    <div class="flex items-start gap-3 px-4 py-3 {{ $ok ? 'bg-green-50/60' : ($isCurrent ? 'bg-brand-50/50' : ($rejected ? 'bg-red-50/40' : 'bg-white')) }}">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-sm font-bold {{ $ok ? 'bg-green-600 text-white' : ($isCurrent ? 'bg-brand-600 text-white' : ($rejected ? 'bg-red-500 text-white' : 'bg-slate-200 text-slate-600')) }}">
                            @if($ok)
                                ✓
                            @else
                                {{ $stepNum }}
                            @endif
                        </span>
                        <div class="min-w-0 flex-1 pt-0.5">
                            <p class="font-semibold text-slate-900">{{ $labels[$tip] ?? $tip }}</p>
                            @if($ok)
                                <p class="text-xs text-green-700 mt-0.5">
                                    Проверен
                                    @if($okFromProfile)
                                        <span class="text-slate-500">· по профилю</span>
                                    @endif
                                </p>
                                @php
                                    $viewDoc = $documents->get($tip)?->first()
                                        ?? ($tip === 'passport' && $okFromProfile ? $profilePassportDocument : null);
                                    $viewUrl = $viewDoc?->view_url;
                                @endphp
                                @if(($canViewFiles ?? false) && $viewUrl)
                                    <a href="{{ $viewUrl }}" target="_blank" rel="noopener" class="text-xs text-brand-700 underline mt-1 inline-block">Открыть файл</a>
                                @elseif(($canViewFiles ?? false) && $viewDoc && \App\Support\DocumentStorage::isJsonRegistryFile($viewDoc->put_fajla))
                                    <span class="text-xs text-slate-500 mt-1 inline-block">Подтверждено по кадастровому номеру</span>
                                @endif
                            @elseif($checking)
                                <p class="text-xs text-slate-500 mt-0.5">Идёт автопроверка…</p>
                            @elseif($rejected)
                                <p class="text-xs text-red-600 mt-0.5">Отклонён — загрузите снова</p>
                            @elseif($locked && $prevLabel)
                                <p class="text-xs text-slate-500 mt-0.5">Сначала: {{ $prevLabel }}</p>
                            @elseif($isCurrent)
                                <p class="text-xs text-brand-800 mt-0.5 font-medium">Текущий шаг — заполните ниже</p>
                            @else
                                <p class="text-xs text-slate-500 mt-0.5">Не загружен</p>
                            @endif
                        </div>
                    </div>

                    @if($showForm)
                        <div class="border-t border-slate-200/80 bg-white px-4 py-4">
                            @if($ok && ($canEditDocuments ?? false))
                                <p class="text-sm text-amber-900 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 mb-4">
                                    Загрузите новый файл, если нужно исправить документ — он снова отправится на проверку.
                                </p>
                            @endif
                            @if($isEgrn)
                                <p class="text-sm text-slate-600 mb-4">
                                    Выписку ЕГРН можно подтвердить <strong>номером</strong> или <strong>сканом PDF/JPG</strong>.
                                    Сверить открытые сведения можно на
                                    <a href="{{ $rosreestrMapUrl }}" target="_blank" rel="noopener" class="text-brand-700 underline">карте Росреестра</a>
                                    (официальный сайт, без встраивания в CRM).
                                </p>

                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">Способ 1 — кадастровый номер</p>
                                <form method="POST" action="{{ route('properties.documents.egrn-check', $property) }}" class="space-y-3 mb-5">
                                    @csrf
                                    <div>
                                        <label for="kadastrovy_nomer_{{ $tip }}" class="form-label">Кадастровый номер</label>
                                        <input
                                            type="text"
                                            id="kadastrovy_nomer_{{ $tip }}"
                                            name="kadastrovy_nomer"
                                            class="form-input font-mono"
                                            value="{{ old('kadastrovy_nomer', $property->kadastrovy_nomer) }}"
                                            placeholder="38:36:000000:12345"
                                            autocomplete="off"
                                            required
                                        >
                                    </div>
                                    <button type="submit" class="btn-primary w-full sm:w-auto">
                                        {{ ($rejected || $ok) ? 'Повторить проверку по номеру' : 'Проверить по номеру' }}
                                    </button>
                                </form>

                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2 pt-2 border-t border-slate-100">Способ 2 — скан выписки</p>
                                <form method="POST" action="{{ route('properties.documents.store', $property) }}" enctype="multipart/form-data" class="space-y-3">
                                    @csrf
                                    <input type="hidden" name="tip" value="{{ $tip }}">
                                    <div>
                                        <label for="egrn_file_{{ $tip }}" class="form-label">Файл выписки ЕГРН</label>
                                        <input
                                            type="file"
                                            id="egrn_file_{{ $tip }}"
                                            name="file"
                                            class="form-input file:mr-4 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2.5 file:text-sm file:font-medium file:text-brand-800"
                                            required
                                            accept=".pdf,.jpg,.jpeg,.png"
                                        >
                                    </div>
                                    <div>
                                        <label for="egrn_kad_{{ $tip }}" class="form-label">Кадастровый номер (если есть в выписке)</label>
                                        <input
                                            type="text"
                                            id="egrn_kad_{{ $tip }}"
                                            name="kadastrovy_nomer"
                                            class="form-input font-mono"
                                            value="{{ old('kadastrovy_nomer', $property->kadastrovy_nomer) }}"
                                            placeholder="Необязательно"
                                            autocomplete="off"
                                        >
                                    </div>
                                    <div>
                                        <label for="nomer_vypiski_{{ $tip }}" class="form-label">Номер выписки ЕГРН</label>
                                        <input
                                            type="text"
                                            id="nomer_vypiski_{{ $tip }}"
                                            name="nomer_vypiski"
                                            class="form-input"
                                            value="{{ old('nomer_vypiski') }}"
                                            placeholder="Необязательно"
                                            autocomplete="off"
                                        >
                                    </div>
                                    <button type="submit" class="btn w-full sm:w-auto border-brand-300 text-brand-800 hover:bg-brand-50">
                                        {{ $ok ? 'Заменить выписку' : 'Загрузить выписку и проверить' }}
                                    </button>
                                </form>
                            @else
                                <p class="text-sm text-slate-600 mb-3">Загрузите скан или фото (PDF, JPG).</p>
                                <form method="POST" action="{{ route('properties.documents.store', $property) }}" enctype="multipart/form-data" class="space-y-3">
                                    @csrf
                                    <input type="hidden" name="tip" value="{{ $tip }}">
                                    <div>
                                        <label for="file_{{ $tip }}" class="form-label">Файл</label>
                                        <input
                                            type="file"
                                            id="file_{{ $tip }}"
                                            name="file"
                                            class="form-input file:mr-4 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2.5 file:text-sm file:font-medium file:text-brand-800"
                                            required
                                            accept=".pdf,.jpg,.jpeg,.png"
                                        >
                                    </div>
                                    <button type="submit" class="btn-primary w-full sm:w-auto">
                                        {{ ($rejected || $ok) ? 'Заменить документ' : 'Загрузить и перейти дальше' }}
                                    </button>
                                </form>
                            @endif
                        </div>
                    @elseif(($canEditDocuments ?? false) && $okFromProfile)
                        <div class="border-t border-slate-200/80 bg-white px-4 py-4 text-sm text-slate-700">
                            Паспорт учтён из профиля.
                            <a href="{{ route('profile.documents.index') }}#step-passport" class="text-brand-700 underline font-medium">Изменить паспорт или данные в профиле</a>
                        </div>
                    @endif
                </li>
            @endforeach
        </ol>
    </div>

    @if($property->status_obyavleniya === 'draft' && $isOwner)
        <div class="card p-6 space-y-3">
            <p class="text-sm text-slate-600">
                <a href="{{ route('profile.documents.index') }}" class="text-brand-700 underline">Профиль → документы и паспорт</a>
                ·
                <a href="{{ route('properties.edit', $property) }}" class="text-brand-700 underline">Редактировать текст объявления</a>
            </p>
            <form method="POST" action="{{ route('properties.publish', $property) }}">
                @csrf
                <button type="submit" class="btn-primary w-full" @disabled(!$ready)>
                    {{ !empty($wasModerationRejected) ? 'Отправить на модерацию снова' : 'Отправить на модерацию' }}
                </button>
            </form>
            @if(!$ready)
                <p class="mt-1 text-xs text-center text-slate-500">Кнопка станет активной, когда все документы в чек-листе будут проверены.</p>
            @endif
        </div>
    @endif
</div>

@if($currentStepTip)
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const el = document.getElementById('step-{{ $currentStepTip }}');
                if (el) {
                    el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
            });
        </script>
    @endpush
@endif
@endsection
