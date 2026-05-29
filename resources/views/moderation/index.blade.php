{{-- Очередь объявлений на проверку модератором. --}}
@extends('layouts.app')

@section('title', 'Модерация объявлений')

@section('content')
<div class="mb-8 flex flex-wrap justify-between gap-4">
    <div>
        <h1 class="text-4xl font-bold mb-2">Модерация объявлений</h1>
        <p class="text-gray-600">Объявления пользователей ждут проверки перед публикацией в каталоге. Для одобрения достаточно проверенных паспорта и выписки ЕГРН.</p>
    </div>
    <a href="{{ route('moderation.documents') }}" class="btn">Проверка документов продавцов</a>
</div>

@if(isset($errors) && $errors instanceof \Illuminate\Support\ViewErrorBag && $errors->any())
    <div class="mb-6 p-4 border border-red-200 bg-red-50 text-red-800 text-sm">{{ $errors->first() }}</div>
@endif

<form method="GET" action="{{ route('moderation.index') }}" class="card p-4 mb-6 flex flex-wrap gap-3 items-end">
    <div class="flex-1 min-w-[200px]">
        <label for="moderation-q" class="form-label">Поиск</label>
        <input type="search" id="moderation-q" name="q" value="{{ $q ?? '' }}" class="form-input" placeholder="Название, адрес, клиент, email">
    </div>
    <div class="min-w-[180px]">
        <label for="moderation-docs" class="form-label">Документы</label>
        <select id="moderation-docs" name="docs" class="form-input">
            <option value="all" @selected(($docs ?? 'all') === 'all')>Все</option>
            <option value="ready" @selected(($docs ?? '') === 'ready')>Готовы к одобрению</option>
            <option value="not_ready" @selected(($docs ?? '') === 'not_ready')>Не хватает документов</option>
        </select>
    </div>
    <div class="min-w-[180px]">
        <label for="moderation-sort" class="form-label">Сортировка</label>
        <select id="moderation-sort" name="sort" class="form-input">
            <option value="newest" @selected(($sort ?? 'newest') === 'newest')>Сначала новые</option>
            <option value="client" @selected(($sort ?? '') === 'client')>По клиенту (ФИО)</option>
            <option value="price" @selected(($sort ?? '') === 'price')>По цене</option>
        </select>
    </div>
    @if(in_array($sort ?? '', ['client', 'price'], true))
        <div class="min-w-[120px]">
            <label for="moderation-dir" class="form-label">Порядок</label>
            <select id="moderation-dir" name="dir" class="form-input">
                @if(($sort ?? '') === 'price')
                    <option value="asc" @selected(($dir ?? 'asc') === 'asc')>Дешевле</option>
                    <option value="desc" @selected(($dir ?? '') === 'desc')>Дороже</option>
                @else
                    <option value="asc" @selected(($dir ?? 'asc') === 'asc')>А → Я</option>
                    <option value="desc" @selected(($dir ?? '') === 'desc')>Я → А</option>
                @endif
            </select>
        </div>
    @endif
    <button type="submit" class="btn-primary">Применить</button>
    @if(!empty($q) || ($sort ?? 'newest') !== 'newest' || ($docs ?? 'all') !== 'all')
        <a href="{{ route('moderation.index') }}" class="btn text-sm">Сбросить</a>
    @endif
</form>

@if($properties->isEmpty())
    <div class="card p-8 text-center text-gray-600">Нет объявлений на модерации.</div>
