@extends('layouts.app')

@section('title', 'Запросы доп. информации')

@section('content')
@include('partials.realtor-nav')

<h1 class="text-3xl font-bold mb-2">Запросы дополнительной информации</h1>
<p class="text-sm text-gray-600 mb-6">Клиенты уточняют документы, срок владения, обременения по объектам.</p>

@forelse($requests as $req)
    <div class="card p-5 mb-4 {{ $req->status === 'open' ? 'border-amber-300 border-2' : '' }}">
        <div class="flex flex-wrap justify-between gap-2 mb-2">
            <div>
                <a href="{{ route('properties.show', $req->property) }}" class="font-bold hover:underline">{{ $req->property?->nazvanie }}</a>
                <p class="text-sm text-gray-600">{{ $req->tipLabel() }} · {{ $req->client?->name ?? 'Клиент' }}</p>
            </div>
            @if($req->status === 'closed')
                <span class="text-xs text-gray-500">Закрыт</span>
            @elseif($req->status === 'answered')
                <span class="text-xs text-green-700">Есть ответ</span>
            @endif
        </div>

        <div class="space-y-2 mb-4 text-sm">
            @foreach($req->messages as $msg)
                <div class="p-2 rounded {{ $msg->isStaff() ? 'bg-brand-50' : 'bg-slate-50' }}">
                    <p class="text-xs text-gray-500">{{ $msg->isStaff() ? 'Менеджер' : 'Клиент' }} · {{ $msg->sozdano_at?->format('d.m.Y H:i') }}</p>
                    <p class="whitespace-pre-line">{{ $msg->tekst }}</p>
                </div>
            @endforeach
        </div>

        @if($req->status !== 'closed')
            <form method="POST" action="{{ route('realtor.info-requests.reply', $req) }}" class="space-y-2 mb-2">
                @csrf
                <textarea name="tekst" rows="2" required class="form-input text-sm" placeholder="Ответ клиенту"></textarea>
                <button type="submit" class="btn-primary text-sm">Ответить</button>
            </form>
            <form method="POST" action="{{ route('realtor.info-requests.close', $req) }}" class="inline">
                @csrf
                <button type="submit" class="text-sm text-gray-600 hover:underline">Закрыть запрос</button>
            </form>
        @endif
    </div>
@empty
    <div class="card p-8 text-center text-gray-600">Запросов пока нет.</div>
@endforelse

<div class="mt-6">{{ $requests->links() }}</div>
@endsection
