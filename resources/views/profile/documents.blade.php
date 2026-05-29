@extends('layouts.app')

@section('title', 'Документы продавца')

@section('content')
@php
    $profileDocs = $profileDocs ?? \App\Support\UserProfileDocuments::summary(Auth::user());
    $personalDataFilled = (bool) ($personalDataFilled ?? false);
    $documentsByTip = $documentsByTip ?? collect();

    $profileSteps = [
        [
            'key' => 'personal',
            'num' => 1,
            'title' => 'Данные паспорта',
            'subtitle' => 'Серия, номер, кем и когда выдан — для договора',
            'done' => $personalDataFilled,
        ],
        [
            'key' => 'passport',
            'num' => 2,
            'title' => 'Скан паспорта',
            'subtitle' => 'Разворот с фото — проверка модератором',
            'done' => $profileDocs['passport_verified'] ?? false,
            'status' => $profileDocs['passport'] ?? 'missing',
        ],
        [
            'key' => 'inn',
            'num' => 3,
            'title' => 'ИНН / СНИЛС',
            'subtitle' => 'Необязательно, но ускоряет проверку',
            'done' => $profileDocs['inn_verified'] ?? false,
            'status' => $profileDocs['inn'] ?? 'missing',
            'optional' => true,
        ],
    ];
@endphp

