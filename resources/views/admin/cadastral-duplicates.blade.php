@extends('layouts.app')

@section('title', 'Дубликаты кадастра')

@section('content')
<div class="mb-8">
    <a href="{{ route('admin.dashboard') }}" class="text-sm text-brand-700 hover:underline">← Админ-панель</a>
    <h1 class="text-3xl font-bold mt-2">Дубликаты по кадастровому номеру</h1>
    <p class="text-sm text-slate-600 mt-1">Активные и на модерации объявления с одинаковым кадастром.</p>
</div>

@if($groups->isEmpty())
    <div class="card p-8 text-center text-slate-600">Дубликатов не найдено.</div>
@else
    @foreach($groups as $cadastral => $items)
        <div class="card p-5 mb-4">
            <h2 class="font-bold text-brand-800 mb-3">{{ $items->first()->kadastrovy_nomer }}</h2>
            <ul class="space-y-2 text-sm">
                @foreach($items as $p)
                    <li class="flex flex-wrap gap-2 items-center">
                        <a href="{{ route('admin.properties.edit', $p) }}" class="font-medium hover:underline">№{{ $p->id }}</a>
                        <span class="text-slate-600">{{ $p->nazvanie }}</span>
                        <span class="badge bg-slate-100">{{ $p->status_obyavleniya ?? $p->status }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endforeach
@endif
@endsection
