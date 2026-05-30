@extends('layouts.app')

@section('title', 'Сохранённые поиски')

@section('content')
<h1 class="text-3xl font-bold mb-6">Сохранённые поиски</h1>
<p class="text-sm text-slate-600 mb-6">Сохраняйте фильтры каталога — при появлении новых объявлений придёт уведомление (проверка два раза в день).</p>

@forelse($searches as $search)
    <div class="card p-5 mb-4 flex flex-wrap justify-between gap-3">
        <div>
            <p class="font-bold">{{ $search->nazvanie }}</p>
            <p class="text-xs text-slate-500 mt-1">Создан {{ $search->created_at?->format('d.m.Y') }}</p>
            <a href="{{ route('properties.index', $search->filtry ?? []) }}" class="text-sm text-brand-700 hover:underline mt-2 inline-block">Открыть в каталоге →</a>
        </div>
        <div class="flex flex-wrap gap-2 items-start">
            <form method="POST" action="{{ route('saved-searches.toggle', $search) }}">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn text-sm">{{ $search->uvedomleniya ? '🔔 Уведомления вкл.' : 'Уведомления выкл.' }}</button>
            </form>
            <form method="POST" action="{{ route('saved-searches.destroy', $search) }}" onsubmit="return confirm('Удалить?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn text-sm text-red-700">Удалить</button>
            </form>
        </div>
    </div>
@empty
    <div class="card p-8 text-center text-slate-600">
        Пока нет сохранённых поисков. В каталоге нажмите «Сохранить поиск» после настройки фильтров.
    </div>
@endforelse

<div class="mt-6">{{ $searches->links() }}</div>
<a href="{{ route('properties.index') }}" class="btn mt-4 inline-block">В каталог</a>
@endsection