<div class="max-w-3xl mx-auto space-y-6">
    <div>
        <a href="{{ route('profile.edit') }}" class="inline-flex items-center gap-1 text-sm font-medium text-brand-700 hover:underline">
            ← Профиль
        </a>
    </div>

    <div class="card p-6 sm:p-8">
        <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 mb-2">Документы продавца</h1>
        <p class="text-sm text-slate-600">
            Здесь — <strong>личные документы</strong> (паспорт и при желании ИНН). Выписку ЕГРН и право собственности загружайте
            <strong>в карточке каждого объявления</strong> — они привязаны к конкретному объекту.
        </p>
    </div>

    @include('profile.partials.profile-readiness')

    @if(session('success'))
        <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-900">{{ session('success') }}</div>
    @endif

    @if(isset($errors) && $errors instanceof \Illuminate\Support\ViewErrorBag && $errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
        Пройдите шаги <strong>1 → 2</strong> в профиле. Шаг 3 — по желанию. После проверки паспорта он автоматически учтётся в документах объявления.
    </div>

    {{-- Шаг 1: текстовые поля --}}
    <div id="step-personal" class="card p-6 sm:p-8 {{ $personalDataFilled ? 'border-green-200' : 'border-brand-300 ring-1 ring-brand-500/20' }}">
        <div class="flex items-start gap-3 mb-6">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-sm font-bold {{ $personalDataFilled ? 'bg-green-600 text-white' : 'bg-brand-600 text-white' }}">
                {{ $personalDataFilled ? '✓' : '1' }}
            </span>
            <div>
                <h2 class="text-lg font-bold text-slate-900">Данные паспорта</h2>
                <p class="text-sm text-slate-600">Заполните поля — они нужны для договора и проверки личности</p>
            </div>
        </div>
        @include('profile.partials.personal-data-form')
    </div>

    {{-- Шаги 2–3: загрузка сканов --}}
    @foreach($profileSteps as $step)
        @if($step['key'] === 'personal')
            @continue
        @endif
        @php
            $tip = $step['key'];
            $latest = $documentsByTip->get($tip)?->first();
            $status = $step['status'] ?? 'missing';
            $isVerified = $step['done'];
            $isRejected = $status === 'rejected';
            $isPending = in_array($status, ['pending', 'checking'], true);
            $optional = !empty($step['optional']);
            $canUpload = !$isVerified && !$isPending;
            $locked = $tip === 'passport' && !$personalDataFilled && !$isVerified;
        @endphp
        <div
            id="step-{{ $tip }}"
            class="card p-6 overflow-hidden {{ $isVerified ? 'border-green-200' : ($isRejected ? 'border-red-200' : ($locked ? 'border-slate-200 opacity-80' : 'border-brand-300')) }}"
        >
            <div class="flex items-start gap-3 mb-4">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-sm font-bold {{ $isVerified ? 'bg-green-600 text-white' : ($isRejected ? 'bg-red-500 text-white' : 'bg-brand-600 text-white') }}">
                    {{ $isVerified ? '✓' : $step['num'] }}
                </span>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-lg font-bold text-slate-900">{{ $step['title'] }}</h2>
                        @if($optional)
                            <span class="text-xs text-slate-500 font-normal">необязательно</span>
                        @endif
                    </div>
                    <p class="text-sm text-slate-600">{{ $step['subtitle'] }}</p>
                    <p class="text-xs mt-1 font-medium {{ $isVerified ? 'text-green-700' : ($isRejected ? 'text-red-600' : ($isPending ? 'text-amber-700' : 'text-slate-500')) }}">
                        {{ \App\Support\UserProfileDocuments::statusText($status) }}
                        @if($latest && $latest->sozdano_at)
                            · {{ $latest->sozdano_at->format('d.m.Y H:i') }}
                        @endif
                    </p>
                    @if($isRejected && $latest?->kommentariy_mod)
                        <p class="text-xs text-red-700 mt-1">{{ $latest->kommentariy_mod }}</p>
                    @endif
                    @include('partials.document-view-link', [
                        'viewUrl' => $latest?->view_url,
                        'egrnJsonOnly' => false,
                        'hasPathButMissing' => $latest && $latest->put_fajla && !$latest->view_url,
                    ])
                    @php
                        $profileDataLines = $tip === 'passport'
                            ? \App\Support\DocumentDataFields::personalDataLines($user->personalData)
                            : ($latest?->dataDisplayLines() ?? []);
                    @endphp
                    @include('partials.document-data-display', [
                        'lines' => $profileDataLines,
                        'title' => $profileDataLines !== [] ? 'Реквизиты' : null,
                    ])
                </div>
            </div>

            @if($locked)
                <p class="text-sm text-slate-600 rounded-lg bg-slate-50 border border-slate-200 px-4 py-3">
                    Сначала заполните данные паспорта в шаге 1 выше.
                </p>
            @elseif($isVerified)
                <p class="text-sm text-green-800 rounded-lg bg-green-50 border border-green-200 px-4 py-3 mb-4">
                    Документ проверен. Если модератор попросил исправить данные — загрузите новый скан: файл снова уйдёт на проверку.
                </p>
                <form method="POST" action="{{ route('profile.documents.store') }}" enctype="multipart/form-data" class="space-y-4 border-t border-slate-100 pt-4">
                    @csrf
                    <input type="hidden" name="tip" value="{{ $tip }}">
                    <div>
                        <label class="form-label">Новый файл (PDF, JPG, PNG)</label>
                        <input type="file" name="file" class="form-input" required accept=".pdf,.jpg,.jpeg,.png">
                    </div>
                    <button type="submit" class="btn border-brand-300 text-brand-800 hover:bg-brand-50">
                        Заменить документ
                    </button>
                </form>
            @elseif($isPending)
                <p class="text-sm text-amber-900 rounded-lg bg-amber-50 border border-amber-200 px-4 py-3">
                    Файл на проверке. Обычно это занимает немного времени — обновите страницу позже.
                </p>
            @elseif($canUpload)
                <form method="POST" action="{{ route('profile.documents.store') }}" enctype="multipart/form-data" class="space-y-4 border-t border-slate-100 pt-4">
                    @csrf
                    <input type="hidden" name="tip" value="{{ $tip }}">
                    @if($tip === 'passport')
                        <p class="text-sm text-slate-600 rounded-lg bg-slate-50 border border-slate-200 px-3 py-2">
                            Данные паспорта заполняются в <a href="#step-personal" class="text-brand-700 underline font-medium">шаге 1</a>.
                            Здесь прикрепите только скан разворота с фото.
                        </p>
                    @elseif(\App\Support\DocumentDataFields::hasFields($tip))
                        @include('partials.document-data-fields', [
                            'tip' => $tip,
                            'values' => old('dannye', $latest?->dannye_json ?? []),
                            'idPrefix' => 'profile_' . $tip,
                        ])
                    @endif
                    <div>
                        <label class="form-label">Файл (PDF, JPG, PNG, до 10 МБ) *</label>
                        <input type="file" name="file" class="form-input" required accept=".pdf,.jpg,.jpeg,.png">
                    </div>
                    <button type="submit" class="btn-primary">
                        {{ $isRejected ? 'Загрузить снова' : 'Отправить на проверку' }}
                    </button>
                </form>
            @endif
        </div>
    @endforeach

    {{-- ЕГРН на объектах --}}
    <div class="card p-6 border-slate-200">
        <h2 class="text-lg font-bold text-slate-900 mb-1">Шаг 4 · Документы на объект</h2>
        <p class="text-sm text-slate-600 mb-4">
            Выписка ЕГРН, право собственности и остальное — отдельно для каждого объявления.
        </p>
        @if($listingProperties->isNotEmpty())
            <ul class="space-y-2">
                @foreach($listingProperties as $prop)
                    @php $pst = $prop->status_obyavleniya ?? $prop->status; @endphp
                    <li class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-slate-200 px-4 py-3">
                        <span class="font-medium text-slate-900 truncate">{{ $prop->nazvanie }}</span>
                        <a href="{{ route('properties.documents', $prop) }}" class="btn text-sm shrink-0">
                            Документы →
                        </a>
                    </li>
                @endforeach
            </ul>
            <p class="text-xs text-slate-500 mt-3">
                <a href="{{ route('properties.drafts') }}" class="underline hover:no-underline">Все черновики</a>
                ·
                <a href="{{ route('properties.create') }}" class="underline hover:no-underline">Новое объявление</a>
            </p>
        @else
            <p class="text-sm text-slate-600 mb-3">Объявлений пока нет.</p>
            <a href="{{ route('properties.create') }}" class="btn-primary text-sm">Создать объявление</a>
        @endif
    </div>

    @if($documents->isNotEmpty())
        <div class="card p-6">
            <h2 class="text-lg font-bold mb-4">История загрузок</h2>
            <ul class="space-y-2">
                @foreach($documents as $doc)
                    <li class="flex flex-wrap justify-between items-center gap-2 rounded-lg border border-slate-100 px-3 py-2 text-sm">
                        <span>
                            {{ $tipLabels[$doc->tip] ?? $doc->tip }}
                            <span class="text-slate-500">· {{ $doc->sozdano_at?->format('d.m.Y H:i') }}</span>
                        </span>
                        <span class="badge
                            @if($doc->status === 'verified') bg-green-100 text-green-800
                            @elseif($doc->status === 'rejected') bg-red-100 text-red-800
                            @else bg-amber-100 text-amber-800 @endif">
                            {{ $doc->statusLabel() }}
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
@endsection
