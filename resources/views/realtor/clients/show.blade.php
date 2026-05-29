@extends('layouts.app')

@section('title', 'Клиент')

@section('content')
@include('partials.realtor-nav')

@if(session('success'))
    <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-900 mb-4">{{ session('success') }}</div>
@endif

<div class="mb-6">
    <a href="{{ route('realtor.clients.index') }}" class="text-sm text-brand-700 hover:underline">← К списку клиентов</a>
    <h1 class="text-3xl font-bold mt-2">
        {{ trim($assignment->client->familia . ' ' . $assignment->client->imya . ' ' . ($assignment->client->otchestvo ?? '')) }}
    </h1>
    <p class="text-gray-600">{{ $assignment->client->email_polzovatela }} · {{ $assignment->client->telefon }}</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <div class="card p-6">
        <h2 class="font-bold mb-4">Карточка CRM</h2>
        <form method="POST" action="{{ route('realtor.clients.update', $assignment) }}" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="form-label">Статус воронки</label>
                <select name="status" class="form-input">
                    @foreach($statusOptions as $k => $label)
                        <option value="{{ $k }}" {{ $assignment->status === $k ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Заметки</label>
                <textarea name="zametki" class="form-input" rows="6">{{ old('zametki', $assignment->zametki) }}</textarea>
            </div>
            <button type="submit" class="btn-primary">Сохранить</button>
        </form>
        <form method="POST" action="{{ route('realtor.clients.destroy', $assignment) }}" class="mt-4 js-confirm-action" data-confirm="Снять закрепление клиента?">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn text-red-700">Снять закрепление</button>
        </form>
    </div>

    <div class="lg:col-span-2 space-y-6">
        <div class="card p-6">
            <h2 class="font-bold mb-3">Договоры</h2>
            @forelse($contracts as $c)
                <div class="py-2 border-b border-gray-100 last:border-0">
                    <a href="{{ route('contracts.show', $c) }}" class="font-medium hover:underline">{{ $c->property?->nazvanie ?? 'Договор #'.$c->id }}</a>
                    <span class="text-sm text-gray-500"> — {{ number_format($c->tsena, 0, ',', ' ') }} ₽</span>
                </div>
            @empty
                <p class="text-gray-500 text-sm">Договоров пока нет</p>
            @endforelse
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="card p-6">
                <h2 class="font-bold mb-3">Задачи</h2>
                @forelse($tasks as $t)
                    <div class="text-sm py-2 border-b {{ $t->isDone() ? 'opacity-50' : '' }}">
                        {{ $t->nazvanie }}
                        @if($t->srok_do)<span class="text-gray-500">до {{ $t->srok_do->format('d.m.Y H:i') }}</span>@endif
                    </div>
                @empty
                    <p class="text-gray-500 text-sm">Нет задач</p>
                @endforelse
                <a href="{{ route('realtor.tasks.index') }}" class="text-sm text-brand-700 mt-2 inline-block">Все задачи →</a>
            </div>
            <div class="card p-6">
                <h2 class="font-bold mb-3">Показы</h2>
                @forelse($showings as $s)
                    <div class="text-sm py-2 border-b">
                        {{ $s->property?->nazvanie }} — {{ $s->naznacheno_na->format('d.m.Y H:i') }}
                    </div>
                @empty
                    <p class="text-gray-500 text-sm">Нет показов</p>
                @endforelse
                <a href="{{ route('realtor.showings.index', ['klient_id' => $assignment->klient_id]) }}" class="text-sm text-brand-700 mt-2 inline-block">Все показы →</a>
            </div>
        </div>

        <div class="card p-6">
            <h2 class="font-bold mb-4">Запланировать показ</h2>
            <form method="POST" action="{{ route('realtor.showings.store') }}" class="space-y-3">
                @csrf
                <input type="hidden" name="klient_id" value="{{ $assignment->klient_id }}">
                <p class="text-sm text-gray-600">
                    Клиент: <strong>{{ trim($assignment->client->familia.' '.$assignment->client->imya) }}</strong>
                </p>
                <div>
                    <label class="form-label">Объект</label>
                    <select name="nedvizhimost_id" class="form-input" required>
                        <option value="">— выберите объявление —</option>
                        @foreach($propertyOptions as $p)
                            <option value="{{ $p->id }}" @selected((int) old('nedvizhimost_id') === (int) $p->id)>
                                {{ $p->nazvanie }}@if($p->adres_ulitsy) — {{ $p->adres_ulitsy }}@endif
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Дата и время</label>
                    <input type="datetime-local" name="naznacheno_na" class="form-input" value="{{ old('naznacheno_na') }}" required>
                </div>
                <textarea name="zametki" class="form-input" rows="2" placeholder="Комментарий">{{ old('zametki') }}</textarea>
                <button type="submit" class="btn-primary">Создать показ</button>
            </form>
        </div>
    </div>
</div>
@endsection
