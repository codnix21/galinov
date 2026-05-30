@extends('layouts.app')

@section('title', 'Заявки на подбор')

@section('content')
@include('partials.realtor-nav')

<div class="flex flex-wrap justify-between gap-3 mb-6">
    <div>
        <h1 class="text-3xl font-bold mb-2">Заявки на подбор</h1>
        <p class="text-sm text-gray-600">Клиенты оставили заявку из каталога, когда по фильтрам ничего не нашлось.</p>
    </div>
    <a href="{{ route('realtor.templates.index') }}" class="btn text-sm">Шаблоны ответов</a>
</div>

@forelse($requests as $req)
    <div class="card p-5 mb-4 {{ $req->status === 'new' ? 'border-amber-300 border-2' : '' }}">
        <div class="flex flex-wrap justify-between gap-2">
            <div class="flex-1">
                <p class="font-bold">{{ $req->imya }}</p>
                <p class="text-sm text-gray-600">{{ $req->telefon ?? $req->email ?? '—' }}</p>
                <p class="text-sm mt-2"><span class="text-gray-500">Критерии:</span> {{ $req->filtersSummary() }}</p>
                <p class="text-xs text-gray-500">Источник: {{ ($req->istochnik ?? 'catalog') === 'form' ? 'заявка на подбор (форма)' : 'каталог (ничего не найдено)' }}</p>
                @if($req->kommentariy)
                    <p class="text-sm mt-2 whitespace-pre-line">{{ $req->kommentariy }}</p>
                @endif
                <p class="text-xs text-gray-500 mt-1">{{ $req->sozdano_at?->format('d.m.Y H:i') }}</p>
                @if($req->assignedRealtor)
                    <p class="text-xs text-brand-800 mt-1">Назначен: {{ trim($req->assignedRealtor->familia.' '.$req->assignedRealtor->imya) }}</p>
                @endif
                @include('partials.lead-assign-form', [
                    'action' => route('realtor.selection-requests.assign', $req),
                    'realtors' => $realtors,
                    'assignedId' => $req->naznachen_rieltor_id,
                ])
            </div>
            @if($req->status === 'new')
                <form method="POST" action="{{ route('realtor.selection-requests.process', $req) }}">
                    @csrf
                    <button type="submit" class="btn-primary text-sm">Отметить обработанной</button>
                </form>
            @endif
        </div>
    </div>
@empty
    <div class="card p-8 text-center text-gray-600">Заявок на подбор пока нет.</div>
@endforelse

<div class="mt-6">{{ $requests->links() }}</div>
@endsection
