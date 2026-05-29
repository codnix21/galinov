@extends('layouts.app')

@section('title', 'Показы')

@section('content')
@include('partials.realtor-nav')

<div class="mb-6 flex flex-wrap justify-between gap-4">
    <h1 class="text-3xl font-bold">Показы объектов</h1>
    <div class="flex gap-2">
        <a href="{{ route('realtor.showings.index') }}" class="btn {{ request('filter') !== 'past' ? 'btn-primary' : '' }}">Предстоящие</a>
        <a href="{{ route('realtor.showings.index', ['filter' => 'past']) }}" class="btn {{ request('filter') === 'past' ? 'btn-primary' : '' }}">Прошедшие</a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <div class="lg:col-span-2 space-y-4">
        @forelse($showings as $showing)
            <div class="card p-6">
                <div class="flex justify-between gap-4 flex-wrap">
                    <div>
                        <div class="font-bold text-lg">{{ $showing->property?->nazvanie }}</div>
                        <p class="text-sm text-gray-600">
                            {{ $showing->naznacheno_na->format('d.m.Y H:i') }} ·
                            {{ trim($showing->client->familia.' '.$showing->client->imya) }}
                        </p>
                        @if($showing->rezultat)
                            <span class="badge mt-2">{{ $resultOptions[$showing->rezultat] ?? $showing->rezultat }}</span>
                        @endif
                    </div>
                    <a href="{{ route('properties.show', $showing->nedvizhimost_id) }}" class="btn text-sm">Объект</a>
                </div>
                <form method="POST" action="{{ route('realtor.showings.update', $showing) }}" class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="form-label text-xs">Результат</label>
                        <select name="rezultat" class="form-input text-sm">
                            @foreach($resultOptions as $k => $label)
                                <option value="{{ $k }}" {{ $showing->rezultat === $k ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="form-label text-xs">Заметки</label>
                        <input type="text" name="zametki" value="{{ $showing->zametki }}" class="form-input text-sm">
                    </div>
                    <button type="submit" class="btn text-sm">Сохранить</button>
                </form>
            </div>
        @empty
            <div class="card p-8 text-center text-gray-500">Показов нет</div>
        @endforelse
        {{ $showings->links() }}
    </div>

    <div class="card p-6 h-fit">
        <h2 class="font-bold mb-4">Запланировать показ</h2>
        <form method="POST" action="{{ route('realtor.showings.store') }}" class="space-y-3">
            @csrf
            <div>
                <label class="form-label">Клиент</label>
                <select name="klient_id" class="form-input" required>
                    <option value="">— выберите клиента —</option>
                    @foreach($clientOptions as $rc)
                        <option value="{{ $rc->klient_id }}" @selected((int) old('klient_id', $preselectedClientId ?? 0) === (int) $rc->klient_id)>
                            {{ trim($rc->client->familia.' '.$rc->client->imya) }}
                        </option>
                    @endforeach
                </select>
            </div>
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
            <button type="submit" class="btn-primary w-full">Создать</button>
        </form>
    </div>
</div>
@endsection
