{{-- Журнал изменений системы (админ). --}}
@extends('layouts.app')

@section('title', 'Журнал изменений')

@section('content')
@php use App\Support\AuditJournalDisplay; use App\Models\Property; use App\Models\Contract; use App\Models\User; @endphp
<div class="mb-8">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-4xl font-bold mb-2">Журнал изменений</h1>
            <p class="text-gray-600">История действий с объявлениями, договорами и пользователями</p>
        </div>
        <a href="{{ route('admin.dashboard') }}" class="btn">← Админ-панель</a>
    </div>
</div>

<div class="card p-6 mb-8">
    <h2 class="text-xl font-bold mb-4">Поиск и фильтры</h2>
    <form method="GET" action="{{ route('admin.audit-logs') }}" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div class="md:col-span-2 lg:col-span-3">
            <label for="search" class="form-label">Поиск</label>
            <input type="text" id="search" name="search" value="{{ $filters['search'] ?? '' }}"
                class="form-input" placeholder="ФИО, email, действие, ID объекта, текст изменений…">
        </div>
        <div>
            <label for="obyekt" class="form-label">Тип объекта</label>
            <select id="obyekt" name="obyekt" class="form-input">
                <option value="">Все</option>
                <option value="property" @selected(($filters['obyekt'] ?? '') === 'property')>Объявления</option>
                <option value="contract" @selected(($filters['obyekt'] ?? '') === 'contract')>Договоры</option>
                <option value="user" @selected(($filters['obyekt'] ?? '') === 'user')>Пользователи</option>
            </select>
        </div>
        <div>
            <label for="deystvie" class="form-label">Действие</label>
            <select id="deystvie" name="deystvie" class="form-input">
                <option value="">Любое</option>
                @foreach($deystviya as $kod => $label)
                    <option value="{{ $kod }}" @selected(($filters['deystvie'] ?? '') === $kod)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="date_from" class="form-label">Дата с</label>
            <input type="date" id="date_from" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="form-input">
        </div>
        <div>
            <label for="date_to" class="form-label">Дата по</label>
            <input type="date" id="date_to" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="form-input">
        </div>
        <div class="md:col-span-2 lg:col-span-3 flex flex-wrap gap-3 pt-2">
            <button type="submit" class="btn-primary">Найти</button>
            <a href="{{ route('admin.audit-logs') }}" class="btn">Сбросить</a>
        </div>
    </form>
</div>

@if($logs->count() > 0)
    <p class="text-sm text-gray-500 mb-4">Найдено записей: {{ $logs->total() }}</p>
    <div class="space-y-4">
        @foreach($logs as $zapis)
            @php
                $izmeneniyaRaw = AuditJournalDisplay::razobratDetalizatsiyu(
                    is_array($zapis->detalizatsiya) ? $zapis->detalizatsiya : null
                );
                $tablitsa = AuditJournalDisplay::podgotovitStrokiTablitsy($izmeneniyaRaw);
                $tip = AuditJournalDisplay::nadpisTipaObyekta($zapis->obyekt_type);
                $objLabel = match ($zapis->obyekt_type) {
                    Property::class => $propertyTitles[$zapis->obyekt_id] ?? ('Объявление #'.$zapis->obyekt_id),
                    Contract::class => $contractLabels[$zapis->obyekt_id] ?? ('Договор #'.$zapis->obyekt_id),
                    User::class => $userObjectLabels[$zapis->obyekt_id] ?? ('Пользователь #'.$zapis->obyekt_id),
                    default => '#'.$zapis->obyekt_id,
                };
                $objUrl = match ($zapis->obyekt_type) {
                    Property::class => route('properties.show', $zapis->obyekt_id),
                    Contract::class => route('contracts.show', $zapis->obyekt_id),
                    User::class => route('admin.users.edit', $zapis->obyekt_id),
                    default => null,
                };
            @endphp
            <div class="card p-5">
                <div class="flex flex-wrap items-start justify-between gap-2 mb-3">
                    <div>
                        <span class="font-semibold text-lg">{{ AuditJournalDisplay::nadpisDeystviya($zapis->deystvie) }}</span>
                        <span class="badge ml-2">{{ $tip }}</span>
                    </div>
                    <span class="text-sm text-gray-500 whitespace-nowrap">{{ $zapis->sozdano_at?->format('d.m.Y H:i:s') }}</span>
                </div>
                <div class="text-sm text-gray-700 space-y-1 mb-3">
                    <p>
                        <span class="text-gray-500">Объект:</span>
                        @if($objUrl)
                            <a href="{{ $objUrl }}" class="text-brand-700 underline hover:no-underline">{{ $objLabel }}</a>
                            <span class="text-gray-400">(ID {{ $zapis->obyekt_id }})</span>
                        @else
                            {{ $objLabel }} (ID {{ $zapis->obyekt_id }})
                        @endif
                    </p>
                    <p>
                        <span class="text-gray-500">Кто выполнил:</span>
                        @if($zapis->polzovatel)
                            {{ trim($zapis->polzovatel->familia.' '.$zapis->polzovatel->imya.' '.($zapis->polzovatel->otchestvo ?? '')) }}
                            <span class="text-gray-400">({{ $zapis->polzovatel->email_polzovatela }})</span>
                        @else
                            <span class="text-gray-400">система</span>
                        @endif
                    </p>
                    @if($zapis->kommentariy)
                        <p><span class="text-gray-500">Комментарий:</span> {{ $zapis->kommentariy }}</p>
                    @endif
                </div>
                @if(count($tablitsa) > 0)
                    <div class="overflow-x-auto rounded-lg border border-slate-200">
                        <table class="w-full text-sm">
                            <thead class="bg-slate-50">
                                <tr class="text-left border-b border-slate-200">
                                    <th class="p-2">Поле</th>
                                    <th class="p-2">Было</th>
                                    <th class="p-2">Стало</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($tablitsa as $st)
                                    <tr class="border-b border-slate-100 align-top">
                                        <td class="p-2 font-medium">{{ $st['nadpis_polya'] }}</td>
                                        <td class="p-2 text-gray-600">{{ $st['bilo'] !== '' ? $st['bilo'] : '—' }}</td>
                                        <td class="p-2">{{ $st['stalo'] !== '' ? $st['stalo'] : '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
    <div class="mt-8">{{ $logs->links() }}</div>
@else
    <div class="card p-12 text-center text-gray-600">
        <p class="text-lg mb-2">Записей не найдено</p>
        <p class="text-sm">Измените фильтры или выполните действия в системе — они появятся здесь автоматически.</p>
    </div>
@endif
@endsection
