@extends('layouts.app')

@section('title', 'Клиенты риэлтора')

@section('content')
@include('partials.realtor-nav')

<div class="mb-6 flex flex-wrap justify-between gap-4">
    <div>
        <h1 class="text-3xl font-bold">Клиенты</h1>
        <p class="text-gray-600">Закреплённые клиенты и воронка</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <div class="lg:col-span-2 space-y-6">
        <div class="card p-6">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="form-label">Статус</label>
                    <select name="status" class="form-input">
                        <option value="">Все</option>
                        @foreach($statusOptions as $k => $label)
                            <option value="{{ $k }}" {{ request('status') === $k ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="form-label">Поиск</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-input" placeholder="ФИО или email">
                </div>
                <div class="md:col-span-3 flex gap-2">
                    <button type="submit" class="btn-primary">Фильтр</button>
                    <a href="{{ route('realtor.clients.index') }}" class="btn">Сбросить</a>
                </div>
            </form>
        </div>

        @forelse($clients as $row)
            <div class="card p-6 flex flex-wrap justify-between gap-4 items-center">
                <div>
                    <a href="{{ route('realtor.clients.show', $row) }}" class="text-lg font-bold hover:underline">
                        {{ trim($row->client->familia . ' ' . $row->client->imya . ' ' . ($row->client->otchestvo ?? '')) }}
                    </a>
                    <p class="text-sm text-gray-600">{{ $row->client->email_polzovatela }} · {{ $row->client->telefon }}</p>
                    <span class="badge mt-2">{{ $statusOptions[$row->status] ?? $row->status }}</span>
                </div>
                <a href="{{ route('realtor.clients.show', $row) }}" class="btn">Карточка →</a>
            </div>
        @empty
            <div class="card p-8 text-center text-gray-500">Нет закреплённых клиентов</div>
        @endforelse
        <div>{{ $clients->links() }}</div>
    </div>

    <div class="card p-6 h-fit">
        <h2 class="text-lg font-bold mb-4">Закрепить клиента</h2>
        <form method="POST" action="{{ route('realtor.clients.store') }}" class="space-y-4">
            @csrf
            <div>
                <label class="form-label">Клиент</label>
                <select name="klient_id" class="form-input" required>
                    <option value="">Выберите…</option>
                    @foreach($availableClients as $c)
                        <option value="{{ $c->id }}">{{ trim($c->familia.' '.$c->imya) }} — {{ $c->email_polzovatela }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Статус</label>
                <select name="status" class="form-input">
                    @foreach($statusOptions as $k => $label)
                        <option value="{{ $k }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Заметки</label>
                <textarea name="zametki" class="form-input" rows="4" placeholder="Что ищет, бюджет, сроки…"></textarea>
            </div>
            <button type="submit" class="btn-primary w-full">Закрепить</button>
        </form>
    </div>
</div>
@endsection
