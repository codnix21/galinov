@extends('layouts.app')

@section('title', 'Проверка документов')

@section('content')
<div class="mb-8 flex flex-wrap justify-between gap-4">
    <div>
        <h1 class="text-2xl sm:text-4xl font-bold mb-2">Проверка документов</h1>
        <p class="text-gray-600 text-sm">ЕГРН, ЕГРЮЛ, право собственности · автопроверка {{ \App\Services\DocumentVerificationService::PROVIDER }}</p>
    </div>
    <a href="{{ route('moderation.index') }}" class="btn">Модерация объявлений</a>
</div>

<form method="GET" action="{{ route('moderation.documents') }}" class="card p-4 mb-6 flex flex-wrap gap-3 items-end">
    <div class="flex-1 min-w-[200px]">
        <label for="doc-q" class="form-label">Поиск</label>
        <input type="search" id="doc-q" name="q" value="{{ $q ?? '' }}" class="form-input" placeholder="Клиент, email, объект, № объявления">
    </div>
    <div class="min-w-[160px]">
        <label for="doc-status" class="form-label">Статус</label>
        <select id="doc-status" name="status" class="form-input">
            <option value="queue" @selected(($status ?? 'queue') === 'queue')>В очереди</option>
            <option value="all" @selected(($status ?? '') === 'all')>Все</option>
            <option value="pending" @selected(($status ?? '') === 'pending')>На модерации</option>
            <option value="checking" @selected(($status ?? '') === 'checking')>Автопроверка</option>
            <option value="rejected" @selected(($status ?? '') === 'rejected')>Отклонённые</option>
            <option value="verified" @selected(($status ?? '') === 'verified')>Проверенные</option>
        </select>
    </div>
    <div class="min-w-[180px]">
        <label for="doc-tip" class="form-label">Тип документа</label>
        <select id="doc-tip" name="tip" class="form-input">
            <option value="">Все типы</option>
            @foreach($tipLabels as $code => $label)
                <option value="{{ $code }}" @selected(($tip ?? '') === $code)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="min-w-[150px]">
        <label for="doc-scope" class="form-label">Привязка</label>
        <select id="doc-scope" name="scope" class="form-input">
            <option value="" @selected(($scope ?? '') === '')>Все</option>
            <option value="profile" @selected(($scope ?? '') === 'profile')>Профиль</option>
            <option value="property" @selected(($scope ?? '') === 'property')>К объекту</option>
        </select>
    </div>
    <div class="min-w-[160px]">
        <label for="doc-sort" class="form-label">Сортировка</label>
        <select id="doc-sort" name="sort" class="form-input">
            <option value="queue" @selected(($sort ?? 'queue') === 'queue')>По очереди</option>
            <option value="newest" @selected(($sort ?? '') === 'newest')>Сначала новые</option>
            <option value="oldest" @selected(($sort ?? '') === 'oldest')>Сначала старые</option>
            <option value="client" @selected(($sort ?? '') === 'client')>По клиенту</option>
        </select>
    </div>
    @if(($sort ?? '') === 'client')
        <div class="min-w-[120px]">
            <label for="doc-dir" class="form-label">Порядок</label>
            <select id="doc-dir" name="dir" class="form-input">
                <option value="asc" @selected(($dir ?? 'asc') === 'asc')>А → Я</option>
                <option value="desc" @selected(($dir ?? '') === 'desc')>Я → А</option>
            </select>
        </div>
    @endif
    <button type="submit" class="btn-primary">Применить</button>
    @if(!empty($q) || ($status ?? 'queue') !== 'queue' || !empty($tip) || !empty($scope) || ($sort ?? 'queue') !== 'queue')
        <a href="{{ route('moderation.documents') }}" class="btn text-sm">Сбросить</a>
    @endif
</form>

@forelse($documents as $doc)
    <div class="card p-5 sm:p-6 mb-4">
        <p class="font-bold">{{ $tipLabels[$doc->tip] ?? $doc->tip }}</p>
        <p class="text-sm text-gray-600">
            {{ trim(($doc->user->familia ?? '').' '.($doc->user->imya ?? '')) }}
            · {{ $doc->user->email_polzovatela ?? '' }}
        </p>
        @if($doc->property)
            <p class="text-sm mt-1">
                <a href="{{ route('properties.show', $doc->property) }}" class="underline">Объект #{{ $doc->property->id }}</a>
                — {{ $doc->property->nazvanie }}
            </p>
        @endif
        @if($doc->vneshniy_id)
            <p class="text-xs text-gray-500 mt-1">{{ app(\App\Services\DocumentVerificationService::class)->externalSummary($doc) }}</p>
        @endif
        <p class="mt-2 text-sm"><span class="badge">{{ $doc->statusLabel() }}</span></p>
        @if($doc->view_url)
            <a href="{{ $doc->view_url }}" target="_blank" rel="noopener" class="text-sm underline mt-2 inline-block">Открыть файл</a>
        @elseif(\App\Support\DocumentStorage::isJsonRegistryFile($doc->put_fajla))
            <span class="text-sm text-gray-500 mt-2 inline-block">Подтверждено по кадастровому номеру (без скана)</span>
        @endif
        @if(in_array($doc->status, ['pending', 'checking', 'rejected'], true))
            <div class="mt-4 flex flex-wrap gap-2">
                <form method="POST" action="{{ route('moderation.documents.recheck', $doc) }}" class="inline">
                    @csrf
                    <button type="submit" class="btn text-sm">Повторить автопроверку</button>
                </form>
                <form method="POST" action="{{ route('moderation.documents.verify', $doc) }}" class="inline">
                    @csrf
                    <input type="hidden" name="action" value="verify">
                    <button type="submit" class="btn-primary text-sm">Подтвердить вручную</button>
                </form>
                <form method="POST" action="{{ route('moderation.documents.verify', $doc) }}" class="inline">
                    @csrf
                    <input type="hidden" name="action" value="reject">
                    <button type="submit" class="btn text-sm">Отклонить</button>
                </form>
            </div>
        @endif
    </div>
@empty
    <div class="card p-8 text-center text-gray-600">По выбранным фильтрам документов не найдено.</div>
@endforelse

<div class="mt-6">{{ $documents->links() }}</div>
@endsection