@else
    <div class="space-y-6">
        @foreach($properties as $property)
            @php
                $docsOk = $docReadiness[$property->id] ?? false;
                $coreDocs = $moderationDocs[$property->id] ?? [];
                $clientName = $property->user
                    ? trim(($property->user->familia ?? '').' '.($property->user->imya ?? '').' '.($property->user->otchestvo ?? ''))
                    : '—';
            @endphp
            <div class="card p-6">
                <div class="flex flex-wrap gap-4 justify-between items-start mb-4">
                    <div>
                        <h2 class="text-xl font-bold">
                            <a href="{{ route('properties.show', $property) }}" class="hover:underline">{{ $property->nazvanie }}</a>
                        </h2>
                        <p class="text-sm text-gray-600 mt-1">{{ $property->gorod }}, {{ $property->adres_ulitsy }} · {{ number_format((float)$property->tsena, 0, ',', ' ') }} ₽</p>
                        @if($property->user)
                            <p class="text-sm text-gray-500">Клиент / собственник: {{ $clientName }}</p>
                        @endif
                        @if($property->sozdal_kak && $property->sozdal_kak !== 'client')
                            <p class="text-sm text-gray-500">
                                {{ \App\Support\PropertyListingAuthor::label($property->sozdal_kak) }}
                                @if($property->realtor)
                                    · риэлтор: {{ trim($property->realtor->familia.' '.$property->realtor->imya) }}
                                @endif
                            </p>
                        @endif
                        <p class="text-sm mt-1 {{ $docsOk ? 'text-green-700' : 'text-amber-700' }}">
                            @if($docsOk)
                                ✓ Паспорт и ЕГРН проверены — можно одобрить
                            @else
                                ⚠ Для одобрения нужны проверенные паспорт и выписка ЕГРН
                            @endif
                        </p>
                        @if(count($coreDocs) > 0)
                            <ul class="mt-2 space-y-1 text-sm">
                                @foreach($coreDocs as $doc)
                                    <li class="flex flex-wrap items-center gap-2">
                                        <span class="{{ $doc['verified'] ? 'text-green-700' : 'text-amber-700' }}">
                                            {{ $doc['verified'] ? '✓' : '○' }} {{ $doc['label'] }}
                                        </span>
                                        @if(!empty($doc['url']))
                                            <a href="{{ $doc['url'] }}" target="_blank" rel="noopener" class="underline text-brand-700">
                                                Просмотреть
                                                @if(($doc['source'] ?? '') === 'profile')
                                                    <span class="text-gray-500 no-underline">(профиль)</span>
                                                @endif
                                            </a>
                                        @elseif(!empty($doc['note']))
                                            <span class="text-gray-500 text-xs">{{ $doc['note'] }}</span>
                                        @elseif($doc['verified'])
                                            <span class="text-gray-500 text-xs">файл на сервере не найден — попросите перезагрузить</span>
                                        @endif
                                        @if(!empty($doc['data_lines']))
                                            @include('partials.document-data-display', [
                                                'lines' => $doc['data_lines'],
                                                'title' => null,
                                            ])
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                        <a href="{{ route('properties.documents', $property) }}" class="text-sm underline mt-2 inline-block">Все документы объекта</a>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <form action="{{ route('moderation.approve', $property) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="btn-primary" @disabled(!$docsOk)>Одобрить</button>
                        </form>
                        <button type="button" class="btn" onclick="document.getElementById('reject-{{ $property->id }}').classList.toggle('hidden')">Отклонить</button>
                    </div>
                </div>
                <div id="reject-{{ $property->id }}" class="{{ (int) session('moderation_reject_property_id', 0) === (int) $property->id ? '' : 'hidden' }} mt-4 pt-4 border-t border-gray-200">
                    <form action="{{ route('moderation.reject', $property) }}" method="POST" class="max-w-xl space-y-3">
                        @csrf
                        <label class="form-label">Причина отказа (увидит автор) *</label>
                        <textarea name="prichina_otkaza_mod" rows="3" required class="form-input" placeholder="Например: укажите корректный адрес; удалите недопустимые выражения в описании.">{{ (session('moderation_reject_property_id') === $property->id) ? old('prichina_otkaza_mod') : '' }}</textarea>
                        @error('prichina_otkaza_mod')
                            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                        <button type="submit" class="btn border-red-300 text-red-800 hover:bg-red-50">Отправить отказ</button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>
    <div class="mt-8">{{ $properties->links() }}</div>
@endif
@endsection
