{{-- Все договоры в админке. --}}
@extends('layouts.app')

@section('title', 'Управление договорами')

@section('content')
<div class="mb-8">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-4xl font-bold mb-2">Управление договорами</h1>
            <p class="text-gray-600">Всего договоров: {{ $contracts->total() }}</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.dashboard') }}" class="btn">
                ← Назад
            </a>
            <a href="{{ route('admin.contracts.create') }}" class="btn-primary">
                + Создать договор
            </a>
        </div>
    </div>
    
    <!-- Поиск -->
    <div class="card p-4 mb-6">
        <form method="GET" action="{{ route('admin.contracts') }}" class="flex gap-3">
            <input type="text" name="search" value="{{ request('search') }}" class="form-input flex-1" placeholder="Поиск по объекту, клиенту, риэлтору, примечаниям...">
            <button type="submit" class="btn-primary">Поиск</button>
            @if(request('search'))
                <a href="{{ route('admin.contracts') }}" class="btn">Сбросить</a>
            @endif
        </form>
    </div>

    @if($contracts->count() > 0)
    <form id="bulk-contracts-form" method="POST" action="{{ route('admin.bulk.contracts') }}" class="card p-4 mb-6 flex flex-wrap items-end gap-3"
          data-confirm="Изменить статус у всех отмеченных договоров?" data-confirm-title="Массовая операция">
        @csrf
        <span class="text-sm font-medium">Массовые действия:</span>
        <select name="status_kod" class="form-input w-full sm:w-48" required>
            @foreach($contractStatuses ?? [] as $st)
                <option value="{{ $st->kod }}">{{ $st->nazvanie }}</option>
            @endforeach
        </select>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="confirm_bulk" value="1" required> Подтверждаю
        </label>
        <button type="submit" class="btn-primary">Применить</button>
    </form>
    @endif
</div>

@if($contracts->count() > 0)
    <div class="space-y-4">
        @foreach($contracts as $contract)
            <div class="card p-6">
                <div class="flex items-start justify-between gap-3">
                    <label class="shrink-0 pt-1">
                        <input type="checkbox" name="ids[]" value="{{ $contract->id }}" form="bulk-contracts-form" class="rounded border-slate-300">
                    </label>
                    <div class="flex-1">
                        <div class="mb-3 flex items-center gap-2 flex-wrap">
                            <span class="badge">{{ $contract->type_name }}</span>
                            <span class="badge">{{ $contract->status_name }}</span>
                        </div>
                        <h3 class="text-xl font-bold mb-2">
                            Договор #{{ $contract->id }} - {{ $contract->property->nazvanie }}
                        </h3>
                        <div class="space-y-2 mb-3 text-sm text-gray-600">
                            <div class="flex flex-wrap items-center gap-x-4 gap-y-1">
                                <span>Владелец: <span class="font-medium text-black">
                                    @if($contract->owner)
                                        {{ trim($contract->owner->familia . ' ' . $contract->owner->imya . ' ' . ($contract->owner->otchestvo ?? '')) }}
                                    @else — @endif
                                </span></span>
                                <span>Покупатель: <span class="font-medium text-black">
                                    @if($contract->buyer)
                                        {{ trim($contract->buyer->familia . ' ' . $contract->buyer->imya . ' ' . ($contract->buyer->otchestvo ?? '')) }}
                                    @elseif($contract->client)
                                        {{ trim($contract->client->familia . ' ' . $contract->client->imya . ' ' . ($contract->client->otchestvo ?? '')) }}
                                    @else — @endif
                                </span></span>
                                <span>Риэлтор: <span class="font-medium text-black">
                                    @if($contract->realtor)
                                        {{ trim($contract->realtor->familia . ' ' . $contract->realtor->imya . ' ' . ($contract->realtor->otchestvo ?? '')) }}
                                    @else — @endif
                                </span></span>
                            </div>
                            <div class="flex items-center gap-4">
                                <span>Цена: <span class="font-medium text-black">{{ number_format($contract->tsena, 0, ',', ' ') }} ₽</span></span>
                                <span>Дата начала: <span class="font-medium text-black">{{ $contract->data_nachala->format('d.m.Y') }}</span></span>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 ml-6">
                        <a href="{{ route('admin.contracts.pdf', $contract) }}" class="btn hover:bg-green-600 hover:border-green-600 hover:text-white" target="_blank" rel="noopener">
                            PDF (с обязанностями)
                        </a>
                        <a href="{{ route('admin.contracts.edit', $contract) }}" class="btn">
                            Редактировать
                        </a>
                        <form method="POST" action="{{ route('admin.contracts.delete', $contract) }}" class="inline delete-form" data-type="договор" data-name="Договор #{{ $contract->id }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn hover:bg-red-600 hover:border-red-600 hover:text-white">
                                Удалить
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-8">
        {{ $contracts->links() }}
    </div>
@else
    <div class="card p-12 text-center">
        <p class="text-xl text-gray-600 mb-4">Договоры не найдены</p>
        <a href="{{ route('admin.contracts.create') }}" class="btn-primary inline-block">
            Создать первый договор
        </a>
    </div>
@endif
@endsection


